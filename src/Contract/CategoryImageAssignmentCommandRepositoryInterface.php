<?php

declare(strict_types=1);

namespace Maatify\Category\Contract;

use DateTimeImmutable;
use Maatify\Category\Command\CreateCategoryImageAssignmentCommand;
use Maatify\Category\Command\RestoreCategoryImageAssignmentCommand;
use Maatify\Category\Command\SoftDeleteCategoryImageAssignmentCommand;
use Maatify\Category\Command\UpdateCategoryImageAssignmentDisplayOrderCommand;

/** Write port for the Category-owned Media Asset assignment lifecycle. */
interface CategoryImageAssignmentCommandRepositoryInterface
{
    public function create(CreateCategoryImageAssignmentCommand $command, DateTimeImmutable $occurredAt): int;

    public function updateDisplayOrder(
        UpdateCategoryImageAssignmentDisplayOrderCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool;

    public function softDelete(
        SoftDeleteCategoryImageAssignmentCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool;

    public function restore(
        RestoreCategoryImageAssignmentCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool;
}
