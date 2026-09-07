<?php

declare(strict_types=1);

namespace Maatify\Category\Contract;

use DateTimeImmutable;
use Maatify\Category\DTO\UpdateCategoryTranslationDTO;

/** Write port for Category Translation content without logical-identity changes. */
interface CategoryTranslationCommandRepositoryInterface
{
    public function update(UpdateCategoryTranslationDTO $command, DateTimeImmutable $occurredAt): bool;
}
