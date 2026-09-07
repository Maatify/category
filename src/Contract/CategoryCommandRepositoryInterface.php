<?php

declare(strict_types=1);

namespace Maatify\Category\Contract;

use DateTimeImmutable;
use Maatify\Category\DTO\CreateCategoryDTO;
use Maatify\Category\DTO\MoveCategoryDTO;
use Maatify\Category\DTO\RestoreCategoryDTO;
use Maatify\Category\DTO\SoftDeleteCategoryDTO;
use Maatify\Category\DTO\UpdateCategoryDisplayOrderDTO;
use Maatify\Category\DTO\UpdateCategoryStatusDTO;

/**
 * Write port for Category persistence.
 *
 * The Category application owns mutation timestamps. Persistence adapters only
 * persist the timestamp supplied by the application and delegate display-order
 * mechanics to the approved persistence capability.
 */
interface CategoryCommandRepositoryInterface
{
    public function create(CreateCategoryDTO $command, DateTimeImmutable $occurredAt): int;

    public function move(MoveCategoryDTO $command, DateTimeImmutable $occurredAt): bool;

    public function softDelete(SoftDeleteCategoryDTO $command, DateTimeImmutable $occurredAt): bool;

    public function restore(RestoreCategoryDTO $command, DateTimeImmutable $occurredAt): bool;

    public function updateStatus(UpdateCategoryStatusDTO $command, DateTimeImmutable $occurredAt): bool;

    public function updateDisplayOrder(UpdateCategoryDisplayOrderDTO $command, DateTimeImmutable $occurredAt): bool;
}
