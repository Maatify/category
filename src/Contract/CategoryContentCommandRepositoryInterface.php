<?php

declare(strict_types=1);

namespace Maatify\Category\Contract;

use DateTimeImmutable;
use Maatify\Category\Command\CreateCategoryContentCommand;
use Maatify\Category\Command\RestoreCategoryContentCommand;
use Maatify\Category\Command\SoftDeleteCategoryContentCommand;
use Maatify\Category\Command\UpdateCategoryContentCommand;

/** Write port for the complete Category Content mutation lifecycle. */
interface CategoryContentCommandRepositoryInterface
{
    public function create(CreateCategoryContentCommand $command, DateTimeImmutable $occurredAt): int;

    public function update(UpdateCategoryContentCommand $command, DateTimeImmutable $occurredAt): bool;

    public function softDelete(SoftDeleteCategoryContentCommand $command, DateTimeImmutable $occurredAt): bool;

    public function restore(RestoreCategoryContentCommand $command, DateTimeImmutable $occurredAt): bool;
}
