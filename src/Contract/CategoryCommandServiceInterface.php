<?php

declare(strict_types=1);

namespace Maatify\Category\Contract;

use Maatify\Category\DTO\CreateCategoryDTO;
use Maatify\Category\DTO\MoveCategoryDTO;
use Maatify\Category\DTO\RestoreCategoryDTO;
use Maatify\Category\DTO\SoftDeleteCategoryDTO;
use Maatify\Category\DTO\UpdateCategoryDisplayOrderDTO;
use Maatify\Category\DTO\UpdateCategoryStatusDTO;
use Maatify\Category\DTO\UpdateCategoryTranslationDTO;

/** Public application contract for Category business mutations. */
interface CategoryCommandServiceInterface
{
    public function create(CreateCategoryDTO $command): int;

    public function move(MoveCategoryDTO $command): void;

    public function softDelete(SoftDeleteCategoryDTO $command): void;

    public function restore(RestoreCategoryDTO $command): void;

    public function updateStatus(UpdateCategoryStatusDTO $command): void;

    public function updateDisplayOrder(UpdateCategoryDisplayOrderDTO $command): void;

    public function updateTranslation(UpdateCategoryTranslationDTO $command): void;
}
