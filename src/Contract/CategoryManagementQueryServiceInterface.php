<?php

declare(strict_types=1);

namespace Maatify\Category\Contract;

use Maatify\Category\DTO\CategoryCollectionDTO;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CategoryListCriteriaDTO;
use Maatify\Category\DTO\CategoryContentCollectionDTO;
use Maatify\Category\DTO\CategoryContentDTO;
use Maatify\Category\DTO\CategoryContentListCriteriaDTO;
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

    /** @throws \Maatify\Category\Exception\CategoryContentNotFoundException */
    public function getContentById(
        int $contentId,
        CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
    ): CategoryContentDTO;

    public function listContents(
        CategoryContentListCriteriaDTO $criteria,
    ): CategoryContentCollectionDTO;
}
