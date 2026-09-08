<?php

declare(strict_types=1);

namespace Maatify\Category\Command;

use Maatify\Category\DTO\CategoryIdDTO;

/** Validated command for restoring a Category Translation with its identity. */
final readonly class RestoreCategoryTranslationCommand implements \JsonSerializable
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
