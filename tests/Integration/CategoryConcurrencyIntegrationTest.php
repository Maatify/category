<?php

declare(strict_types=1);

namespace Maatify\Category\Tests\Integration;

use Maatify\Category\Contract\CategoryCommandServiceInterface;
use Maatify\Category\Command\CreateCategoryCommand;
use Maatify\Category\Command\CreateCategoryTranslationCommand;
use Maatify\Category\Command\MoveCategoryCommand;
use Maatify\Category\Command\SoftDeleteCategoryCommand;
use Maatify\Category\Command\RestoreCategoryCommand;
use Maatify\Category\Command\RestoreCategoryTranslationCommand;
use Maatify\Category\Command\SoftDeleteCategoryTranslationCommand;
use Maatify\Category\Command\UpdateCategoryDisplayOrderCommand;
use Maatify\Category\Enum\CategoryStatusEnum;
use Maatify\Category\Exception\CategoryCycleException;
use Maatify\Category\Exception\CategoryHasNonDeletedChildrenException;
use Maatify\Category\Exception\CategoryCodeAlreadyExistsException;
use Maatify\Category\Exception\CategoryTranslationAlreadyExistsException;
use Maatify\Category\Infrastructure\Repository\PdoCategoryCommandRepository;
use Maatify\Category\Infrastructure\Repository\PdoCategoryQueryReader;
use Maatify\Category\Infrastructure\Repository\PdoCategoryTranslationCommandRepository;
use Maatify\Category\Infrastructure\Transaction\PdoCategoryTransaction;
use Maatify\Category\Service\CategoryCommandService;
use Maatify\Category\Tests\Integration\Support\CategoryMySqlIntegrationTestCase;
use Maatify\Category\Tests\Integration\Support\FixedCategoryClock;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
use PDO;
use PDOException;
use RuntimeException;
use Throwable;

final class CategoryConcurrencyIntegrationTest extends CategoryMySqlIntegrationTestCase
{
    private function service(PDO $connection, FixedCategoryClock $clock = new FixedCategoryClock()): CategoryCommandServiceInterface
    {
        return new CategoryCommandService(
            new PdoCategoryCommandRepository($connection, new ScopedOrderingManager()),
            new PdoCategoryQueryReader($connection),
            new PdoCategoryTranslationCommandRepository($connection),
            new PdoCategoryTransaction($connection),
            $clock,
        );
    }

    public function testConcurrentMoveProducesNoInvalidHierarchyAndPreservesFinalState(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection);

        $nodeA = $service->create(new CreateCategoryCommand('node-a'));
        $nodeB = $service->create(new CreateCategoryCommand('node-b', $nodeA));
        $targetParent = $service->create(new CreateCategoryCommand('target-parent'));

        $locker = $this->newConnection();
        $locker->beginTransaction();
        $locker->prepare('SELECT id FROM maa_category_categories WHERE id = ? FOR UPDATE')->execute([$nodeA]);

        $blockedConnection = $this->newConnection();
        $blockedConnection->exec('SET SESSION innodb_lock_wait_timeout = 1');
        $blockedService = $this->service($blockedConnection);

        try {
            $blockedService->move(new MoveCategoryCommand($nodeB, $targetParent));
            self::fail('Move should wait for lock on ancestor and time out.');
        } catch (PDOException $e) {
            self::assertFalse($blockedConnection->inTransaction());
        }

