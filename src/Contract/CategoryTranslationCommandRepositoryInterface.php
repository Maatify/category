<?php

declare(strict_types=1);

namespace Maatify\Category\Contract;

use DateTimeImmutable;
use Maatify\Category\Command\CreateCategoryTranslationCommand;
use Maatify\Category\Command\RestoreCategoryTranslationCommand;
use Maatify\Category\Command\SoftDeleteCategoryTranslationCommand;
use Maatify\Category\Command\UpdateCategoryTranslationCommand;

/** Write port for the complete Category Translation mutation lifecycle. */
interface CategoryTranslationCommandRepositoryInterface
{
    public function create(CreateCategoryTranslationCommand $command, DateTimeImmutable $occurredAt): int;

    public function update(UpdateCategoryTranslationCommand $command, DateTimeImmutable $occurredAt): bool;

    public function softDelete(SoftDeleteCategoryTranslationCommand $command, DateTimeImmutable $occurredAt): bool;

    public function restore(RestoreCategoryTranslationCommand $command, DateTimeImmutable $occurredAt): bool;
}
