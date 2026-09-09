<?php

declare(strict_types=1);

namespace Maatify\Category\Service;

use Maatify\Category\Contract\CategoryReadQueryInterface;
use Maatify\Category\Contract\CategoryQueryServiceInterface;
use Maatify\Category\DTO\CategoryCollectionDTO;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CategoryIdDTO;
use Maatify\Category\DTO\CategoryContentCollectionDTO;
use Maatify\Category\DTO\CategoryVisibleListCriteriaDTO;
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
}
