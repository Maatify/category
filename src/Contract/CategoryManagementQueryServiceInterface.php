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

/** Public application contract for management Category reads. */
interface CategoryManagementQueryServiceInterface
{
    /** @throws \Maatify\Category\Exception\CategoryNotFoundException */
    public function getById(
        int $categoryId,
        CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
    ): CategoryDTO;

    public function listCategories(CategoryListCriteriaDTO $criteria): CategoryCollectionDTO;

    public function listRootCategories(CategoryListCriteriaDTO $criteria): CategoryCollectionDTO;

    public function listChildren(int $parentId, CategoryListCriteriaDTO $criteria): CategoryCollectionDTO;

    /** @throws \Maatify\Category\Exception\CategoryTranslationNotFoundException */
    public function getTranslationById(
        int $translationId,
        CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
    ): CategoryTranslationDTO;

    public function listTranslations(
        CategoryTranslationListCriteriaDTO $criteria,
    ): CategoryTranslationCollectionDTO;
}
