<?php

declare(strict_types=1);

namespace Maatify\Category\Api\Service;

use Maatify\Category\Api\Contract\CategoryManagementQueryServiceInterface;
use Maatify\Category\Query\Contract\CategoryManagementReadQueryInterface;
use Maatify\Category\Query\DTO\CategoryCollectionDTO;
use Maatify\Category\Query\DTO\CategoryDTO;
use Maatify\Category\Common\DTO\CategoryIdDTO;
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
use Maatify\Category\Exception\CategoryNotFoundException;
use Maatify\Category\Content\Exception\CategoryContentNotFoundException;
use Maatify\Category\ImageAssignment\Exception\CategoryImageAssignmentNotFoundException;
use Maatify\Category\ImageRole\Exception\CategoryImageRoleNotFoundException;
use Maatify\Category\ContentField\Exception\CategoryContentFieldNotFoundException;

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

    public function getImageAssignmentById(
        int $assignmentId,
        CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
    ): CategoryImageAssignmentDTO {
        $id = (new CategoryIdDTO($assignmentId, 'assignmentId'))->value;
        $assignment = $this->reader->findImageAssignmentById($id, $deletedState);

        if ($assignment === null) {
            throw CategoryImageAssignmentNotFoundException::withId($id);
        }

        return $assignment;
    }

    public function listImageAssignments(
        CategoryImageAssignmentListCriteriaDTO $criteria,
    ): CategoryImageAssignmentCollectionDTO {
        return $this->reader->listImageAssignments($criteria);
    }

    public function getImageRoleById(
        int $roleId,
        CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
    ): CategoryImageRoleDTO {
        $id = (new CategoryIdDTO($roleId, 'roleId'))->value;
        $role = $this->reader->findImageRoleById($id, $deletedState);

        if ($role === null) {
            throw CategoryImageRoleNotFoundException::withId($id);
        }

        return $role;
    }

    public function getImageRoleByKey(
        string $roleKey,
        CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
    ): CategoryImageRoleDTO {
        CategoryImageRoleDTO::assertValidRoleKey($roleKey);
        $role = $this->reader->findImageRoleByKey($roleKey, $deletedState);

        if ($role === null) {
            throw CategoryImageRoleNotFoundException::withKey($roleKey);
        }

        return $role;
    }

    public function listImageRoles(CategoryImageRoleListCriteriaDTO $criteria): CategoryImageRoleCollectionDTO
    {
        return $this->reader->listImageRoles($criteria);
    }

    public function getContentFieldById(
        int $fieldId,
        CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
    ): CategoryContentFieldDTO {
        $id = (new CategoryIdDTO($fieldId, 'fieldId'))->value;
        $field = $this->reader->findContentFieldById($id, $deletedState);

        if ($field === null) {
            throw CategoryContentFieldNotFoundException::withId($id);
        }

        return $field;
    }

    public function listContentFields(
        CategoryContentFieldListCriteriaDTO $criteria,
    ): CategoryContentFieldCollectionDTO {
        return $this->reader->listContentFields($criteria);
    }
}
