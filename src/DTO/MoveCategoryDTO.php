<?php

declare(strict_types=1);

namespace Maatify\Category\DTO;

use Maatify\Category\Exception\CategoryInvalidArgumentException;

/** Validated input for moving a Category to a new parent or to the root. */
final readonly class MoveCategoryDTO implements \JsonSerializable
{
    public int $categoryId;
    public ?int $parentId;

    public function __construct(int|string $categoryId, int|string|null $parentId)
    {
        $normalizedCategoryId = (new CategoryIdDTO($categoryId, 'categoryId'))->value;
        $normalizedParentId = $parentId === null
            ? null
            : (new CategoryIdDTO($parentId, 'parentId'))->value;

        if ($normalizedCategoryId === $normalizedParentId) {
            throw CategoryInvalidArgumentException::selfParent($normalizedCategoryId);
        }

        $this->categoryId = $normalizedCategoryId;
        $this->parentId = $normalizedParentId;
    }

    /** @return array{categoryId: int, parentId: ?int} */
    public function jsonSerialize(): mixed
    {
        return [
            'categoryId' => $this->categoryId,
            'parentId' => $this->parentId,
        ];
    }
}
