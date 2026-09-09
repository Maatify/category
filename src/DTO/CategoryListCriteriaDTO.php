<?php

declare(strict_types=1);

namespace Maatify\Category\DTO;

use Maatify\Category\Enum\CategoryDeletedStateEnum;
use Maatify\Category\Enum\CategoryStatusEnum;
use Maatify\Category\Exception\CategoryInvalidArgumentException;

/** Typed, bounded criteria for management Category list reads. */
final readonly class CategoryListCriteriaDTO implements \JsonSerializable
{
    public const DEFAULT_MAX_RESULTS = 100;
    public const MAX_MAX_RESULTS = 100;

    public function __construct(
        public ?CategoryStatusEnum $status = null,
        public CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
        public int $maxResults = self::DEFAULT_MAX_RESULTS,
    ) {
        if ($maxResults < 1 || $maxResults > self::MAX_MAX_RESULTS) {
            throw CategoryInvalidArgumentException::invalidListLimit($maxResults, self::MAX_MAX_RESULTS);
        }
    }

    /** @return array{status: ?string, deletedState: string, maxResults: int} */
    public function jsonSerialize(): mixed
    {
        return [
            'status' => $this->status?->value,
            'deletedState' => $this->deletedState->value,
            'maxResults' => $this->maxResults,
        ];
    }
}
