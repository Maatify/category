<?php

declare(strict_types=1);

namespace Maatify\Category\Tests\Integration;

use Maatify\Category\Contract\CategoryCommandServiceInterface;
use Maatify\Category\DTO\CreateCategoryDTO;
use Maatify\Category\DTO\CreateCategoryTranslationDTO;
use Maatify\Category\DTO\MoveCategoryDTO;
use Maatify\Category\DTO\RestoreCategoryDTO;
use Maatify\Category\DTO\RestoreCategoryTranslationDTO;
use Maatify\Category\DTO\SoftDeleteCategoryDTO;
use Maatify\Category\DTO\SoftDeleteCategoryTranslationDTO;
use Maatify\Category\DTO\UpdateCategoryDisplayOrderDTO;
use Maatify\Category\DTO\UpdateCategoryStatusDTO;
use Maatify\Category\DTO\UpdateCategoryTranslationDTO;
use Maatify\Category\Enum\CategoryStatusEnum;
use Maatify\Category\Exception\CategoryCycleException;
use Maatify\Category\Exception\CategoryHasNonDeletedChildrenException;
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

        $categoryId = $service->create(new CreateCategoryDTO('root-category'));
        self::assertSame(1, $categoryId);
        self::assertSame(
            '2026-01-03 00:00:00',
            $queryReader->findById($categoryId)?->createdAt->format('Y-m-d H:i:s'),
        );

        $service->softDelete(new SoftDeleteCategoryDTO($categoryId));
        $deleted = $queryReader->findById($categoryId);
        self::assertNotNull($deleted->deletedAt);
        self::assertNull($queryReader->findActiveById($categoryId));

        $service->restore(new RestoreCategoryDTO($categoryId));
        $restored = $queryReader->findById($categoryId);
        self::assertSame($categoryId, $restored->id);
        self::assertNull($restored->deletedAt);
        self::assertSame('root-category', $restored->code);
    }

    public function testCompleteAncestorChainRejectsAnIndirectCycleOnMySql(): void
    {
        $service = $this->service($this->connection(), new FixedCategoryClock());
        $a = $service->create(new CreateCategoryDTO('cycle-a'));
        $b = $service->create(new CreateCategoryDTO('cycle-b', $a));
        $c = $service->create(new CreateCategoryDTO('cycle-c', $b));

        $this->expectException(CategoryCycleException::class);
        $service->move(new MoveCategoryDTO($a, $c));
    }

    public function testCreatedRootAndChildRowsReceiveScopedPositionsAndMoveImmediately(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection, new FixedCategoryClock());
        $firstRootId = $service->create(new CreateCategoryDTO('created-root-first'));
        $secondRootId = $service->create(new CreateCategoryDTO('created-root-second'));
        $firstChildId = $service->create(new CreateCategoryDTO('created-child-first', $firstRootId));
        $secondChildId = $service->create(new CreateCategoryDTO('created-child-second', $firstRootId));

        self::assertSame([
            $firstRootId => 1,
            $secondRootId => 2,
        ], $this->ordersForScope($connection, null));
        self::assertSame([
            $firstChildId => 1,
            $secondChildId => 2,
        ], $this->ordersForScope($connection, $firstRootId));

        $service->updateDisplayOrder(new UpdateCategoryDisplayOrderDTO($secondRootId, 1));
        $service->updateDisplayOrder(new UpdateCategoryDisplayOrderDTO($secondChildId, 1));

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
        $categoryId = $service->create(new CreateCategoryDTO('translation-lifecycle-category'));

        $translationId = $service->createTranslation(
            new CreateCategoryTranslationDTO($categoryId, 'en-US', 'Shirts', 'Base description'),
        );
        $created = $queryReader->findTranslationById($translationId);
        self::assertNotNull($created);
        self::assertSame($translationId, $created->id);
        self::assertSame($categoryId, $created->categoryId);
        self::assertSame('en-US', $created->languageCode);

        $service->updateTranslation(new UpdateCategoryTranslationDTO(
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

        $service->softDeleteTranslation(new SoftDeleteCategoryTranslationDTO($translationId));
        $deleted = $queryReader->findTranslationById($translationId);
        self::assertNotNull($deleted);
        self::assertNotNull($deleted->deletedAt);
        self::assertSame($translationId, $deleted->id);

        $service->restoreTranslation(new RestoreCategoryTranslationDTO($translationId));
        $restored = $queryReader->findTranslationById($translationId);
        self::assertNotNull($restored);
        self::assertSame($translationId, $restored->id);
        self::assertSame($categoryId, $restored->categoryId);
        self::assertSame('en-US', $restored->languageCode);
        self::assertNull($restored->deletedAt);
        self::assertSame('قمصان', $restored->name);
    }

    public function testTranslationCreationRejectsDuplicateLogicalIdentityIncludingSoftDeletedRows(): void
    {
        $service = $this->service($this->connection(), new FixedCategoryClock());
        $categoryId = $service->create(new CreateCategoryDTO('translation-identity-category'));
        $command = new CreateCategoryTranslationDTO($categoryId, 'en-US', 'Shirts', null);

        $translationId = $service->createTranslation($command);
        $service->softDeleteTranslation(new SoftDeleteCategoryTranslationDTO($translationId));

        $this->expectException(CategoryTranslationAlreadyExistsException::class);
        $service->createTranslation($command);
    }

    public function testSoftDeleteChecksNonDeletedChildrenAndAllowsTheParentAfterChildDeletion(): void
    {
        $service = $this->service($this->connection(), new FixedCategoryClock());
        $parentId = $service->create(new CreateCategoryDTO('delete-parent'));
        $childId = $service->create(new CreateCategoryDTO('delete-child', $parentId));

        try {
            $service->softDelete(new SoftDeleteCategoryDTO($parentId));
            self::fail('A parent with a non-deleted child must not be soft-deleted.');
        } catch (CategoryHasNonDeletedChildrenException) {
            // The failed operation must have rolled back before the child deletion.
        }

        $service->softDelete(new SoftDeleteCategoryDTO($childId));
        $service->softDelete(new SoftDeleteCategoryDTO($parentId));

        $queryReader = new PdoCategoryQueryReader($this->connection());
        self::assertNull($queryReader->findActiveById($parentId));
        self::assertNotNull($queryReader->findById($parentId));
    }

    public function testSharedOrderingApiMovesRowsInsideTheSameParentScope(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection, new FixedCategoryClock());
        $parentId = $service->create(new CreateCategoryDTO('ordering-parent'));
        $firstId = $service->create(new CreateCategoryDTO('ordering-first', $parentId));
        $secondId = $service->create(new CreateCategoryDTO('ordering-second', $parentId));

        $service->updateDisplayOrder(new UpdateCategoryDisplayOrderDTO($secondId, 1));

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
        $firstId = $createService->create(new CreateCategoryDTO('root-ordering-first'));
        $secondId = $createService->create(new CreateCategoryDTO('root-ordering-second'));

        $updateService = $this->service($connection, new FixedCategoryClock('2026-01-04 00:00:00 UTC'));
        $updateService->updateDisplayOrder(new UpdateCategoryDisplayOrderDTO($secondId, 1));

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
        $sourceParentId = $service->create(new CreateCategoryDTO('source-parent'));
        $targetParentId = $service->create(new CreateCategoryDTO('target-parent'));
        $categoryId = $service->create(new CreateCategoryDTO('movable-category', $sourceParentId));

        $locker = $this->newConnection();
        $locker->beginTransaction();
        $lockingReader = new PdoCategoryQueryReader($locker);
        self::assertNotNull($lockingReader->findActiveByIdForUpdate($targetParentId));

        $blockedConnection = $this->newConnection();
        $blockedConnection->exec('SET SESSION innodb_lock_wait_timeout = 1');
        $blockedService = $this->service($blockedConnection, new FixedCategoryClock());

        try {
            $blockedService->move(new MoveCategoryDTO($categoryId, $targetParentId));
            self::fail('Moving under a locked parent must wait for the lock.');
        } catch (PDOException) {
            self::assertFalse($blockedConnection->inTransaction());
        } finally {
            if ($locker->inTransaction()) {
                $locker->rollBack();
            }
        }

        $blockedService->move(new MoveCategoryDTO($categoryId, $targetParentId));
        self::assertSame($targetParentId, (new PdoCategoryQueryReader($blockedConnection))->findById($categoryId)?->parentId);
        $blockedConnection = null;
    }

    public function testSoftDeleteWaitsOnTheLockedCategoryRow(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection, new FixedCategoryClock());
        $categoryId = $service->create(new CreateCategoryDTO('locked-delete-category'));

        $locker = $this->newConnection();
        $locker->beginTransaction();
        self::assertNotNull((new PdoCategoryQueryReader($locker))->findActiveByIdForUpdate($categoryId));

        $blockedConnection = $this->newConnection();
        $blockedConnection->exec('SET SESSION innodb_lock_wait_timeout = 1');
        $blockedService = $this->service($blockedConnection, new FixedCategoryClock());

        try {
            $blockedService->softDelete(new SoftDeleteCategoryDTO($categoryId));
            self::fail('Soft delete must wait for a lock on the category row.');
        } catch (PDOException) {
            self::assertFalse($blockedConnection->inTransaction());
        } finally {
            if ($locker->inTransaction()) {
                $locker->rollBack();
            }
        }

        $blockedService->softDelete(new SoftDeleteCategoryDTO($categoryId));
        self::assertNull((new PdoCategoryQueryReader($blockedConnection))->findActiveById($categoryId));
        $blockedConnection = null;
    }

    public function testStatusUpdateWaitsOnTheLockedCategoryRow(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection, new FixedCategoryClock());
        $categoryId = $service->create(new CreateCategoryDTO('locked-status-category'));

        $locker = $this->newConnection();
        $locker->beginTransaction();
        self::assertNotNull((new PdoCategoryQueryReader($locker))->findActiveByIdForUpdate($categoryId));

        $blockedConnection = $this->newConnection();
        $blockedConnection->exec('SET SESSION innodb_lock_wait_timeout = 1');
        $blockedService = $this->service($blockedConnection, new FixedCategoryClock());

        try {
            $blockedService->updateStatus(new UpdateCategoryStatusDTO($categoryId, CategoryStatusEnum::INACTIVE));
            self::fail('Status update must wait for a lock on the category row.');
        } catch (PDOException) {
            self::assertFalse($blockedConnection->inTransaction());
        } finally {
            if ($locker->inTransaction()) {
                $locker->rollBack();
            }
        }

        $blockedService->updateStatus(new UpdateCategoryStatusDTO($categoryId, CategoryStatusEnum::INACTIVE));
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
