<?php

declare(strict_types=1);

namespace Maatify\Category\DTO;

/** Validated input for soft-deleting a Category. */
final readonly class SoftDeleteCategoryDTO implements \JsonSerializable
{
    public int $categoryId;

    public function __construct(int|string $categoryId)
    {
        $this->categoryId = (new CategoryIdDTO($categoryId, 'categoryId'))->value;
    }

    /** @return array{categoryId: int} */
    public function jsonSerialize(): mixed
    {
        return ['categoryId' => $this->categoryId];
    }
}
