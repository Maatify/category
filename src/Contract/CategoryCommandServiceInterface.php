<?php

declare(strict_types=1);

namespace Maatify\Category\Contract;

use Maatify\Category\Command\CreateCategoryCommand;
use Maatify\Category\Command\CreateCategoryContentCommand;
use Maatify\Category\Command\CreateCategoryImageAssignmentCommand;
use Maatify\Category\Command\CreateCategoryImageRoleCommand;
use Maatify\Category\Command\CreateCategoryContentFieldCommand;
use Maatify\Category\Command\MoveCategoryCommand;
use Maatify\Category\Command\RestoreCategoryCommand;
use Maatify\Category\Command\RestoreCategoryContentCommand;
use Maatify\Category\Command\RestoreCategoryImageAssignmentCommand;
use Maatify\Category\Command\RestoreCategoryImageRoleCommand;
use Maatify\Category\Command\SoftDeleteCategoryCommand;
use Maatify\Category\Command\SoftDeleteCategoryContentCommand;
use Maatify\Category\Command\SoftDeleteCategoryImageAssignmentCommand;
use Maatify\Category\Command\SoftDeleteCategoryImageRoleCommand;
use Maatify\Category\Command\UpdateCategoryDisplayOrderCommand;
use Maatify\Category\Command\UpdateCategoryStatusCommand;
use Maatify\Category\Command\UpdateCategoryContentCommand;
use Maatify\Category\Command\UpdateCategoryImageAssignmentDisplayOrderCommand;
use Maatify\Category\Command\UpdateCategoryImageRoleStatusCommand;
use Maatify\Category\Command\UpdateCategoryContentFieldCommand;
use Maatify\Category\Command\UpdateCategoryContentFieldDisplayOrderCommand;
use Maatify\Category\Command\SoftDeleteCategoryContentFieldCommand;
use Maatify\Category\Command\RestoreCategoryContentFieldCommand;

/** Public application contract for Category business mutations. */
interface CategoryCommandServiceInterface
{
    public function create(CreateCategoryCommand $command): int;

    public function createContent(CreateCategoryContentCommand $command): int;

    public function createImageAssignment(CreateCategoryImageAssignmentCommand $command): int;

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
