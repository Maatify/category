<?php

declare(strict_types=1);

namespace Maatify\Category\Tests\Integration;

use Maatify\Category\Lifecycle\Command\CreateCategoryCommand;
use Maatify\Category\Content\Mutation\Command\CreateCategoryContentCommand;
use Maatify\Category\Lifecycle\Command\SoftDeleteCategoryCommand;
use Maatify\Category\Content\Mutation\Command\SoftDeleteCategoryContentCommand;
use Maatify\Category\Lifecycle\Command\UpdateCategoryStatusCommand;
use Maatify\Category\Query\DTO\CategoryListCriteriaDTO;
use Maatify\Category\Content\Query\DTO\CategoryContentListCriteriaDTO;
use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;
use Maatify\Category\Lifecycle\Enum\CategoryStatusEnum;
use Maatify\Category\Lifecycle\Exception\CategoryNotFoundException;
use Maatify\Category\Content\Mutation\Exception\CategoryContentNotFoundException;
use Maatify\Category\Infrastructure\PdoCategoryCommandRepository;
use Maatify\Category\Query\Infrastructure\PdoCategoryManagementReadQuery;
use Maatify\Category\Query\Infrastructure\PdoCategoryQueryReader;
use Maatify\Category\Content\Infrastructure\PdoCategoryContentCommandRepository;
use Maatify\Category\ImageAssignment\Infrastructure\PdoCategoryImageAssignmentCommandRepository;
use Maatify\Category\ContentField\Infrastructure\PdoCategoryContentFieldCommandRepository;
use Maatify\Category\Api\Service\CategoryCommandService;
use Maatify\Category\Api\Service\CategoryManagementQueryService;
use Maatify\Category\Tests\Integration\Support\CategoryMySqlIntegrationTestCase;
use Maatify\Category\Tests\Integration\Support\FixedCategoryClock;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
use Maatify\Persistence\Pdo\Transaction\PdoTransactionRunner;
use PDO;

final class CategoryManagementQueryIntegrationTest extends CategoryMySqlIntegrationTestCase
{
    public function testCategoryManagementReadsFilterStateAndStatusWithoutConsumerAncestorVisibility(): void
    {
        $connection = $this->connection();
        $commandService = $this->commandService($connection);
        $activeId = $commandService->create(new CreateCategoryCommand('management-active'));
        $inactiveParentId = $commandService->create(new CreateCategoryCommand('management-inactive-parent'));
        $childId = $commandService->create(new CreateCategoryCommand('management-child', $inactiveParentId));
        $deletedId = $commandService->create(new CreateCategoryCommand('management-deleted'));

        $commandService->updateStatus(new UpdateCategoryStatusCommand($inactiveParentId, CategoryStatusEnum::INACTIVE));
        $commandService->softDelete(new SoftDeleteCategoryCommand($deletedId));
        $this->setDisplayOrder($connection, $activeId, 1);
        $this->setDisplayOrder($connection, $inactiveParentId, 1);
        $this->setDisplayOrder($connection, $childId, 2);
        $this->setDisplayOrder($connection, $deletedId, 3);

        $service = new CategoryManagementQueryService(new PdoCategoryManagementReadQuery($connection, new FixedCategoryClock()));

        self::assertSame(
            [$activeId, $inactiveParentId, $childId],
            $this->categoryIds($service->listCategories(new CategoryListCriteriaDTO())),
        );
        self::assertSame(
            [$inactiveParentId],
            $this->categoryIds($service->listCategories(new CategoryListCriteriaDTO(
                status: CategoryStatusEnum::INACTIVE,
            ))),
        );
        self::assertSame(
            [$deletedId],
            $this->categoryIds($service->listCategories(new CategoryListCriteriaDTO(
                deletedState: CategoryDeletedStateEnum::DELETED_ONLY,
            ))),
        );
        self::assertSame(
            [$activeId, $inactiveParentId, $childId, $deletedId],
            $this->categoryIds($service->listCategories(new CategoryListCriteriaDTO(
                deletedState: CategoryDeletedStateEnum::INCLUDE_DELETED,
                maxResults: 4,
            ))),
        );

        self::assertSame(
            $inactiveParentId,
            $service->getById($inactiveParentId)->id,
        );
        self::assertSame($childId, $service->getById($childId)->id);
        self::assertSame(
            $deletedId,
            $service->getById($deletedId, CategoryDeletedStateEnum::DELETED_ONLY)->id,
        );
        $this->expectException(CategoryNotFoundException::class);
        $service->getById($deletedId);
    }

