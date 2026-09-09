<?php

declare(strict_types=1);

namespace Maatify\Category\Service;

use Maatify\Category\Contract\CategoryManagementQueryServiceInterface;
use Maatify\Category\Contract\CategoryManagementReadQueryInterface;
use Maatify\Category\DTO\CategoryCollectionDTO;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CategoryIdDTO;
use Maatify\Category\DTO\CategoryListCriteriaDTO;
use Maatify\Category\DTO\CategoryContentCollectionDTO;
use Maatify\Category\DTO\CategoryContentDTO;
use Maatify\Category\DTO\CategoryContentListCriteriaDTO;
use Maatify\Category\Enum\CategoryDeletedStateEnum;
use Maatify\Category\Exception\CategoryNotFoundException;
use Maatify\Category\Exception\CategoryContentNotFoundException;

/** Coordinates the public management Category read contract. */
final readonly class CategoryManagementQueryService implements CategoryManagementQueryServiceInterface
{
    public function __construct(private CategoryManagementReadQueryInterface $reader) {}

    public function getById(
        int $categoryId,
        CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
    ): CategoryDTO {
        $id = (new CategoryIdDTO($categoryId, 'categoryId'))->value;
        $category = $this->reader->findById($id, $deletedState);

        if ($category === null) {
            throw CategoryNotFoundException::withId($id);
        }

        return $category;
    }

    public function listCategories(CategoryListCriteriaDTO $criteria): CategoryCollectionDTO
    {
        return $this->reader->listCategories($criteria);
    }

    public function listRootCategories(CategoryListCriteriaDTO $criteria): CategoryCollectionDTO
    {
        return $this->reader->listRootCategories($criteria);
    }

    public function listChildren(int $parentId, CategoryListCriteriaDTO $criteria): CategoryCollectionDTO
    {
        $id = (new CategoryIdDTO($parentId, 'parentId'))->value;

        return $this->reader->listChildren($id, $criteria);
    }

    public function getContentById(
        int $contentId,
        CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
    ): CategoryContentDTO {
        $id = (new CategoryIdDTO($contentId, 'contentId'))->value;
        $content = $this->reader->findContentById($id, $deletedState);

        if ($content === null) {
            throw CategoryContentNotFoundException::withId($id);
        }

        return $content;
    }

    public function listContents(
        CategoryContentListCriteriaDTO $criteria,
    ): CategoryContentCollectionDTO {
        return $this->reader->listContents($criteria);
    }
}
