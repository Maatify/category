<?php

declare(strict_types=1);

namespace Maatify\Category\Query\Contract;

use Maatify\Category\Query\DTO\CategoryCollectionDTO;
use Maatify\Category\Query\DTO\CategoryDTO;
use Maatify\Category\Query\DTO\CategoryListCriteriaDTO;
use Maatify\Category\Content\Query\DTO\CategoryContentCollectionDTO;
use Maatify\Category\Content\Query\DTO\CategoryContentDTO;
use Maatify\Category\Content\Query\DTO\CategoryContentListCriteriaDTO;
use Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentCollectionDTO;
use Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentDTO;
use Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentListCriteriaDTO;
use Maatify\Category\ImageRole\Query\DTO\CategoryImageRoleCollectionDTO;
use Maatify\Category\ImageRole\Query\DTO\CategoryImageRoleDTO;
use Maatify\Category\ImageRole\Query\DTO\CategoryImageRoleListCriteriaDTO;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldCollectionDTO;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldDTO;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldListCriteriaDTO;
use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;

/** Dedicated management read port, separate from consumer visibility reads. */
interface CategoryManagementReadQueryInterface
{
    /** Finds a Category using the requested explicit soft-deletion state. */
    public function findById(int $categoryId, CategoryDeletedStateEnum $deletedState): ?CategoryDTO;

    /** Lists Categories in display-order/id order, bounded by the criteria. */
    public function listCategories(CategoryListCriteriaDTO $criteria): CategoryCollectionDTO;

    /** Lists root Categories in display-order/id order, bounded by the criteria. */
    public function listRootCategories(CategoryListCriteriaDTO $criteria): CategoryCollectionDTO;

    /** Lists direct children in display-order/id order, bounded by the criteria. */
    public function listChildren(int $parentId, CategoryListCriteriaDTO $criteria): CategoryCollectionDTO;

    /** Finds a Content using the requested explicit soft-deletion state. */
    public function findContentById(
        int $contentId,
        CategoryDeletedStateEnum $deletedState,
    ): ?CategoryContentDTO;

    /** Lists Contents in language-code/id order, bounded by the criteria. */
    public function listContents(
        CategoryContentListCriteriaDTO $criteria,
    ): CategoryContentCollectionDTO;

    public function findImageAssignmentById(
        int $assignmentId,
        CategoryDeletedStateEnum $deletedState,
    ): ?CategoryImageAssignmentDTO;

    public function listImageAssignments(
        CategoryImageAssignmentListCriteriaDTO $criteria,
    ): CategoryImageAssignmentCollectionDTO;

    public function findImageRoleById(
        int $roleId,
        CategoryDeletedStateEnum $deletedState,
    ): ?CategoryImageRoleDTO;

    public function findImageRoleByKey(
        string $roleKey,
        CategoryDeletedStateEnum $deletedState,
    ): ?CategoryImageRoleDTO;

    public function listImageRoles(CategoryImageRoleListCriteriaDTO $criteria): CategoryImageRoleCollectionDTO;

    public function findContentFieldById(
        int $fieldId,
        CategoryDeletedStateEnum $deletedState,
    ): ?CategoryContentFieldDTO;

    public function listContentFields(
        CategoryContentFieldListCriteriaDTO $criteria,
    ): CategoryContentFieldCollectionDTO;
}