    public function testRootAndChildListsAreBoundedAndOrderedByDisplayOrderThenId(): void
    {
        $connection = $this->connection();
        $commandService = $this->commandService($connection);
        $parentId = $commandService->create(new CreateCategoryCommand('management-list-parent'));
        $firstId = $commandService->create(new CreateCategoryCommand('management-list-first', $parentId));
        $secondId = $commandService->create(new CreateCategoryCommand('management-list-second', $parentId));
        $thirdId = $commandService->create(new CreateCategoryCommand('management-list-third', $parentId));
        $this->setDisplayOrder($connection, $firstId, 1);
        $this->setDisplayOrder($connection, $secondId, 1);
        $this->setDisplayOrder($connection, $thirdId, 2);

        $service = new CategoryManagementQueryService(new PdoCategoryManagementReadQuery($connection, new FixedCategoryClock()));
        $criteria = new CategoryListCriteriaDTO(maxResults: 2);

        self::assertSame([$parentId], $this->categoryIds($service->listRootCategories($criteria)));
        self::assertSame(
            [$firstId, $secondId],
            $this->categoryIds($service->listChildren($parentId, $criteria)),
        );
    }

    public function testContentManagementReadsFilterByCategoryAndDeletedState(): void
    {
        $connection = $this->connection();
        $commandService = $this->commandService($connection);
        $categoryId = $commandService->create(new CreateCategoryCommand('management-contents'));
        $otherCategoryId = $commandService->create(new CreateCategoryCommand('management-other-contents'));
        $englishId = $commandService->createContent(
            new CreateCategoryContentCommand($categoryId, 'en-US', 'Shirts', null),
        );
        $arabicId = $commandService->createContent(
            new CreateCategoryContentCommand($categoryId, 'ar-EG', 'قمصان', null),
        );
        $deletedId = $commandService->createContent(
            new CreateCategoryContentCommand($categoryId, 'fr-FR', 'Chemises', null),
        );
        $commandService->createContent(
            new CreateCategoryContentCommand($otherCategoryId, 'en-US', 'Other', null),
        );
        $commandService->softDeleteContent(new SoftDeleteCategoryContentCommand($deletedId));

        $service = new CategoryManagementQueryService(new PdoCategoryManagementReadQuery($connection, new FixedCategoryClock()));
        $categoryCriteria = new CategoryContentListCriteriaDTO(categoryId: $categoryId);
        $activeIds = $this->contentIds($service->listContents($categoryCriteria));

        self::assertSame([$arabicId, $englishId], $activeIds);
        self::assertSame(
            [$deletedId],
            $this->contentIds($service->listContents(new CategoryContentListCriteriaDTO(
                categoryId: $categoryId,
                deletedState: CategoryDeletedStateEnum::DELETED_ONLY,
            ))),
        );
        self::assertSame(
            [$arabicId, $englishId, $deletedId],
            $this->contentIds($service->listContents(new CategoryContentListCriteriaDTO(
                categoryId: $categoryId,
                deletedState: CategoryDeletedStateEnum::INCLUDE_DELETED,
            ))),
        );
        self::assertSame($deletedId, $service->getContentById(
            $deletedId,
            CategoryDeletedStateEnum::DELETED_ONLY,
        )->id);

        $this->expectException(CategoryContentNotFoundException::class);
        $service->getContentById($deletedId);
    }

    /** @return list<int> */
    private function categoryIds(\Maatify\Category\Query\DTO\CategoryCollectionDTO $categories): array
    {
        $ids = [];
        foreach ($categories as $category) {
            $ids[] = $category->id;
        }

        return $ids;
    }

    /** @return list<int> */
    private function contentIds(\Maatify\Category\Content\Query\DTO\CategoryContentCollectionDTO $contents): array
    {
        $ids = [];
        foreach ($contents as $content) {
            $ids[] = $content->id;
        }

        return $ids;
    }

    private function commandService(PDO $connection): CategoryCommandService
    {
        return new CategoryCommandService(
            new PdoCategoryCommandRepository($connection, new ScopedOrderingManager()),
            new PdoCategoryQueryReader($connection, new FixedCategoryClock()),
            new PdoCategoryContentCommandRepository($connection),
            new PdoCategoryImageAssignmentCommandRepository($connection, new ScopedOrderingManager()),
            new PdoCategoryContentFieldCommandRepository($connection, new ScopedOrderingManager()),
            new PdoTransactionRunner($connection),
            new FixedCategoryClock('2026-01-01 00:00:00 Africa/Cairo'),
        );
    }

    private function setDisplayOrder(PDO $connection, int $categoryId, int $displayOrder): void
    {
        $statement = $connection->prepare(
            'UPDATE `maa_category_categories` '
            . 'SET `display_order` = :display_order WHERE `id` = :category_id',
        );
        $statement->execute([
            'display_order' => $displayOrder,
            'category_id' => $categoryId,
        ]);
    }
}
