<?php

declare(strict_types=1);

namespace Maatify\Category\Tests\Integration;

use Maatify\Category\Contract\CategoryCommandServiceInterface;
use Maatify\Category\Command\CreateCategoryCommand;
use Maatify\Category\Command\CreateCategoryTranslationCommand;
use Maatify\Category\Command\MoveCategoryCommand;
use Maatify\Category\Command\RestoreCategoryCommand;
use Maatify\Category\Command\RestoreCategoryTranslationCommand;
use Maatify\Category\Command\SoftDeleteCategoryCommand;
use Maatify\Category\Command\SoftDeleteCategoryTranslationCommand;
use Maatify\Category\Command\UpdateCategoryDisplayOrderCommand;
use Maatify\Category\Command\UpdateCategoryStatusCommand;
use Maatify\Category\Command\UpdateCategoryTranslationCommand;
use Maatify\Category\Enum\CategoryStatusEnum;
use Maatify\Category\Exception\CategoryCycleException;
use Maatify\Category\Exception\CategoryHasNonDeletedChildrenException;
use Maatify\Category\Exception\CategoryNotFoundException;
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

final class CategoryPdoIntegrationTest extends CategoryMySqlIntegrationTestCase
{
    public function testApplicationClockAndAllStateReaderSupportLifecycleIdentity(): void
    {
        $clock = new FixedCategoryClock();
        $service = $this->service($this->connection(), $clock);
        $queryReader = new PdoCategoryQueryReader($this->connection());

        $categoryId = $service->create(new CreateCategoryCommand('root-category'));
        self::assertSame(1, $categoryId);
        self::assertSame(
            '2026-01-03 00:00:00',
            $queryReader->findById($categoryId)?->createdAt->format('Y-m-d H:i:s'),
        );

        $service->softDelete(new SoftDeleteCategoryCommand($categoryId));
        $deleted = $queryReader->findById($categoryId);
        self::assertNotNull($deleted->deletedAt);
        self::assertNull($queryReader->findActiveById($categoryId));

        $service->restore(new RestoreCategoryCommand($categoryId));
        $restored = $queryReader->findById($categoryId);
        self::assertSame($categoryId, $restored->id);
        self::assertNull($restored->deletedAt);
        self::assertSame('root-category', $restored->code);
    }

    public function testCompleteAncestorChainRejectsAnIndirectCycleOnMySql(): void
    {
        $service = $this->service($this->connection(), new FixedCategoryClock());
        $a = $service->create(new CreateCategoryCommand('cycle-a'));
        $b = $service->create(new CreateCategoryCommand('cycle-b', $a));
        $c = $service->create(new CreateCategoryCommand('cycle-c', $b));

        $this->expectException(CategoryCycleException::class);
        $service->move(new MoveCategoryCommand($a, $c));
    }

    public function testCreatedRootAndChildRowsReceiveScopedPositionsAndMoveImmediately(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection, new FixedCategoryClock());
        $firstRootId = $service->create(new CreateCategoryCommand('created-root-first'));
        $secondRootId = $service->create(new CreateCategoryCommand('created-root-second'));
        $firstChildId = $service->create(new CreateCategoryCommand('created-child-first', $firstRootId));
        $secondChildId = $service->create(new CreateCategoryCommand('created-child-second', $firstRootId));

        self::assertSame([
            $firstRootId => 1,
            $secondRootId => 2,
        ], $this->ordersForScope($connection, null));
        self::assertSame([
            $firstChildId => 1,
            $secondChildId => 2,
        ], $this->ordersForScope($connection, $firstRootId));

        $service->updateDisplayOrder(new UpdateCategoryDisplayOrderCommand($secondRootId, 1));
        $service->updateDisplayOrder(new UpdateCategoryDisplayOrderCommand($secondChildId, 1));

