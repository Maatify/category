<?php

declare(strict_types=1);

namespace Maatify\Category\Contract;

use DateTimeImmutable;
use Maatify\Category\Command\CreateCategoryContentFieldCommand;
use Maatify\Category\Command\RestoreCategoryContentFieldCommand;
use Maatify\Category\Command\SoftDeleteCategoryContentFieldCommand;
use Maatify\Category\Command\UpdateCategoryContentFieldCommand;
use Maatify\Category\Command\UpdateCategoryContentFieldDisplayOrderCommand;

/** Write port for the complete Category Content Field lifecycle. */
interface CategoryContentFieldCommandRepositoryInterface
{
    public function create(CreateCategoryContentFieldCommand $command, DateTimeImmutable $occurredAt): int;

    public function update(UpdateCategoryContentFieldCommand $command, DateTimeImmutable $occurredAt): bool;

    public function updateDisplayOrder(
        UpdateCategoryContentFieldDisplayOrderCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool;

    public function softDelete(
        SoftDeleteCategoryContentFieldCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool;

    public function restore(
        RestoreCategoryContentFieldCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool;
}
