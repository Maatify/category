<?php

declare(strict_types=1);

namespace Maatify\Category\Tests\Integration;

use Maatify\Category\Command\CreateCategoryCommand;
use Maatify\Category\Command\CreateCategoryTranslationCommand;
use Maatify\Category\Command\SoftDeleteCategoryCommand;
use Maatify\Category\Command\SoftDeleteCategoryTranslationCommand;
use Maatify\Category\Command\UpdateCategoryStatusCommand;
use Maatify\Category\DTO\CategoryListCriteriaDTO;
use Maatify\Category\DTO\CategoryTranslationListCriteriaDTO;
use Maatify\Category\Enum\CategoryDeletedStateEnum;
use Maatify\Category\Enum\CategoryStatusEnum;
use Maatify\Category\Exception\CategoryNotFoundException;
use Maatify\Category\Exception\CategoryTranslationNotFoundException;
use Maatify\Category\Infrastructure\Repository\PdoCategoryCommandRepository;
use Maatify\Category\Infrastructure\Repository\PdoCategoryManagementReadQuery;
use Maatify\Category\Infrastructure\Repository\PdoCategoryQueryReader;
use Maatify\Category\Infrastructure\Repository\PdoCategoryTranslationCommandRepository;
use Maatify\Category\Infrastructure\Transaction\PdoCategoryTransaction;
use Maatify\Category\Service\CategoryCommandService;
use Maatify\Category\Service\CategoryManagementQueryService;
use Maatify\Category\Tests\Integration\Support\CategoryMySqlIntegrationTestCase;
use Maatify\Category\Tests\Integration\Support\FixedCategoryClock;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
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

        $service = new CategoryManagementQueryService(new PdoCategoryManagementReadQuery($connection));

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

        $service = new CategoryManagementQueryService(new PdoCategoryManagementReadQuery($connection));
        $criteria = new CategoryListCriteriaDTO(maxResults: 2);

        self::assertSame([$parentId], $this->categoryIds($service->listRootCategories($criteria)));
        self::assertSame(
            [$firstId, $secondId],
            $this->categoryIds($service->listChildren($parentId, $criteria)),
        );
    }

    public function testTranslationManagementReadsFilterByCategoryAndDeletedState(): void
    {
        $connection = $this->connection();
        $commandService = $this->commandService($connection);
        $categoryId = $commandService->create(new CreateCategoryCommand('management-translations'));
        $otherCategoryId = $commandService->create(new CreateCategoryCommand('management-other-translations'));
        $englishId = $commandService->createTranslation(
            new CreateCategoryTranslationCommand($categoryId, 'en-US', 'Shirts', null),
        );
        $arabicId = $commandService->createTranslation(
            new CreateCategoryTranslationCommand($categoryId, 'ar-EG', 'قمصان', null),
        );
        $deletedId = $commandService->createTranslation(
            new CreateCategoryTranslationCommand($categoryId, 'fr-FR', 'Chemises', null),
        );
        $commandService->createTranslation(
            new CreateCategoryTranslationCommand($otherCategoryId, 'en-US', 'Other', null),
        );
        $commandService->softDeleteTranslation(new SoftDeleteCategoryTranslationCommand($deletedId));

        $service = new CategoryManagementQueryService(new PdoCategoryManagementReadQuery($connection));
        $categoryCriteria = new CategoryTranslationListCriteriaDTO(categoryId: $categoryId);
        $activeIds = $this->translationIds($service->listTranslations($categoryCriteria));

        self::assertSame([$arabicId, $englishId], $activeIds);
        self::assertSame(
            [$deletedId],
            $this->translationIds($service->listTranslations(new CategoryTranslationListCriteriaDTO(
                categoryId: $categoryId,
                deletedState: CategoryDeletedStateEnum::DELETED_ONLY,
            ))),
        );
        self::assertSame(
            [$arabicId, $englishId, $deletedId],
            $this->translationIds($service->listTranslations(new CategoryTranslationListCriteriaDTO(
                categoryId: $categoryId,
                deletedState: CategoryDeletedStateEnum::INCLUDE_DELETED,
            ))),
        );
        self::assertSame($deletedId, $service->getTranslationById(
            $deletedId,
            CategoryDeletedStateEnum::DELETED_ONLY,
        )->id);

        $this->expectException(CategoryTranslationNotFoundException::class);
        $service->getTranslationById($deletedId);
    }

    /** @return list<int> */
    private function categoryIds(\Maatify\Category\DTO\CategoryCollectionDTO $categories): array
    {
        $ids = [];
        foreach ($categories as $category) {
            $ids[] = $category->id;
        }

        return $ids;
    }

    /** @return list<int> */
    private function translationIds(\Maatify\Category\DTO\CategoryTranslationCollectionDTO $translations): array
    {
        $ids = [];
        foreach ($translations as $translation) {
            $ids[] = $translation->id;
        }

        return $ids;
    }

    private function commandService(PDO $connection): CategoryCommandService
    {
        return new CategoryCommandService(
            new PdoCategoryCommandRepository($connection, new ScopedOrderingManager()),
            new PdoCategoryQueryReader($connection),
            new PdoCategoryTranslationCommandRepository($connection),
            new PdoCategoryTransaction($connection),
            new FixedCategoryClock('2026-01-01 00:00:00 UTC'),
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
