<?php

declare(strict_types=1);

namespace Maatify\Category\Command;

use Maatify\Category\DTO\CategoryIdDTO;

/** Validated command for soft-deleting a Category Content. */
final readonly class SoftDeleteCategoryContentCommand implements \JsonSerializable
{
    public int $contentId;

    public function __construct(int|string $contentId)
    {
        $this->contentId = (new CategoryIdDTO($contentId, 'contentId'))->value;
    }

    /** @return array{contentId: int} */
    public function jsonSerialize(): mixed
    {
        return ['contentId' => $this->contentId];
    }
}
