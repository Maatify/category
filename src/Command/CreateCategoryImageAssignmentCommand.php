<?php

declare(strict_types=1);

namespace Maatify\Category\Command;

use Maatify\Category\DTO\CategoryIdDTO;
use Maatify\Category\DTO\CategoryImageAssignmentScopeDTO;

/** Validated command for assigning a host Media Asset to a Category scope. */
final readonly class CreateCategoryImageAssignmentCommand implements \JsonSerializable
{
    public int $categoryId;
    public int $mediaAssetId;
    public CategoryImageAssignmentScopeDTO $scope;
    public ?string $languageCode;
    public ?string $platform;

    public function __construct(
        int|string $categoryId,
        int|string $mediaAssetId,
        ?string $languageCode = null,
        ?string $platform = null,
    ) {
        $this->categoryId = (new CategoryIdDTO($categoryId, 'categoryId'))->value;
        $this->mediaAssetId = (new CategoryIdDTO($mediaAssetId, 'mediaAssetId'))->value;
        $this->scope = new CategoryImageAssignmentScopeDTO($languageCode, $platform);
        $this->languageCode = $this->scope->languageCode;
        $this->platform = $this->scope->platform;
    }

    /** @return array{categoryId: int, mediaAssetId: int, languageCode: ?string, platform: ?string} */
    public function jsonSerialize(): mixed
    {
        return [
            'categoryId' => $this->categoryId,
            'mediaAssetId' => $this->mediaAssetId,
            'languageCode' => $this->languageCode,
            'platform' => $this->platform,
        ];
    }
}