        self::assertSame([
            $secondRootId => 1,
            $firstRootId => 2,
        ], $this->ordersForScope($connection, null));
        self::assertSame([
            $secondChildId => 1,
            $firstChildId => 2,
        ], $this->ordersForScope($connection, $firstRootId));
    }

    public function testTranslationLifecycleIsPackageOwnedAndPreservesLogicalIdentity(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection, new FixedCategoryClock('2026-01-03 00:00:00 UTC'));
        $queryReader = new PdoCategoryQueryReader($connection);
        $categoryId = $service->create(new CreateCategoryCommand('translation-lifecycle-category'));

        $translationId = $service->createTranslation(
            new CreateCategoryTranslationCommand($categoryId, 'en-US', 'Shirts', 'Base description'),
        );
        $created = $queryReader->findTranslationById($translationId);
        self::assertNotNull($created);
        self::assertSame($translationId, $created->id);
        self::assertSame($categoryId, $created->categoryId);
        self::assertSame('en-US', $created->languageCode);

        $service->updateTranslation(new UpdateCategoryTranslationCommand(
            $translationId,
            'قمصان',
            'وصف',
        ));
        $updated = $queryReader->findTranslationById($translationId);
        self::assertNotNull($updated);
        self::assertSame($translationId, $updated->id);
        self::assertSame($categoryId, $updated->categoryId);
        self::assertSame('en-US', $updated->languageCode);
        self::assertSame('قمصان', $updated->name);

        $service->softDeleteTranslation(new SoftDeleteCategoryTranslationCommand($translationId));
        $deleted = $queryReader->findTranslationById($translationId);
        self::assertNotNull($deleted);
        self::assertNotNull($deleted->deletedAt);
        self::assertSame($translationId, $deleted->id);

        $service->restoreTranslation(new RestoreCategoryTranslationCommand($translationId));
        $restored = $queryReader->findTranslationById($translationId);
        self::assertNotNull($restored);
        self::assertSame($translationId, $restored->id);
        self::assertSame($categoryId, $restored->categoryId);
        self::assertSame('en-US', $restored->languageCode);
        self::assertNull($restored->deletedAt);
        self::assertSame('قمصان', $restored->name);
    }

    public function testTranslationMutationsFollowParentLifecycleStateContractOnMySql(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection, new FixedCategoryClock('2026-01-03 00:00:00 UTC'));
        $queryReader = new PdoCategoryQueryReader($connection);

        $inactiveCategoryId = $service->create(new CreateCategoryCommand('inactive-translation-parent'));
        $service->updateStatus(
            new UpdateCategoryStatusCommand($inactiveCategoryId, CategoryStatusEnum::INACTIVE),
        );

        $inactiveTranslationId = $service->createTranslation(
            new CreateCategoryTranslationCommand($inactiveCategoryId, 'en-US', 'Inactive parent', null),
        );
        $inactiveTranslation = $queryReader->findTranslationById($inactiveTranslationId);
        self::assertNotNull($inactiveTranslation);
        self::assertSame($inactiveCategoryId, $inactiveTranslation->categoryId);

        $service->updateTranslation(
            new UpdateCategoryTranslationCommand($inactiveTranslationId, 'Updated inactive parent', null),
        );
        $inactiveTranslation = $queryReader->findTranslationById($inactiveTranslationId);
        self::assertNotNull($inactiveTranslation);
        self::assertSame('Updated inactive parent', $inactiveTranslation->name);

        $service->softDeleteTranslation(new SoftDeleteCategoryTranslationCommand($inactiveTranslationId));
        $inactiveTranslation = $queryReader->findTranslationById($inactiveTranslationId);
        self::assertNotNull($inactiveTranslation);
        self::assertNotNull($inactiveTranslation->deletedAt);

        $service->restoreTranslation(new RestoreCategoryTranslationCommand($inactiveTranslationId));
        $inactiveTranslation = $queryReader->findTranslationById($inactiveTranslationId);
        self::assertNotNull($inactiveTranslation);
        self::assertNull($inactiveTranslation->deletedAt);

        $deletedCategoryId = $service->create(new CreateCategoryCommand('deleted-translation-parent'));
        $deletedTranslationId = $service->createTranslation(
            new CreateCategoryTranslationCommand($deletedCategoryId, 'en-US', 'Deleted parent', null),
        );
        $service->softDelete(new SoftDeleteCategoryCommand($deletedCategoryId));

        try {
            $service->createTranslation(
                new CreateCategoryTranslationCommand($deletedCategoryId, 'ar-EG', 'Rejected', null),
            );
            self::fail('A Translation must not be created under a soft-deleted Category.');
        } catch (CategoryNotFoundException) {
            // Creation requires a non-deleted parent Category.
        }

        $service->updateTranslation(
            new UpdateCategoryTranslationCommand($deletedTranslationId, 'Updated deleted parent', null),
        );
        $deletedTranslation = $queryReader->findTranslationById($deletedTranslationId);
        self::assertNotNull($deletedTranslation);
        self::assertSame('Updated deleted parent', $deletedTranslation->name);

        $service->softDeleteTranslation(new SoftDeleteCategoryTranslationCommand($deletedTranslationId));
        $deletedTranslation = $queryReader->findTranslationById($deletedTranslationId);
        self::assertNotNull($deletedTranslation);
        self::assertNotNull($deletedTranslation->deletedAt);

        $service->restoreTranslation(new RestoreCategoryTranslationCommand($deletedTranslationId));
        $restored = $queryReader->findTranslationById($deletedTranslationId);
        self::assertNotNull($restored);
        self::assertNull($restored->deletedAt);
        self::assertSame($deletedCategoryId, $restored->categoryId);
    }

    public function testTranslationCreationRejectsDuplicateLogicalIdentityIncludingSoftDeletedRows(): void
    {
        $service = $this->service($this->connection(), new FixedCategoryClock());
        $categoryId = $service->create(new CreateCategoryCommand('translation-identity-category'));
        $command = new CreateCategoryTranslationCommand($categoryId, 'en-US', 'Shirts', null);

        $translationId = $service->createTranslation($command);
        $service->softDeleteTranslation(new SoftDeleteCategoryTranslationCommand($translationId));

        $this->expectException(CategoryTranslationAlreadyExistsException::class);
        $service->createTranslation($command);
    }

    public function testSoftDeleteChecksNonDeletedChildrenAndAllowsTheParentAfterChildDeletion(): void
    {
        $service = $this->service($this->connection(), new FixedCategoryClock());
        $parentId = $service->create(new CreateCategoryCommand('delete-parent'));
        $childId = $service->create(new CreateCategoryCommand('delete-child', $parentId));

        try {
            $service->softDelete(new SoftDeleteCategoryCommand($parentId));
            self::fail('A parent with a non-deleted child must not be soft-deleted.');
        } catch (CategoryHasNonDeletedChildrenException) {
            // The failed operation must have rolled back before the child deletion.
        }

        $service->softDelete(new SoftDeleteCategoryCommand($childId));
        $service->softDelete(new SoftDeleteCategoryCommand($parentId));

        $queryReader = new PdoCategoryQueryReader($this->connection());
        self::assertNull($queryReader->findActiveById($parentId));
        self::assertNotNull($queryReader->findById($parentId));
    }

    public function testSharedOrderingApiMovesRowsInsideTheSameParentScope(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection, new FixedCategoryClock());
        $parentId = $service->create(new CreateCategoryCommand('ordering-parent'));
        $firstId = $service->create(new CreateCategoryCommand('ordering-first', $parentId));
        $secondId = $service->create(new CreateCategoryCommand('ordering-second', $parentId));

        $service->updateDisplayOrder(new UpdateCategoryDisplayOrderCommand($secondId, 1));

        $ordersStatement = $connection->query(
            'SELECT `id`, `display_order` FROM `maa_category_categories` '
            . 'WHERE `parent_id` = ' . $parentId . ' ORDER BY `display_order`, `id`',
        );
        if ($ordersStatement === false) {
            self::fail('Unable to inspect Category ordering.');
        }
        /** @var array<int|string, int|string> $orders */
        $orders = $ordersStatement->fetchAll(PDO::FETCH_KEY_PAIR);
        self::assertSame(1, (int) $orders[$secondId]);
        self::assertSame(2, (int) $orders[$firstId]);
    }

    public function testSharedOrderingApiMovesRootRowsAndUpdatesTimestampAtomically(): void
    {
        $connection = $this->connection();
        $createService = $this->service($connection, new FixedCategoryClock('2026-01-03 00:00:00 UTC'));
        $firstId = $createService->create(new CreateCategoryCommand('root-ordering-first'));
        $secondId = $createService->create(new CreateCategoryCommand('root-ordering-second'));

        $updateService = $this->service($connection, new FixedCategoryClock('2026-01-04 00:00:00 UTC'));
        $updateService->updateDisplayOrder(new UpdateCategoryDisplayOrderCommand($secondId, 1));

        $ordersStatement = $connection->query(
            'SELECT `id`, `display_order` FROM `maa_category_categories` '
            . 'WHERE `parent_id` IS NULL ORDER BY `display_order`, `id`',
        );
        if ($ordersStatement === false) {
            self::fail('Unable to inspect root Category ordering.');
        }
        /** @var array<int|string, int|string> $orders */
        $orders = $ordersStatement->fetchAll(PDO::FETCH_KEY_PAIR);
        self::assertSame(1, (int) $orders[$secondId]);
        self::assertSame(2, (int) $orders[$firstId]);

        $timestampStatement = $connection->prepare(
            'SELECT `updated_at` FROM `maa_category_categories` WHERE `id` = :id',
        );
        $timestampStatement->execute(['id' => $secondId]);
        self::assertSame('2026-01-04 00:00:00', $timestampStatement->fetchColumn());
    }

    public function testTransactionPreservesTheOriginalThrowableWhenTransactionIsAlreadyClosed(): void
    {
        $transaction = new PdoCategoryTransaction($this->connection());
        $original = new RuntimeException('original transaction failure');

        $thrown = null;
        try {
            $transaction->run(function () use ($original): void {
                throw $original;
            });
        } catch (Throwable $thrown) {
        }
        self::assertSame($original, $thrown);
        self::assertFalse($this->connection()->inTransaction());

        $thrown = null;
        try {
            $transaction->run(function () use ($original): void {
                // Simulate a driver/operation that closes the transaction before
                // reporting its failure to the transaction adapter.
                $this->connection()->commit();
                throw $original;
            });
        } catch (Throwable $thrown) {
        }
        self::assertSame($original, $thrown);
        self::assertFalse($this->connection()->inTransaction());
    }

    public function testMoveWaitsOnLockedParentAndThenSucceedsAfterTheTransactionReleasesIt(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection, new FixedCategoryClock());
        $sourceParentId = $service->create(new CreateCategoryCommand('source-parent'));
        $targetParentId = $service->create(new CreateCategoryCommand('target-parent'));
        $categoryId = $service->create(new CreateCategoryCommand('movable-category', $sourceParentId));

        $locker = $this->newConnection();
        $locker->beginTransaction();
        $lockingReader = new PdoCategoryQueryReader($locker);
        self::assertNotNull($lockingReader->findActiveByIdForUpdate($targetParentId));

        $blockedConnection = $this->newConnection();
        $blockedConnection->exec('SET SESSION innodb_lock_wait_timeout = 1');
        $blockedService = $this->service($blockedConnection, new FixedCategoryClock());

        try {
            $blockedService->move(new MoveCategoryCommand($categoryId, $targetParentId));
            self::fail('Moving under a locked parent must wait for the lock.');
        } catch (PDOException) {
            self::assertFalse($blockedConnection->inTransaction());
        } finally {
            if ($locker->inTransaction()) {
                $locker->rollBack();
            }
        }

        $blockedService->move(new MoveCategoryCommand($categoryId, $targetParentId));
        self::assertSame($targetParentId, (new PdoCategoryQueryReader($blockedConnection))->findById($categoryId)?->parentId);
        $blockedConnection = null;
    }

    public function testSoftDeleteWaitsOnTheLockedCategoryRow(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection, new FixedCategoryClock());
        $categoryId = $service->create(new CreateCategoryCommand('locked-delete-category'));

        $locker = $this->newConnection();
        $locker->beginTransaction();
        self::assertNotNull((new PdoCategoryQueryReader($locker))->findActiveByIdForUpdate($categoryId));

        $blockedConnection = $this->newConnection();
        $blockedConnection->exec('SET SESSION innodb_lock_wait_timeout = 1');
        $blockedService = $this->service($blockedConnection, new FixedCategoryClock());

        try {
            $blockedService->softDelete(new SoftDeleteCategoryCommand($categoryId));
            self::fail('Soft delete must wait for a lock on the category row.');
        } catch (PDOException) {
            self::assertFalse($blockedConnection->inTransaction());
        } finally {
            if ($locker->inTransaction()) {
                $locker->rollBack();
            }
        }

        $blockedService->softDelete(new SoftDeleteCategoryCommand($categoryId));
        self::assertNull((new PdoCategoryQueryReader($blockedConnection))->findActiveById($categoryId));
        $blockedConnection = null;
    }

    public function testStatusUpdateWaitsOnTheLockedCategoryRow(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection, new FixedCategoryClock());
        $categoryId = $service->create(new CreateCategoryCommand('locked-status-category'));

        $locker = $this->newConnection();
        $locker->beginTransaction();
        self::assertNotNull((new PdoCategoryQueryReader($locker))->findActiveByIdForUpdate($categoryId));

        $blockedConnection = $this->newConnection();
        $blockedConnection->exec('SET SESSION innodb_lock_wait_timeout = 1');
        $blockedService = $this->service($blockedConnection, new FixedCategoryClock());

        try {
            $blockedService->updateStatus(new UpdateCategoryStatusCommand($categoryId, CategoryStatusEnum::INACTIVE));
            self::fail('Status update must wait for a lock on the category row.');
        } catch (PDOException) {
            self::assertFalse($blockedConnection->inTransaction());
        } finally {
            if ($locker->inTransaction()) {
                $locker->rollBack();
            }
        }

        $blockedService->updateStatus(new UpdateCategoryStatusCommand($categoryId, CategoryStatusEnum::INACTIVE));
        self::assertSame(
            CategoryStatusEnum::INACTIVE,
            (new PdoCategoryQueryReader($blockedConnection))->findById($categoryId)?->status,
        );
        $blockedConnection = null;
    }

    /** @return array<int, int> */
    private function ordersForScope(PDO $connection, ?int $parentId): array
    {
        $statement = $connection->prepare(
            'SELECT `id`, `display_order` FROM `maa_category_categories` '
            . 'WHERE `parent_id` <=> :parent_id AND `deleted_at` IS NULL '
            . 'ORDER BY `display_order`, `id`',
        );
        $statement->execute(['parent_id' => $parentId]);
        /** @var array<int|string, int|string> $orders */
        $orders = $statement->fetchAll(PDO::FETCH_KEY_PAIR);

        $normalized = [];
        foreach ($orders as $id => $order) {
            $normalized[(int) $id] = (int) $order;
        }

        return $normalized;
    }

    private function service(PDO $connection, FixedCategoryClock $clock): CategoryCommandServiceInterface
    {
        return new CategoryCommandService(
            new PdoCategoryCommandRepository($connection, new ScopedOrderingManager()),
            new PdoCategoryQueryReader($connection),
            new PdoCategoryTranslationCommandRepository($connection),
            new PdoCategoryTransaction($connection),
            $clock,
        );
    }
}
