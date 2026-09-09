<?php

declare(strict_types=1);

namespace Maatify\Category\Contract;

use Maatify\Category\Command\CreateCategoryCommand;
use Maatify\Category\Command\CreateCategoryContentCommand;
use Maatify\Category\Command\MoveCategoryCommand;
use Maatify\Category\Command\RestoreCategoryCommand;
use Maatify\Category\Command\RestoreCategoryContentCommand;
use Maatify\Category\Command\SoftDeleteCategoryCommand;
use Maatify\Category\Command\SoftDeleteCategoryContentCommand;
use Maatify\Category\Command\UpdateCategoryDisplayOrderCommand;
use Maatify\Category\Command\UpdateCategoryStatusCommand;
use Maatify\Category\Command\UpdateCategoryContentCommand;

/** Public application contract for Category business mutations. */
interface CategoryCommandServiceInterface
{
    public function create(CreateCategoryCommand $command): int;

    public function createContent(CreateCategoryContentCommand $command): int;

    public function move(MoveCategoryCommand $command): void;

    public function softDelete(SoftDeleteCategoryCommand $command): void;

    public function softDeleteContent(SoftDeleteCategoryContentCommand $command): void;

    public function restore(RestoreCategoryCommand $command): void;

    public function restoreContent(RestoreCategoryContentCommand $command): void;

    public function updateStatus(UpdateCategoryStatusCommand $command): void;

    public function updateDisplayOrder(UpdateCategoryDisplayOrderCommand $command): void;

    public function updateContent(UpdateCategoryContentCommand $command): void;
}
