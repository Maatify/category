<?php

declare(strict_types=1);

namespace Maatify\Category\Tests\Integration;

use Maatify\Category\Api\CategoryFactory;
use Maatify\Category\Api\Domain\CategoryApiInterface;
use Maatify\Category\Content\Api\Contract\ContentApiInterface;
use Maatify\Category\Lifecycle\Command\CreateCategoryCommand;
use Maatify\Category\Content\Mutation\Command\CreateCategoryContentCommand;
use Maatify\Category\Lifecycle\Command\SoftDeleteCategoryCommand;
use Maatify\Category\Content\Mutation\Command\SoftDeleteCategoryContentCommand;
use Maatify\Category\Lifecycle\Command\UpdateCategoryStatusCommand;
use Maatify\Category\Query\DTO\CategoryListCriteriaDTO;
use Maatify\Category\Content\Query\DTO\CategoryContentListCriteriaDTO;
use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;
use Maatify\Category\Lifecycle\Enum\CategoryStatusEnum;
use Maatify\Category\Exception\CategoryNotFoundException;
use Maatify\Category\Content\Exception\CategoryContentNotFoundException;
use Maatify\Category\Tests\Integration\Support\CategoryMySqlIntegrationTestCase;
use Maatify\Category\Tests\Integration\Support\FixedCategoryClock;
use PDO;

final class CategoryManagementQueryIntegrationTest extends CategoryMySqlIntegrationTestCase
{
    public function testCategoryManagementReadsFilterStateAndStatusWithoutConsumerAncestorVisibility(): void
    {
        $connection = $this->connection();
        $commandService = $this->commandService($connection);
        $contentService = $this->contentService($connection);
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

        $service = $this->categoryService($connection);

        self::assertSame(
            [$activeId, $inactiveParentId, $childId],
            $this->categoryIds($service->listForManagement(new CategoryListCriteriaDTO())),
        );
        self::assertSame(
            [$inactiveParentId],
            $this->categoryIds($service->listForManagement(new CategoryListCriteriaDTO(
                status: CategoryStatusEnum::INACTIVE,
            ))),
        );
        self::assertSame(
            [$deletedId],
            $this->categoryIds($service->listForManagement(new CategoryListCriteriaDTO(
                deletedState: CategoryDeletedStateEnum::DELETED_ONLY,
            ))),
        );
        self::assertSame(
            [$activeId, $inactiveParentId, $childId, $deletedId],
            $this->categoryIds($service->listForManagement(new CategoryListCriteriaDTO(
                deletedState: CategoryDeletedStateEnum::INCLUDE_DELETED,
                maxResults: 4,
            ))),
        );

        self::assertSame(
            $inactiveParentId,
            $service->getByIdForManagement($inactiveParentId)->id,
        );
        self::assertSame($childId, $service->getByIdForManagement($childId)->id);
        self::assertSame(
            $deletedId,
            $service->getByIdForManagement($deletedId, CategoryDeletedStateEnum::DELETED_ONLY)->id,
        );
        $this->expectException(CategoryNotFoundException::class);
        $service->getByIdForManagement($deletedId);
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

        $service = $this->categoryService($connection);
        $criteria = new CategoryListCriteriaDTO(maxResults: 2);

        self::assertSame([$parentId], $this->categoryIds($service->listRootCategoriesForManagement($criteria)));
        self::assertSame(
            [$firstId, $secondId],
            $this->categoryIds($service->listChildrenForManagement($parentId, $criteria)),
        );
    }

    public function testContentManagementReadsFilterByCategoryAndDeletedState(): void
    {
        $connection = $this->connection();
        $commandService = $this->commandService($connection);
        $contentService = $this->contentService($connection);
        $categoryId = $commandService->create(new CreateCategoryCommand('management-contents'));
        $otherCategoryId = $commandService->create(new CreateCategoryCommand('management-other-contents'));
        $englishId = $contentService->create(
            new CreateCategoryContentCommand($categoryId, 'en-US', 'Shirts', null),
        );
        $arabicId = $contentService->create(
            new CreateCategoryContentCommand($categoryId, 'ar-EG', 'قمصان', null),
        );
        $deletedId = $contentService->create(
            new CreateCategoryContentCommand($categoryId, 'fr-FR', 'Chemises', null),
        );
        $contentService->create(
            new CreateCategoryContentCommand($otherCategoryId, 'en-US', 'Other', null),
        );
        $contentService->softDelete(new SoftDeleteCategoryContentCommand($deletedId));

        $service = $this->contentService($connection);
        $categoryCriteria = new CategoryContentListCriteriaDTO(categoryId: $categoryId);
        $activeIds = $this->contentIds($service->listForManagement($categoryCriteria));

        self::assertSame([$arabicId, $englishId], $activeIds);
        self::assertSame(
            [$deletedId],
            $this->contentIds($service->listForManagement(new CategoryContentListCriteriaDTO(
                categoryId: $categoryId,
                deletedState: CategoryDeletedStateEnum::DELETED_ONLY,
            ))),
        );
        self::assertSame(
            [$arabicId, $englishId, $deletedId],
            $this->contentIds($service->listForManagement(new CategoryContentListCriteriaDTO(
                categoryId: $categoryId,
                deletedState: CategoryDeletedStateEnum::INCLUDE_DELETED,
            ))),
        );
        self::assertSame($deletedId, $service->getByIdForManagement(
            $deletedId,
            CategoryDeletedStateEnum::DELETED_ONLY,
        )->id);

        $this->expectException(CategoryContentNotFoundException::class);
        $service->getByIdForManagement($deletedId);
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

    private function commandService(PDO $connection): CategoryApiInterface
    {
        return $this->categoryService($connection);
    }

    private function categoryService(PDO $connection): CategoryApiInterface
    {
        return CategoryFactory::create(
            $connection,
            new FixedCategoryClock('2026-01-01 00:00:00 Africa/Cairo'),
        )->categories();
    }

    private function contentService(PDO $connection): ContentApiInterface
    {
        return CategoryFactory::create(
            $connection,
            new FixedCategoryClock('2026-01-01 00:00:00 Africa/Cairo'),
        )->contents();
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
