<?php

declare(strict_types=1);

namespace Maatify\Category\DTO;

use DateTimeImmutable;
use Maatify\Category\Enum\CategoryStatusEnum;
use Maatify\Category\Exception\CategoryInvalidArgumentException;

final readonly class CategoryDTO
{
    public function __construct(
        public int $id,
        public ?int $parentId,
        public string $code,
        public CategoryStatusEnum $status,
        public int $displayOrder,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public ?DateTimeImmutable $deletedAt,
    ) {
        if ($id < 1) {
            throw CategoryInvalidArgumentException::nonPositiveId('id');
        }

        if ($parentId !== null && $parentId < 1) {
            throw CategoryInvalidArgumentException::nonPositiveId('parentId');
        }

        if ($parentId === $id) {
            throw CategoryInvalidArgumentException::selfParent($id);
        }
    }

}
