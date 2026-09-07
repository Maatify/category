<?php

declare(strict_types=1);

namespace Maatify\Category\DTO;

use Maatify\Category\Exception\CategoryInvalidArgumentException;

/** Validated input for creating a Category Translation with a stable identity. */
final readonly class CreateCategoryTranslationDTO implements \JsonSerializable
{
    public int $categoryId;
    public string $languageCode;
    public string $name;
    public ?string $description;

    public function __construct(
        int|string $categoryId,
        string $languageCode,
        string $name,
        ?string $description,
    ) {
        if (trim($languageCode) === '') {
            throw CategoryInvalidArgumentException::emptyField('languageCode');
        }

        if (mb_strlen($languageCode) > 16) {
            throw CategoryInvalidArgumentException::fieldTooLong('languageCode', 16);
        }

        if (trim($name) === '') {
            throw CategoryInvalidArgumentException::emptyField('name');
        }

        if (mb_strlen($name) > 255) {
            throw CategoryInvalidArgumentException::fieldTooLong('name', 255);
        }

        $this->categoryId = (new CategoryIdDTO($categoryId, 'categoryId'))->value;
        $this->languageCode = $languageCode;
        $this->name = $name;
        $this->description = $description;
    }

    /** @return array{categoryId: int, languageCode: string, name: string, description: ?string} */
    public function jsonSerialize(): mixed
    {
        return [
            'categoryId' => $this->categoryId,
            'languageCode' => $this->languageCode,
            'name' => $this->name,
            'description' => $this->description,
        ];
    }
}
