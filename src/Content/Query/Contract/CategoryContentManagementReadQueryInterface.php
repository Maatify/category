<?php

declare(strict_types=1);

namespace Maatify\Category\Content\Query\Contract;

use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;
use Maatify\Category\Content\Query\DTO\CategoryContentCollectionDTO;
use Maatify\Category\Content\Query\DTO\CategoryContentDTO;
use Maatify\Category\Content\Query\DTO\CategoryContentListCriteriaDTO;

/** Dedicated management read port for Category Content. */
interface CategoryContentManagementReadQueryInterface
{
    /** Finds a Content using the requested explicit soft-deletion state. */
    public function findContentById(
        int $contentId,
        CategoryDeletedStateEnum $deletedState,
    ): ?CategoryContentDTO;

    /** Lists Contents in language-code/id order, bounded by the criteria. */
    public function listContents(
        CategoryContentListCriteriaDTO $criteria,
    ): CategoryContentCollectionDTO;
}
