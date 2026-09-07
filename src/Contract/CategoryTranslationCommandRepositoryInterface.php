<?php

declare(strict_types=1);

namespace Maatify\Category\Contract;

use DateTimeImmutable;
use Maatify\Category\DTO\CreateCategoryTranslationDTO;
use Maatify\Category\DTO\RestoreCategoryTranslationDTO;
use Maatify\Category\DTO\SoftDeleteCategoryTranslationDTO;
use Maatify\Category\DTO\UpdateCategoryTranslationDTO;

/** Write port for the complete Category Translation mutation lifecycle. */
interface CategoryTranslationCommandRepositoryInterface
{
    public function create(CreateCategoryTranslationDTO $command, DateTimeImmutable $occurredAt): int;

    public function update(UpdateCategoryTranslationDTO $command, DateTimeImmutable $occurredAt): bool;

    public function softDelete(SoftDeleteCategoryTranslationDTO $command, DateTimeImmutable $occurredAt): bool;

    public function restore(RestoreCategoryTranslationDTO $command, DateTimeImmutable $occurredAt): bool;
}
