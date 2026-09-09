<?php

declare(strict_types=1);

namespace Maatify\Category\Contract;

use Maatify\Category\DTO\CategoryCollectionDTO;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CategoryContentCollectionDTO;
use Maatify\Category\DTO\CategoryImageAssignmentCollectionDTO;
use Maatify\Category\DTO\CategoryImageAssignmentScopeDTO;
use Maatify\Category\DTO\CategoryContentFieldCollectionDTO;
use Maatify\Category\DTO\CategoryContentFieldScopeDTO;
use Maatify\Category\DTO\CategoryVisibleListCriteriaDTO;

/** Public application contract for visible Category reads and lists. */
interface CategoryQueryServiceInterface
{
    /**
     * @throws \Maatify\Category\Exception\CategoryNotFoundException
     */
    public function getById(int $categoryId): CategoryDTO;

    public function listRootCategories(
        CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO(),
    ): CategoryCollectionDTO;

    public function listChildren(
        int $parentId,
        CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO(),
    ): CategoryCollectionDTO;

    public function listContents(
        int $categoryId,
        CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO(),
    ): CategoryContentCollectionDTO;

    public function listImageAssignments(
        int $categoryId,
        CategoryImageAssignmentScopeDTO $scope,
        CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO(),
    ): CategoryImageAssignmentCollectionDTO;

    public function listContentFields(
        int $categoryId,
        CategoryContentFieldScopeDTO $scope,
        CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO(),
    ): CategoryContentFieldCollectionDTO;
}
