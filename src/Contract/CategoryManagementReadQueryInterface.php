<?php

declare(strict_types=1);

namespace Maatify\Category\Contract;

use Maatify\Category\DTO\CategoryCollectionDTO;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CategoryListCriteriaDTO;
use Maatify\Category\DTO\CategoryTranslationCollectionDTO;
use Maatify\Category\DTO\CategoryTranslationDTO;
use Maatify\Category\DTO\CategoryTranslationListCriteriaDTO;
use Maatify\Category\Enum\CategoryDeletedStateEnum;

/** Dedicated management read port, separate from consumer visibility reads. */
interface CategoryManagementReadQueryInterface
{
    /** Finds a Category using the requested explicit soft-deletion state. */
    public function findById(int $categoryId, CategoryDeletedStateEnum $deletedState): ?CategoryDTO;

    /** Lists Categories in display-order/id order, bounded by the criteria. */
    public function listCategories(CategoryListCriteriaDTO $criteria): CategoryCollectionDTO;

    /** Lists root Categories in display-order/id order, bounded by the criteria. */
    public function listRootCategories(CategoryListCriteriaDTO $criteria): CategoryCollectionDTO;

    /** Lists direct children in display-order/id order, bounded by the criteria. */
    public function listChildren(int $parentId, CategoryListCriteriaDTO $criteria): CategoryCollectionDTO;

    /** Finds a Translation using the requested explicit soft-deletion state. */
    public function findTranslationById(
        int $translationId,
        CategoryDeletedStateEnum $deletedState,
    ): ?CategoryTranslationDTO;

    /** Lists Translations in language-code/id order, bounded by the criteria. */
    public function listTranslations(
        CategoryTranslationListCriteriaDTO $criteria,
    ): CategoryTranslationCollectionDTO;
}
