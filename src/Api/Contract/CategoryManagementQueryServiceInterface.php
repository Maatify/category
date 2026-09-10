<?php

declare(strict_types=1);

namespace Maatify\Category\Api\Contract;

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
use Maatify\Category\ImageRole\CategoryImageRoleDTO;
use Maatify\Category\ImageRole\Query\DTO\CategoryImageRoleListCriteriaDTO;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldCollectionDTO;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldDTO;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldListCriteriaDTO;
use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;

/** Public application contract for management Category reads. */
interface CategoryManagementQueryServiceInterface
{
    /** @throws \Maatify\Category\Exception\CategoryNotFoundException */
    public function getById(
        int $categoryId,
        CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
    ): CategoryDTO;

    public function listCategories(CategoryListCriteriaDTO $criteria): CategoryCollectionDTO;

    public function listRootCategories(CategoryListCriteriaDTO $criteria): CategoryCollectionDTO;

    public function listChildren(int $parentId, CategoryListCriteriaDTO $criteria): CategoryCollectionDTO;

    /** @throws \Maatify\Category\Content\Exception\CategoryContentNotFoundException */
    public function getContentById(
        int $contentId,
        CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
    ): CategoryContentDTO;

    public function listContents(
        CategoryContentListCriteriaDTO $criteria,
    ): CategoryContentCollectionDTO;

    /** @throws \Maatify\Category\ImageAssignment\Exception\CategoryImageAssignmentNotFoundException */
    public function getImageAssignmentById(
        int $assignmentId,
        CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
    ): CategoryImageAssignmentDTO;

    public function listImageAssignments(
        CategoryImageAssignmentListCriteriaDTO $criteria,
    ): CategoryImageAssignmentCollectionDTO;

    /** @throws \Maatify\Category\ImageRole\Exception\CategoryImageRoleNotFoundException */
    public function getImageRoleById(
        int $roleId,
        CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
    ): CategoryImageRoleDTO;

    /** @throws \Maatify\Category\ImageRole\Exception\CategoryImageRoleNotFoundException */
    public function getImageRoleByKey(
        string $roleKey,
        CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
    ): CategoryImageRoleDTO;

    public function listImageRoles(CategoryImageRoleListCriteriaDTO $criteria): CategoryImageRoleCollectionDTO;

    /** @throws \Maatify\Category\ContentField\Exception\CategoryContentFieldNotFoundException */
    public function getContentFieldById(
        int $fieldId,
        CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
    ): CategoryContentFieldDTO;

    public function listContentFields(
        CategoryContentFieldListCriteriaDTO $criteria,
    ): CategoryContentFieldCollectionDTO;
}
