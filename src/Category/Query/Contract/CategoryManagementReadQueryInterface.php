<?php

declare(strict_types=1);

namespace Maatify\Category\Query\Contract;

use Maatify\Category\Query\DTO\CategoryCollectionDTO;
use Maatify\Category\Query\DTO\CategoryDTO;
use Maatify\Category\Query\DTO\CategoryListCriteriaDTO;
use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;

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
}
