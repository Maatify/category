<?php

declare(strict_types=1);

namespace Maatify\Category\Tests\Integration;

use Maatify\Category\Command\CreateCategoryCommand;
use Maatify\Category\Command\CreateCategoryContentFieldCommand;
use Maatify\Category\Command\CreateCategoryImageAssignmentCommand;
use Maatify\Category\Command\SetCategoryImageAssignmentDefaultCommand;
use Maatify\Category\Command\UpdateCategoryContentFieldDisplayOrderCommand;
use Maatify\Category\Command\UpdateCategoryDisplayOrderCommand;
use Maatify\Category\Command\UpdateCategoryImageAssignmentDisplayOrderCommand;
use Maatify\Category\Command\UpdateCategoryStatusCommand;
use Maatify\Category\Enum\CategoryContentFieldFormatEnum;
use Maatify\Category\Enum\CategoryStatusEnum;
use Maatify\Category\Exception\CategoryNotFoundException;
use Maatify\Category\Infrastructure\Repository\PdoCategoryCommandRepository;
use Maatify\Category\Infrastructure\Repository\PdoCategoryContentCommandRepository;
use Maatify\Category\Infrastructure\Repository\PdoCategoryContentFieldCommandRepository;
use Maatify\Category\Infrastructure\Repository\PdoCategoryImageAssignmentCommandRepository;
use Maatify\Category\Infrastructure\Repository\PdoCategoryQueryReader;
use Maatify\Category\Service\CategoryCommandService;
use Maatify\Category\Tests\Integration\Support\CategoryMySqlIntegrationTestCase;
use Maatify\Category\Tests\Integration\Support\FixedCategoryClock;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
use Maatify\Persistence\Pdo\Transaction\PdoTransactionRunner;
use PDO;

final class CategoryTransactionIntegrationTest extends CategoryMySqlIntegrationTestCase
{
    public function testCategoryMutationRunsStandaloneWithThePublishedPersistenceRunner(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection);

        $categoryId = $service->create(new CreateCategoryCommand('standalone-transaction-category'));

