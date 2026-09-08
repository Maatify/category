<?php

declare(strict_types=1);

namespace Maatify\Category\Contract;

use Maatify\Category\DTO\CategoryCollectionDTO;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CategoryTranslationCollectionDTO;

/** Public application contract for visible Category reads and lists. */
interface CategoryQueryServiceInterface
{
    /**
     * @throws \Maatify\Category\Exception\CategoryNotFoundException
     */
    public function getById(int $categoryId): CategoryDTO;

    public function listRootCategories(): CategoryCollectionDTO;

    public function listChildren(int $parentId): CategoryCollectionDTO;

    public function listTranslations(int $categoryId): CategoryTranslationCollectionDTO;
}
