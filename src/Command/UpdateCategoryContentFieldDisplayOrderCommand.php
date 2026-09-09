<?php

declare(strict_types=1);

namespace Maatify\Category\Command;

use Maatify\Category\DTO\CategoryIdDTO;
use Maatify\Category\Exception\CategoryInvalidArgumentException;

/** Dedicated ordering operation; Category Content Field identity is immutable. */
final readonly class UpdateCategoryContentFieldDisplayOrderCommand implements \JsonSerializable
{
    public int $fieldId;

    public function __construct(int|string $fieldId, public int $displayOrder)
    {
        if ($displayOrder < 1) {
            throw CategoryInvalidArgumentException::invalidDisplayOrder($displayOrder);
        }

        $this->fieldId = (new CategoryIdDTO($fieldId, 'fieldId'))->value;
    }

    /** @return array{fieldId: int, displayOrder: int} */
    public function jsonSerialize(): mixed
    {
        return [
            'fieldId' => $this->fieldId,
            'displayOrder' => $this->displayOrder,
        ];
    }
}