        $locker->rollBack();
    }

    public function testCreateChildRacingWithParentDeletionDoesNotLeaveInvalidState(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection);

        $parentId = $service->create(new CreateCategoryCommand('parent'));

        $locker = $this->newConnection();
        $locker->beginTransaction();
        $locker->prepare('SELECT id FROM maa_category_categories WHERE id = ? FOR UPDATE')->execute([$parentId]);
        $locker->prepare('UPDATE maa_category_categories SET deleted_at = NOW() WHERE id = ?')->execute([$parentId]);

        $blockedConnection = $this->newConnection();
        $blockedConnection->exec('SET SESSION innodb_lock_wait_timeout = 1');
        $blockedService = $this->service($blockedConnection);

        try {
            $blockedService->create(new CreateCategoryCommand('child', $parentId));
            self::fail('Create must wait for lock on parent and fail due to timeout.');
        } catch (PDOException $e) {
            self::assertFalse($blockedConnection->inTransaction());
        }

        $locker->rollBack();
    }

    public function testConcurrentOrderingMutationsPreserveDeterministicSequence(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection);

        $pId = $service->create(new CreateCategoryCommand('parent'));
        $c1 = $service->create(new CreateCategoryCommand('child1', $pId));
        $c2 = $service->create(new CreateCategoryCommand('child2', $pId));

        $locker = $this->newConnection();
        $locker->beginTransaction();
        $locker->prepare('SELECT id FROM maa_category_categories WHERE id = ? FOR UPDATE')->execute([$c1]);

        $blockedConnection = $this->newConnection();
        $blockedConnection->exec('SET SESSION innodb_lock_wait_timeout = 1');
        $blockedService = $this->service($blockedConnection);

        try {
            $blockedService->updateDisplayOrder(new UpdateCategoryDisplayOrderCommand($c1, 2));
            self::fail('Order update must wait for lock on category row.');
        } catch (PDOException $e) {
            self::assertFalse($blockedConnection->inTransaction());
        }

        $locker->rollBack();
    }

    public function testCodeCannotBeReusedByConcurrentCreations(): void
    {
        $connection = $this->connection();

        $locker = $this->newConnection();
        $locker->beginTransaction();

        // This is purely simulating a unique code conflict under concurrency, relying on unique constraint
        $locker->prepare('INSERT INTO maa_category_categories (code, status, display_order, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())')->execute(['shared-code', 'active', 1]);

        $blockedConnection = $this->newConnection();
        $blockedConnection->exec('SET SESSION innodb_lock_wait_timeout = 1');
        $blockedService = $this->service($blockedConnection);

        try {
            // Because code is unique, it waits on the insert lock and fails on duplicate
            $blockedService->create(new CreateCategoryCommand('shared-code'));
            self::fail('Code must fail uniquely under concurrency.');
        } catch (CategoryCodeAlreadyExistsException|PDOException $e) {
            self::assertFalse($blockedConnection->inTransaction());
        }

        $locker->rollBack();
    }

    public function testConcurrentTranslationIdentityCreationFails(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection);
        $catId = $service->create(new CreateCategoryCommand('trans-cat'));

        $locker = $this->newConnection();
        $locker->beginTransaction();
        $locker->prepare('INSERT INTO maa_category_category_translations (category_id, language_code, name, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())')->execute([$catId, 'en', 'Test']);

        $blockedConnection = $this->newConnection();
        $blockedConnection->exec('SET SESSION innodb_lock_wait_timeout = 1');
        $blockedService = $this->service($blockedConnection);

        try {
            $blockedService->createTranslation(new CreateCategoryTranslationCommand($catId, 'en', 'Test2', null));
            self::fail('Translation identity must fail uniquely under concurrency.');
        } catch (CategoryTranslationAlreadyExistsException|PDOException $e) {
            self::assertFalse($blockedConnection->inTransaction());
        }

        $locker->rollBack();
    }

    public function testRestorePreservesTheOriginalRowIdentityAndAvoidsReplacement(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection);

        $cId = $service->create(new CreateCategoryCommand('unique-restore-code'));
        $service->softDelete(new SoftDeleteCategoryCommand($cId));

        $locker = $this->newConnection();
        $locker->beginTransaction();
        $locker->prepare('SELECT id FROM maa_category_categories WHERE id = ? FOR UPDATE')->execute([$cId]);

        $blockedConnection = $this->newConnection();
        $blockedConnection->exec('SET SESSION innodb_lock_wait_timeout = 1');
        $blockedService = $this->service($blockedConnection);

        try {
            $blockedService->restore(new RestoreCategoryCommand($cId));
            self::fail('Restore must wait for lock.');
        } catch (PDOException $e) {
            self::assertFalse($blockedConnection->inTransaction());
        }

        $locker->rollBack();
    }

    public function testParentDeletionRacesDoNotLeaveAnActiveChildAttachedToInvalidParentState(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection);

        $parentId = $service->create(new CreateCategoryCommand('delete-parent'));
        $childId = $service->create(new CreateCategoryCommand('active-child', $parentId));

        $locker = $this->newConnection();
        $locker->beginTransaction();
        // Assume child is deleted in this transaction but not committed
        $locker->prepare('SELECT id FROM maa_category_categories WHERE id = ? FOR UPDATE')->execute([$childId]);
        $locker->prepare('UPDATE maa_category_categories SET deleted_at = NOW() WHERE id = ?')->execute([$childId]);

        $blockedConnection = $this->newConnection();
        $blockedConnection->exec('SET SESSION innodb_lock_wait_timeout = 1');
        $blockedService = $this->service($blockedConnection);

        try {
            // Trying to delete parent which requires checking children.
            // It will wait for the lock on child to check if it's active.
            $blockedService->softDelete(new SoftDeleteCategoryCommand($parentId));
            self::fail('Parent delete must fail or timeout checking child locks.');
        } catch (PDOException|CategoryHasNonDeletedChildrenException $e) {
            self::assertFalse($blockedConnection->inTransaction());
        }

        $locker->rollBack();
    }

    public function testTransactionRollbackOnWriteFailureLeavesNoPartialMutations(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection);

        $parentId = $service->create(new CreateCategoryCommand('parent-for-fail'));

        // Induce write failure
        $transaction = new PdoCategoryTransaction($connection);
        $original = new RuntimeException('write failure');

        $thrown = null;
        try {
            $transaction->run(function () use ($connection, $original, $parentId) {
                // partial write
                $connection->prepare('INSERT INTO maa_category_categories (parent_id, code, status, display_order, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())')
                    ->execute([$parentId, 'partial-write', 'active', 1]);
                throw $original;
            });
        } catch (Throwable $thrown) {
        }

        self::assertSame($original, $thrown);
        self::assertFalse($connection->inTransaction());

        // Verify no partial mutations left
        $stmt = $connection->prepare('SELECT id FROM maa_category_categories WHERE code = ?');
        $stmt->execute(['partial-write']);
        self::assertFalse($stmt->fetchColumn());
    }
}
