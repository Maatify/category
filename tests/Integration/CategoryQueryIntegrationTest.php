<?php

declare(strict_types=1);

namespace Maatify\Category\Tests\Integration;

use Maatify\Category\Command\CreateCategoryCommand;
use Maatify\Category\Command\CreateCategoryTranslationCommand;
use Maatify\Category\Command\SoftDeleteCategoryCommand;
use Maatify\Category\Command\SoftDeleteCategoryTranslationCommand;
use Maatify\Category\Command\UpdateCategoryStatusCommand;
use Maatify\Category\Enum\CategoryStatusEnum;
use Maatify\Category\Infrastructure\Repository\PdoCategoryCommandRepository;
use Maatify\Category\Infrastructure\Repository\PdoCategoryQueryReader;
use Maatify\Category\Infrastructure\Repository\PdoCategoryReadQuery;
use Maatify\Category\Infrastructure\Repository\PdoCategoryTranslationCommandRepository;
use Maatify\Category\Infrastructure\Transaction\PdoCategoryTransaction;
use Maatify\Category\Service\CategoryCommandService;
use Maatify\Category\Service\CategoryQueryService;
use Maatify\Category\Tests\Integration\Support\CategoryMySqlIntegrationTestCase;
use Maatify\Category\Tests\Integration\Support\FixedCategoryClock;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
use PDO;

final class CategoryQueryIntegrationTest extends CategoryMySqlIntegrationTestCase
{
    public function testVisibleIdentityAndRootListExcludeDeletedOrInactiveRowsAndUseDisplayOrderThenId(): void
    {
        $connection = $this->connection();
        $commandService = $this->commandService($connection);
        $firstId = $commandService->create(new CreateCategoryCommand('root-first'));
        $secondId = $commandService->create(new CreateCategoryCommand('root-second'));
        $thirdId = $commandService->create(new CreateCategoryCommand('root-third'));
        $inactiveId = $commandService->create(new CreateCategoryCommand('root-inactive'));
        $deletedId = $commandService->create(new CreateCategoryCommand('root-deleted'));

        $commandService->updateStatus(new UpdateCategoryStatusCommand($inactiveId, CategoryStatusEnum::INACTIVE));
        $commandService->softDelete(new SoftDeleteCategoryCommand($deletedId));
        $this->setDisplayOrder($connection, $firstId, 2);
        $this->setDisplayOrder($connection, $secondId, 1);
        $this->setDisplayOrder($connection, $thirdId, 1);
        $this->setDisplayOrder($connection, $inactiveId, 3);
        $this->setDisplayOrder($connection, $deletedId, 3);

        $reader = new PdoCategoryReadQuery($connection);
        $queryService = new CategoryQueryService($reader);

        self::assertSame($secondId, $queryService->getById($secondId)->id);
        self::assertNull($reader->findVisibleById($inactiveId));
        self::assertNull($reader->findVisibleById($deletedId));
        self::assertSame([$secondId, $thirdId, $firstId], $this->categoryIds($queryService->listRootCategories()));
    }

    public function testChildrenAreOrderedAndHiddenWhenAnyAncestorIsInactive(): void
    {
        $connection = $this->connection();
        $commandService = $this->commandService($connection);
        $activeRootId = $commandService->create(new CreateCategoryCommand('active-root'));
        $inactiveRootId = $commandService->create(new CreateCategoryCommand('inactive-root'));
        $firstChildId = $commandService->create(new CreateCategoryCommand('first-child', $activeRootId));
        $secondChildId = $commandService->create(new CreateCategoryCommand('second-child', $activeRootId));
        $inactiveChildId = $commandService->create(new CreateCategoryCommand('inactive-child', $activeRootId));
        $deletedChildId = $commandService->create(new CreateCategoryCommand('deleted-child', $activeRootId));
        $grandchildId = $commandService->create(new CreateCategoryCommand('grandchild', $firstChildId));

        $commandService->updateStatus(new UpdateCategoryStatusCommand($inactiveRootId, CategoryStatusEnum::INACTIVE));
        $commandService->updateStatus(new UpdateCategoryStatusCommand($inactiveChildId, CategoryStatusEnum::INACTIVE));
        $commandService->softDelete(new SoftDeleteCategoryCommand($deletedChildId));
        $this->setDisplayOrder($connection, $firstChildId, 1);
        $this->setDisplayOrder($connection, $secondChildId, 1);
        $this->setDisplayOrder($connection, $inactiveChildId, 3);
        $this->setDisplayOrder($connection, $deletedChildId, 3);

        $reader = new PdoCategoryReadQuery($connection);
        $queryService = new CategoryQueryService($reader);

        self::assertSame(
            [$firstChildId, $secondChildId],
            $this->categoryIds($queryService->listChildren($activeRootId)),
        );
        self::assertSame([], $this->categoryIds($queryService->listChildren($inactiveRootId)));
        self::assertSame($grandchildId, $queryService->getById($grandchildId)->id);

        $commandService->updateStatus(new UpdateCategoryStatusCommand($activeRootId, CategoryStatusEnum::INACTIVE));

        self::assertSame([], $this->categoryIds($queryService->listChildren($activeRootId)));
        self::assertNull($reader->findVisibleById($grandchildId));
    }

    public function testTranslationsExcludeSoftDeletedRowsAndInvisibleCategoryPaths(): void
    {
        $connection = $this->connection();
        $commandService = $this->commandService($connection);
        $visibleId = $commandService->create(new CreateCategoryCommand('translated-category'));
        $inactiveId = $commandService->create(new CreateCategoryCommand('translated-inactive'));
        $deletedId = $commandService->create(new CreateCategoryCommand('translated-deleted'));

        $commandService->createTranslation(
            new CreateCategoryTranslationCommand($visibleId, 'en-US', 'Shirts', null),
        );
        $commandService->createTranslation(
            new CreateCategoryTranslationCommand($visibleId, 'ar-EG', 'قمصان', 'وصف'),
        );
        $deletedVisibleTranslationId = $commandService->createTranslation(
            new CreateCategoryTranslationCommand($visibleId, 'fr-FR', 'Chemises', null),
        );
        $commandService->createTranslation(
            new CreateCategoryTranslationCommand($inactiveId, 'en-US', 'Inactive', null),
        );
        $deletedCategoryTranslationId = $commandService->createTranslation(
            new CreateCategoryTranslationCommand($deletedId, 'en-US', 'Deleted', null),
        );

        $commandService->updateStatus(new UpdateCategoryStatusCommand($inactiveId, CategoryStatusEnum::INACTIVE));
        $commandService->softDelete(new SoftDeleteCategoryCommand($deletedId));
        $commandService->softDeleteTranslation(
            new SoftDeleteCategoryTranslationCommand($deletedVisibleTranslationId),
        );
        $commandService->softDeleteTranslation(
            new SoftDeleteCategoryTranslationCommand($deletedCategoryTranslationId),
        );

        $queryService = new CategoryQueryService(new PdoCategoryReadQuery($connection));
        $visibleTranslations = $queryService->listTranslations($visibleId);
        $languages = [];
        foreach ($visibleTranslations as $translation) {
            $languages[] = $translation->languageCode;
        }

        self::assertSame(['ar-EG', 'en-US'], $languages);
        self::assertTrue($queryService->listTranslations($inactiveId)->isEmpty());
        self::assertTrue($queryService->listTranslations($deletedId)->isEmpty());
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
