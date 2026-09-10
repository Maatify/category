<?php

declare(strict_types=1);

namespace Maatify\Category\Api\Contract;

use Maatify\Category\Query\DTO\CategoryCollectionDTO;
use Maatify\Category\Query\DTO\CategoryDTO;
use Maatify\Category\Content\Query\DTO\CategoryContentCollectionDTO;
use Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentCollectionDTO;
use Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentScopeDTO;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldCollectionDTO;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldScopeDTO;
use Maatify\Category\Query\DTO\CategoryVisibleListCriteriaDTO;

/** Public application contract for visible Category reads and lists. */
interface CategoryQueryServiceInterface
{
    /**
     * @throws \Maatify\Category\Lifecycle\Exception\CategoryNotFoundException
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
