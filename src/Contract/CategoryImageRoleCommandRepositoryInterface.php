<?php

declare(strict_types=1);

namespace Maatify\Category\Contract;

use DateTimeImmutable;
use Maatify\Category\Command\CreateCategoryImageRoleCommand;
use Maatify\Category\Command\RestoreCategoryImageRoleCommand;
use Maatify\Category\Command\SoftDeleteCategoryImageRoleCommand;
use Maatify\Category\Command\UpdateCategoryImageRoleStatusCommand;

/** Write port for the package-owned Category Image Role lifecycle. */
interface CategoryImageRoleCommandRepositoryInterface
{
    public function create(CreateCategoryImageRoleCommand $command, DateTimeImmutable $occurredAt): int;

    public function updateStatus(
        UpdateCategoryImageRoleStatusCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool;

    public function softDelete(
        SoftDeleteCategoryImageRoleCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool;

    public function restore(
        RestoreCategoryImageRoleCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool;
}
