<?php

declare(strict_types=1);

namespace Maatify\Category\Api\Service;

use Maatify\Category\Query\Contract\CategoryReadQueryInterface;
use Maatify\Category\Api\Contract\CategoryQueryServiceInterface;
use Maatify\Category\Query\DTO\CategoryCollectionDTO;
use Maatify\Category\Query\DTO\CategoryDTO;
use Maatify\Category\Common\DTO\CategoryIdDTO;
use Maatify\Category\Content\Query\DTO\CategoryContentCollectionDTO;
use Maatify\Category\Query\DTO\CategoryVisibleListCriteriaDTO;
use Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentCollectionDTO;
use Maatify\Category\ImageAssignment\CategoryImageAssignmentScopeDTO;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldCollectionDTO;
use Maatify\Category\ContentField\CategoryContentFieldScopeDTO;
use Maatify\Category\Exception\CategoryNotFoundException;

/** Coordinates the public visible Category read contract. */
final readonly class CategoryQueryService implements CategoryQueryServiceInterface
{
    public function __construct(private CategoryReadQueryInterface $reader) {}

    public function getById(int $categoryId): CategoryDTO
    {
        $id = (new CategoryIdDTO($categoryId, 'categoryId'))->value;
        $category = $this->reader->findVisibleById($id);

        if ($category === null) {
            throw CategoryNotFoundException::withId($id);
        }

        return $category;
    }

    public function listRootCategories(
        CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO(),
    ): CategoryCollectionDTO
    {
        return $this->reader->listVisibleRootCategories($criteria);
    }

    public function listChildren(
        int $parentId,
        CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO(),
    ): CategoryCollectionDTO
    {
        $id = (new CategoryIdDTO($parentId, 'parentId'))->value;

        return $this->reader->listVisibleChildren($id, $criteria);
    }

    public function listContents(
        int $categoryId,
        CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO(),
    ): CategoryContentCollectionDTO
    {
        $id = (new CategoryIdDTO($categoryId, 'categoryId'))->value;

        return $this->reader->listVisibleContents($id, $criteria);
    }

    public function listImageAssignments(
        int $categoryId,
        CategoryImageAssignmentScopeDTO $scope,
        CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO(),
    ): CategoryImageAssignmentCollectionDTO {
        $id = (new CategoryIdDTO($categoryId, 'categoryId'))->value;

        return $this->reader->listVisibleImageAssignments($id, $scope, $criteria);
    }

    public function listContentFields(
        int $categoryId,
        CategoryContentFieldScopeDTO $scope,
        CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO(),
    ): CategoryContentFieldCollectionDTO {
        $id = (new CategoryIdDTO($categoryId, 'categoryId'))->value;

        return $this->reader->listVisibleContentFields($id, $scope, $criteria);
    }
}
