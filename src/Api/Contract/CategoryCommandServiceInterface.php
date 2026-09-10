<?php

declare(strict_types=1);

namespace Maatify\Category\Api\Contract;

use Maatify\Category\Lifecycle\Command\CreateCategoryCommand;
use Maatify\Category\Content\Mutation\Command\CreateCategoryContentCommand;
use Maatify\Category\ImageAssignment\Assignment\Command\CreateCategoryImageAssignmentCommand;
use Maatify\Category\ImageAssignment\Default\Command\ClearCategoryImageAssignmentDefaultCommand;
use Maatify\Category\ImageRole\Lifecycle\Command\CreateCategoryImageRoleCommand;
use Maatify\Category\ContentField\Mutation\Command\CreateCategoryContentFieldCommand;
use Maatify\Category\Hierarchy\Command\MoveCategoryCommand;
use Maatify\Category\Lifecycle\Command\RestoreCategoryCommand;
use Maatify\Category\Content\Mutation\Command\RestoreCategoryContentCommand;
use Maatify\Category\ImageAssignment\Lifecycle\Command\RestoreCategoryImageAssignmentCommand;
use Maatify\Category\ImageRole\Lifecycle\Command\RestoreCategoryImageRoleCommand;
use Maatify\Category\Lifecycle\Command\SoftDeleteCategoryCommand;
use Maatify\Category\Content\Mutation\Command\SoftDeleteCategoryContentCommand;
use Maatify\Category\ImageAssignment\Lifecycle\Command\SoftDeleteCategoryImageAssignmentCommand;
use Maatify\Category\ImageAssignment\Default\Command\SetCategoryImageAssignmentDefaultCommand;
use Maatify\Category\ImageRole\Lifecycle\Command\SoftDeleteCategoryImageRoleCommand;
use Maatify\Category\Ordering\Command\UpdateCategoryDisplayOrderCommand;
use Maatify\Category\Lifecycle\Command\UpdateCategoryStatusCommand;
use Maatify\Category\Content\Mutation\Command\UpdateCategoryContentCommand;
use Maatify\Category\ImageAssignment\Ordering\Command\UpdateCategoryImageAssignmentDisplayOrderCommand;
use Maatify\Category\ImageRole\Lifecycle\Command\UpdateCategoryImageRoleStatusCommand;
use Maatify\Category\ContentField\Mutation\Command\UpdateCategoryContentFieldCommand;
use Maatify\Category\ContentField\Ordering\Command\UpdateCategoryContentFieldDisplayOrderCommand;
use Maatify\Category\ContentField\Mutation\Command\SoftDeleteCategoryContentFieldCommand;
use Maatify\Category\ContentField\Mutation\Command\RestoreCategoryContentFieldCommand;

/** Public application contract for Category business mutations. */
interface CategoryCommandServiceInterface
{
    public function create(CreateCategoryCommand $command): int;

    public function createContent(CreateCategoryContentCommand $command): int;

    public function createImageAssignment(CreateCategoryImageAssignmentCommand $command): int;

    public function setImageAssignmentDefault(SetCategoryImageAssignmentDefaultCommand $command): void;

    public function clearImageAssignmentDefault(ClearCategoryImageAssignmentDefaultCommand $command): void;

    public function createImageRole(CreateCategoryImageRoleCommand $command): int;

    public function updateImageRoleStatus(UpdateCategoryImageRoleStatusCommand $command): void;

    public function softDeleteImageRole(SoftDeleteCategoryImageRoleCommand $command): void;

    public function restoreImageRole(RestoreCategoryImageRoleCommand $command): void;

    public function createContentField(CreateCategoryContentFieldCommand $command): int;

    public function move(MoveCategoryCommand $command): void;

    public function softDelete(SoftDeleteCategoryCommand $command): void;

    public function softDeleteContent(SoftDeleteCategoryContentCommand $command): void;

    public function restore(RestoreCategoryCommand $command): void;

    public function restoreContent(RestoreCategoryContentCommand $command): void;

    public function restoreImageAssignment(RestoreCategoryImageAssignmentCommand $command): void;

    public function updateStatus(UpdateCategoryStatusCommand $command): void;

    public function updateDisplayOrder(UpdateCategoryDisplayOrderCommand $command): void;

    public function updateContent(UpdateCategoryContentCommand $command): void;

    public function updateImageAssignmentDisplayOrder(
        UpdateCategoryImageAssignmentDisplayOrderCommand $command,
    ): void;

    public function updateContentField(UpdateCategoryContentFieldCommand $command): void;

    public function updateContentFieldDisplayOrder(
        UpdateCategoryContentFieldDisplayOrderCommand $command,
    ): void;

    public function softDeleteImageAssignment(SoftDeleteCategoryImageAssignmentCommand $command): void;

    public function softDeleteContentField(SoftDeleteCategoryContentFieldCommand $command): void;

    public function restoreContentField(RestoreCategoryContentFieldCommand $command): void;
}
