<?php

declare(strict_types=1);

namespace Maatify\Category\ImageAssignment\Query\Contract;

use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;
use Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentCollectionDTO;
use Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentDTO;
use Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentListCriteriaDTO;

/** Dedicated management read port for Image Assignments. */
interface CategoryImageAssignmentManagementReadQueryInterface
{
    public function findImageAssignmentById(
        int $assignmentId,
        CategoryDeletedStateEnum $deletedState,
    ): ?CategoryImageAssignmentDTO;

    public function listImageAssignments(
        CategoryImageAssignmentListCriteriaDTO $criteria,
    ): CategoryImageAssignmentCollectionDTO;
}
