<?php

declare(strict_types=1);

namespace Maatify\Category\Service;

use Maatify\Category\Contract\CategoryReadQueryInterface;
use Maatify\Category\Contract\CategoryQueryServiceInterface;
use Maatify\Category\DTO\CategoryCollectionDTO;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CategoryIdDTO;
use Maatify\Category\DTO\CategoryTranslationCollectionDTO;
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

    public function listRootCategories(): CategoryCollectionDTO
    {
        return $this->reader->listVisibleRootCategories();
    }

    public function listChildren(int $parentId): CategoryCollectionDTO
    {
        $id = (new CategoryIdDTO($parentId, 'parentId'))->value;

        return $this->reader->listVisibleChildren($id);
    }

    public function listTranslations(int $categoryId): CategoryTranslationCollectionDTO
    {
        $id = (new CategoryIdDTO($categoryId, 'categoryId'))->value;

        return $this->reader->listVisibleTranslations($id);
    }
}