        self::assertNotNull((new PdoCategoryQueryReader($connection, new FixedCategoryClock()))->findById($categoryId));
        self::assertFalse($connection->inTransaction());
    }

    public function testHostRollbackCancelsCategoryMutationAndRemainsTheTransactionOwner(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection);
        $reader = new PdoCategoryQueryReader($connection, new FixedCategoryClock());

        $connection->beginTransaction();
        try {
            $categoryId = $service->create(new CreateCategoryCommand('host-rollback-category'));

            self::assertTrue($connection->inTransaction());
            self::assertSame($categoryId, $reader->findByCode('host-rollback-category')?->id);
            $connection->rollBack();
        } finally {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
        }

        self::assertNull($reader->findByCode('host-rollback-category'));
    }

    public function testHostCommitPersistsCategoryMutationAndRemainsTheTransactionOwner(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection);
        $reader = new PdoCategoryQueryReader($connection, new FixedCategoryClock());

        $connection->beginTransaction();
        try {
            $categoryId = $service->create(new CreateCategoryCommand('host-commit-category'));

            self::assertTrue($connection->inTransaction());
            self::assertSame($categoryId, $reader->findByCode('host-commit-category')?->id);
            $connection->commit();
        } finally {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
        }

        self::assertNotNull($reader->findByCode('host-commit-category'));
    }

    public function testHostCommitAndRollbackControlImageAssignmentDefaultMutation(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection);
        $reader = new PdoCategoryQueryReader($connection, new FixedCategoryClock());
        $categoryId = $service->create(new CreateCategoryCommand('host-default-transaction-category'));
        $firstId = $service->createImageAssignment(
            new CreateCategoryImageAssignmentCommand($categoryId, 1003),
        );
        $secondId = $service->createImageAssignment(
            new CreateCategoryImageAssignmentCommand($categoryId, 1004),
        );

        $connection->beginTransaction();
        try {
            $service->setImageAssignmentDefault(new SetCategoryImageAssignmentDefaultCommand($firstId));
            self::assertTrue($connection->inTransaction());
            $firstAssignment = $reader->findImageAssignmentById($firstId);
            self::assertNotNull($firstAssignment);
            self::assertTrue($firstAssignment->isDefault);
            $connection->rollBack();
        } finally {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
        }

        $firstAssignment = $reader->findImageAssignmentById($firstId);
        self::assertNotNull($firstAssignment);
        self::assertFalse($firstAssignment->isDefault);

        $connection->beginTransaction();
        try {
            $service->setImageAssignmentDefault(new SetCategoryImageAssignmentDefaultCommand($secondId));
            self::assertTrue($connection->inTransaction());
            $secondAssignment = $reader->findImageAssignmentById($secondId);
            self::assertNotNull($secondAssignment);
            self::assertTrue($secondAssignment->isDefault);
            $connection->commit();
        } finally {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
        }

        $firstAssignment = $reader->findImageAssignmentById($firstId);
        $secondAssignment = $reader->findImageAssignmentById($secondId);
        self::assertNotNull($firstAssignment);
        self::assertNotNull($secondAssignment);
        self::assertFalse($firstAssignment->isDefault);
        self::assertTrue($secondAssignment->isDefault);
    }

    public function testCategoryFailureDoesNotCloseTheCallerOwnedTransaction(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection);

        $connection->beginTransaction();
        try {
            $categoryId = $service->create(new CreateCategoryCommand('failure-keeps-outer-transaction'));

            try {
                $service->updateDisplayOrder(new UpdateCategoryDisplayOrderCommand(999999, 1));
                self::fail('Updating a missing Category must fail.');
            } catch (CategoryNotFoundException) {
                self::assertTrue($connection->inTransaction());
            }

            $service->updateStatus(new UpdateCategoryStatusCommand($categoryId, CategoryStatusEnum::INACTIVE));
            $connection->commit();
        } finally {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
        }

        $updated = (new PdoCategoryQueryReader($connection, new FixedCategoryClock()))->findById($categoryId);
        self::assertNotNull($updated);
        self::assertSame(CategoryStatusEnum::INACTIVE, $updated->status);
    }

    public function testAllThreeOrderingMutationsParticipateInTheCallerOwnedTransaction(): void
    {
        $connection = $this->connection();
        $service = $this->service($connection);
        $reader = new PdoCategoryQueryReader($connection, new FixedCategoryClock());

        $firstCategoryId = $service->create(new CreateCategoryCommand('outer-ordering-first'));
        $secondCategoryId = $service->create(new CreateCategoryCommand('outer-ordering-second'));
        $firstAssignmentId = $service->createImageAssignment(
            new CreateCategoryImageAssignmentCommand($firstCategoryId, 1001),
        );
        $secondAssignmentId = $service->createImageAssignment(
            new CreateCategoryImageAssignmentCommand($firstCategoryId, 1002),
        );
        $firstFieldId = $service->createContentField(
            new CreateCategoryContentFieldCommand(
                $firstCategoryId,
                'outer-first-field',
                null,
                null,
                CategoryContentFieldFormatEnum::TEXT,
                'first',
            ),
        );
        $secondFieldId = $service->createContentField(
            new CreateCategoryContentFieldCommand(
                $firstCategoryId,
                'outer-second-field',
                null,
                null,
                CategoryContentFieldFormatEnum::TEXT,
                'second',
            ),
        );

        $firstCategory = $reader->findById($firstCategoryId);
        $secondCategory = $reader->findById($secondCategoryId);
        self::assertNotNull($firstCategory);
        self::assertNotNull($secondCategory);
        self::assertSame(1, $firstCategory->displayOrder);
        self::assertSame(2, $secondCategory->displayOrder);

        $connection->beginTransaction();
        try {
            $service->updateDisplayOrder(new UpdateCategoryDisplayOrderCommand($secondCategoryId, 1));
            self::assertTrue($connection->inTransaction());

            $service->updateImageAssignmentDisplayOrder(
                new UpdateCategoryImageAssignmentDisplayOrderCommand($secondAssignmentId, 1),
            );
            self::assertTrue($connection->inTransaction());

            $service->updateContentFieldDisplayOrder(
                new UpdateCategoryContentFieldDisplayOrderCommand($secondFieldId, 1),
            );
            self::assertTrue($connection->inTransaction());

            $firstCategory = $reader->findById($firstCategoryId);
            $secondCategory = $reader->findById($secondCategoryId);
            self::assertNotNull($firstCategory);
            self::assertNotNull($secondCategory);
            self::assertSame(2, $firstCategory->displayOrder);
            self::assertSame(1, $secondCategory->displayOrder);
            self::assertSame(
                [$secondAssignmentId, $firstAssignmentId],
                $this->idsForScope($connection, 'maa_category_category_image_assignments', $firstCategoryId),
            );
            self::assertSame(
                [$secondFieldId, $firstFieldId],
                $this->idsForScope($connection, 'maa_category_category_content_fields', $firstCategoryId),
            );
            self::assertSame(
                '2026-01-05 00:00:00',
                $this->updatedAtForId($connection, 'maa_category_categories', $secondCategoryId),
            );
            self::assertSame(
                '2026-01-05 00:00:00',
                $this->updatedAtForId($connection, 'maa_category_category_image_assignments', $secondAssignmentId),
            );
            self::assertSame(
                '2026-01-05 00:00:00',
                $this->updatedAtForId($connection, 'maa_category_category_content_fields', $secondFieldId),
            );

            $connection->commit();
        } finally {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
        }

        $secondCategory = $reader->findById($secondCategoryId);
        self::assertNotNull($secondCategory);
        self::assertSame(1, $secondCategory->displayOrder);
    }

    /** @return list<int> */
    private function idsForScope(PDO $connection, string $table, int $categoryId): array
    {
        $statement = $connection->prepare(
            'SELECT `id` FROM `' . $table . '` '
            . 'WHERE `category_id` = :category_id '
            . 'AND `language_code` IS NULL AND `platform` IS NULL '
            . 'AND `deleted_at` IS NULL ORDER BY `display_order`, `id`',
        );
        $statement->execute(['category_id' => $categoryId]);

        $ids = [];
        foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $id) {
            self::assertTrue(is_int($id) || is_string($id));
            $ids[] = (int) $id;
        }

        return $ids;
    }

    private function updatedAtForId(PDO $connection, string $table, int $id): string
    {
        $statement = $connection->prepare(
            'SELECT `updated_at` FROM `' . $table . '` WHERE `id` = :id',
        );
        $statement->execute(['id' => $id]);
        $updatedAt = $statement->fetchColumn();

        if (!is_string($updatedAt)) {
            self::fail('Expected an updated_at value for ' . $table . ' #' . $id . '.');
        }

        return $updatedAt;
    }

    private function service(PDO $connection): CategoryCommandService
    {
        $clock = new FixedCategoryClock('2026-01-05 00:00:00 Africa/Cairo');

        return new CategoryCommandService(
            new PdoCategoryCommandRepository($connection, new ScopedOrderingManager()),
            new PdoCategoryQueryReader($connection, $clock),
            new PdoCategoryContentCommandRepository($connection),
            new PdoCategoryImageAssignmentCommandRepository($connection, new ScopedOrderingManager()),
            new PdoCategoryContentFieldCommandRepository($connection, new ScopedOrderingManager()),
            new PdoTransactionRunner($connection),
            $clock,
        );
    }
}
