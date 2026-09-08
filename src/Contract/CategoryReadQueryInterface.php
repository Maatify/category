<?php

declare(strict_types=1);

namespace Maatify\Category\Contract;

use Maatify\Category\DTO\CategoryCollectionDTO;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CategoryTranslationCollectionDTO;

/** Dedicated public read port for visible Category query behavior. */
interface CategoryReadQueryInterface
{
    /** Finds a non-deleted, active Category whose complete ancestor chain is visible. */
    public function findVisibleById(int $categoryId): ?CategoryDTO;

    /** Lists visible root Categories in deterministic display order. */
    public function listVisibleRootCategories(): CategoryCollectionDTO;

    /**
     * Lists visible direct children of an active parent whose complete ancestor
     * chain is visible.
     */
    public function listVisibleChildren(int $parentId): CategoryCollectionDTO;

    /**
     * Lists non-deleted translations for a visible Category in language-code
     * order. The Package validates the syntactic/storage contract; the Host
     * validates semantic language support and owns fallback/locale policy.
     */
    public function listVisibleTranslations(int $categoryId): CategoryTranslationCollectionDTO;
}
