<?php

declare(strict_types=1);

namespace Maatify\Category\DTO;

use Maatify\Category\Exception\CategoryInvalidArgumentException;

/** Validated input for the dedicated Category display-order operation. */
final readonly class UpdateCategoryDisplayOrderDTO implements \JsonSerializable
{
    public int $categoryId;
    public int $displayOrder;

    public function __construct(int|string $categoryId, int $displayOrder)
    {
        if ($displayOrder < 1) {
            throw CategoryInvalidArgumentException::invalidDisplayOrder($displayOrder);
        }

        $this->categoryId = (new CategoryIdDTO($categoryId, 'categoryId'))->value;
        $this->displayOrder = $displayOrder;
    }

    /** @return array{categoryId: int, displayOrder: int} */
    public function jsonSerialize(): mixed
    {
        return [
            'categoryId' => $this->categoryId,
            'displayOrder' => $this->displayOrder,
        ];
    }
}
