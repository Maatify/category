<?php

declare(strict_types=1);

namespace Maatify\Category\Command;

use Maatify\Category\DTO\CategoryIdDTO;

/** Validated command for soft-deleting a Category Translation. */
final readonly class SoftDeleteCategoryTranslationCommand implements \JsonSerializable
{
    public int $translationId;

    public function __construct(int|string $translationId)
    {
        $this->translationId = (new CategoryIdDTO($translationId, 'translationId'))->value;
    }

    /** @return array{translationId: int} */
    public function jsonSerialize(): mixed
    {
        return ['translationId' => $this->translationId];
    }
}
