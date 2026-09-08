<?php

declare(strict_types=1);

namespace Maatify\Category\Contract;

use Maatify\Category\Command\CreateCategoryCommand;
use Maatify\Category\Command\CreateCategoryTranslationCommand;
use Maatify\Category\Command\MoveCategoryCommand;
use Maatify\Category\Command\RestoreCategoryCommand;
use Maatify\Category\Command\RestoreCategoryTranslationCommand;
use Maatify\Category\Command\SoftDeleteCategoryCommand;
use Maatify\Category\Command\SoftDeleteCategoryTranslationCommand;
use Maatify\Category\Command\UpdateCategoryDisplayOrderCommand;
use Maatify\Category\Command\UpdateCategoryStatusCommand;
use Maatify\Category\Command\UpdateCategoryTranslationCommand;

/** Public application contract for Category business mutations. */
interface CategoryCommandServiceInterface
{
    public function create(CreateCategoryCommand $command): int;

    public function createTranslation(CreateCategoryTranslationCommand $command): int;

    public function move(MoveCategoryCommand $command): void;

    public function softDelete(SoftDeleteCategoryCommand $command): void;

    public function softDeleteTranslation(SoftDeleteCategoryTranslationCommand $command): void;

    public function restore(RestoreCategoryCommand $command): void;

    public function restoreTranslation(RestoreCategoryTranslationCommand $command): void;

    public function updateStatus(UpdateCategoryStatusCommand $command): void;

    public function updateDisplayOrder(UpdateCategoryDisplayOrderCommand $command): void;

    public function updateTranslation(UpdateCategoryTranslationCommand $command): void;
}
