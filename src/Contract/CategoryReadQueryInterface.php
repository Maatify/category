<?php

declare(strict_types=1);

namespace Maatify\Category\Contract;

use Maatify\Category\DTO\CategoryCollectionDTO;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CategoryContentCollectionDTO;
use Maatify\Category\DTO\CategoryVisibleListCriteriaDTO;

/** Dedicated public read port for visible Category query behavior. */
interface CategoryReadQueryInterface
{
    /** Finds a non-deleted, active Category whose complete ancestor chain is visible. */
    public function findVisibleById(int $categoryId): ?CategoryDTO;

    /** Lists visible root Categories in deterministic display order. */
    public function listVisibleRootCategories(
        CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO(),
    ): CategoryCollectionDTO;

    /**
     * Lists visible direct children of an active parent whose complete ancestor
     * chain is visible.
     */
    public function listVisibleChildren(
        int $parentId,
        CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO(),
    ): CategoryCollectionDTO;

    /**
     * Lists non-deleted contents for a visible Category in language-code
     * order. The Package validates the syntactic/storage contract; the Host
     * validates semantic language support and owns fallback/locale policy.
     */
    public function listVisibleContents(
        int $categoryId,
        CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO(),
    ): CategoryContentCollectionDTO;
}
