<?php

declare(strict_types=1);

namespace Maatify\Category\ContentField\Query\Contract;

use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldCollectionDTO;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldDTO;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldListCriteriaDTO;

/** Dedicated management read port for Content Fields. */
interface CategoryContentFieldManagementReadQueryInterface
{
    public function findContentFieldById(
        int $fieldId,
        CategoryDeletedStateEnum $deletedState,
    ): ?CategoryContentFieldDTO;

    public function listContentFields(
        CategoryContentFieldListCriteriaDTO $criteria,
    ): CategoryContentFieldCollectionDTO;
}
