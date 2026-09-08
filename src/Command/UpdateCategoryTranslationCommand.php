<?php

declare(strict_types=1);

namespace Maatify\Category\Command;

use Maatify\Category\DTO\CategoryIdDTO;
use Maatify\Category\Exception\CategoryInvalidArgumentException;

/**
 * Validated command for changing translation content.
 *
 * The Category Translation logical identity is deliberately absent: neither
 * category_id nor language_code can be changed through this contract.
 */
final readonly class UpdateCategoryTranslationCommand implements \JsonSerializable
{
    public int $translationId;
    public string $name;
    public ?string $description;

    public function __construct(int|string $translationId, string $name, ?string $description)
    {
        if (trim($name) === '') {
            throw CategoryInvalidArgumentException::emptyField('name');
        }

        if (mb_strlen($name) > 255) {
            throw CategoryInvalidArgumentException::fieldTooLong('name', 255);
        }

        $this->translationId = (new CategoryIdDTO($translationId, 'translationId'))->value;
        $this->name = $name;
        $this->description = $description;
    }

    /** @return array{translationId: int, name: string, description: ?string} */
    public function jsonSerialize(): mixed
    {
        return [
            'translationId' => $this->translationId,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
