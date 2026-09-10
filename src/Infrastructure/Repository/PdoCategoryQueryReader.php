<?php

declare(strict_types=1);

namespace Maatify\Category\Infrastructure\Repository;

use DateTimeImmutable;
use Maatify\Category\Contract\CategoryQueryReaderInterface;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CategoryContentDTO;
use Maatify\Category\DTO\CategoryImageAssignmentDTO;
use Maatify\Category\DTO\CategoryImageRoleDTO;
use Maatify\Category\Enum\CategoryImageRoleStatusEnum;
use Maatify\Category\DTO\CategoryContentFieldDTO;
use Maatify\Category\Enum\CategoryStatusEnum;
use Maatify\Category\Exception\CategoryPersistenceException;
use Maatify\SharedCommon\Contracts\ClockInterface;
use PDO;

/** PDO read adapter with explicit active/all-state and locking semantics. */
final readonly class PdoCategoryQueryReader implements CategoryQueryReaderInterface
{
    private const CATEGORY_TABLE = 'maa_category_categories';
    private const CONTENT_TABLE = 'maa_category_category_contents';
    private const IMAGE_ASSIGNMENT_TABLE = 'maa_category_category_image_assignments';
    private const IMAGE_ROLE_TABLE = 'maa_category_category_image_roles';
    private const CONTENT_FIELD_TABLE = 'maa_category_category_content_fields';

    public function __construct(
        private PDO $pdo,
        private ClockInterface $clock,
    ) {}

    public function findById(int $categoryId): ?CategoryDTO
    {
        return $this->findCategory($categoryId, false, false);
    }

    public function findByCode(string $code): ?CategoryDTO
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `parent_id`, `code`, `status`, `display_order`, '
            . '`created_at`, `updated_at`, `deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` '
            . 'WHERE `code` = :code LIMIT 1',
        );
        $statement->execute(['code' => $code]);
        /** @var array<string, mixed>|false $row */
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrateCategory($row) : null;
    }

    public function findActiveById(int $categoryId): ?CategoryDTO
    {
        return $this->findCategory($categoryId, true, false);
    }

    public function findActiveByIdForUpdate(int $categoryId): ?CategoryDTO
    {
        return $this->findCategory($categoryId, true, true);
    }

    public function findByIdForUpdate(int $categoryId): ?CategoryDTO
    {
        return $this->findCategory($categoryId, false, true);
    }

    public function hasNonDeletedChildrenForUpdate(int $categoryId): bool
    {
        $statement = $this->pdo->prepare(
            'SELECT `id` FROM `' . self::CATEGORY_TABLE . '` '
            . 'WHERE `parent_id` = :parent_id AND `deleted_at` IS NULL '
            . 'FOR UPDATE',
        );
        $statement->execute(['parent_id' => $categoryId]);

        return $statement->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function findContentById(int $contentId): ?CategoryContentDTO
    {
        return $this->findContent($contentId, false);
    }

    public function findContentByIdForUpdate(int $contentId): ?CategoryContentDTO
    {
        return $this->findContent($contentId, true);
    }

    public function findImageAssignmentById(int $assignmentId): ?CategoryImageAssignmentDTO
    {
        return $this->findImageAssignment($assignmentId, false);
    }

    public function findImageAssignmentByIdForUpdate(int $assignmentId): ?CategoryImageAssignmentDTO
    {
        return $this->findImageAssignment($assignmentId, true);
    }

    public function findImageRoleByIdForUpdate(int $roleId): ?CategoryImageRoleDTO
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `role_key`, `status`, `created_at`, `updated_at`, `deleted_at` '
            . 'FROM `' . self::IMAGE_ROLE_TABLE . '` '
            . 'WHERE `id` = :id LIMIT 1 FOR UPDATE',
        );
        $statement->execute(['id' => $roleId]);
        /** @var array<string, mixed>|false $row */
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrateImageRole($row) : null;
    }

    public function findContentFieldById(int $fieldId): ?CategoryContentFieldDTO
    {
        return $this->findContentField($fieldId, false);
    }

    public function findContentFieldByIdForUpdate(int $fieldId): ?CategoryContentFieldDTO
    {
        return $this->findContentField($fieldId, true);
    }

    private function findContent(int $contentId, bool $forUpdate): ?CategoryContentDTO
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `category_id`, `language_code`, `name`, `description`, '
            . '`created_at`, `updated_at`, `deleted_at` '
            . 'FROM `' . self::CONTENT_TABLE . '` '
            . 'WHERE `id` = :id LIMIT 1'
            . ($forUpdate ? ' FOR UPDATE' : ''),
        );
        $statement->execute(['id' => $contentId]);
        /** @var array<string, mixed>|false $row */
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrateContent($row) : null;
    }

    private function findImageAssignment(int $assignmentId, bool $forUpdate): ?CategoryImageAssignmentDTO
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `category_id`, `media_asset_id`, `role_id`, `language_code`, `platform`, '
            . '`display_order`, `created_at`, `updated_at`, `deleted_at` '
            . 'FROM `' . self::IMAGE_ASSIGNMENT_TABLE . '` '
            . 'WHERE `id` = :id LIMIT 1'
            . ($forUpdate ? ' FOR UPDATE' : ''),
        );
        $statement->execute(['id' => $assignmentId]);
        /** @var array<string, mixed>|false $row */
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrateImageAssignment($row) : null;
    }

    private function findContentField(int $fieldId, bool $forUpdate): ?CategoryContentFieldDTO
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `category_id`, `field_key`, `language_code`, `platform`, `format`, `value`, '
            . '`display_order`, `created_at`, `updated_at`, `deleted_at` '
            . 'FROM `' . self::CONTENT_FIELD_TABLE . '` '
            . 'WHERE `id` = :id LIMIT 1'
            . ($forUpdate ? ' FOR UPDATE' : ''),
        );
        $statement->execute(['id' => $fieldId]);
        /** @var array<string, mixed>|false $row */
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrateContentField($row) : null;
    }

    private function findCategory(int $categoryId, bool $activeOnly, bool $forUpdate): ?CategoryDTO
    {
        $where = '`id` = :id';
        if ($activeOnly) {
            $where .= ' AND `deleted_at` IS NULL';
        }

        $statement = $this->pdo->prepare(
            'SELECT `id`, `parent_id`, `code`, `status`, `display_order`, '
            . '`created_at`, `updated_at`, `deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` '
            . 'WHERE ' . $where . ' LIMIT 1'
            . ($forUpdate ? ' FOR UPDATE' : ''),
        );
        $statement->execute(['id' => $categoryId]);
        /** @var array<string, mixed>|false $row */
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrateCategory($row) : null;
    }

    /** @param array<string, mixed> $row */
    private function hydrateCategory(array $row): CategoryDTO
    {
        $status = $row['status'] ?? null;
        if (!is_string($status)) {
            throw CategoryPersistenceException::unexpectedColumnType('status');
        }

        $parentId = $row['parent_id'] ?? null;
        if ($parentId !== null && !is_int($parentId) && !is_string($parentId)) {
            throw CategoryPersistenceException::unexpectedColumnType('parent_id');
        }
        try {
            $categoryStatus = CategoryStatusEnum::from($status);
        } catch (\ValueError $exception) {
            throw CategoryPersistenceException::invalidStorageValue('status', $exception);
        }

        return new CategoryDTO(
            id: $this->integerValue($row, 'id'),
            parentId: $parentId === null ? null : (int) $parentId,
            code: $this->stringValue($row, 'code'),
            status: $categoryStatus,
            displayOrder: $this->integerValue($row, 'display_order'),
            createdAt: $this->timestampValue($row, 'created_at'),
            updatedAt: $this->timestampValue($row, 'updated_at'),
            deletedAt: $this->nullableTimestampValue($row, 'deleted_at'),
        );
    }

    /** @param array<string, mixed> $row */
    private function hydrateContent(array $row): CategoryContentDTO
    {
        return new CategoryContentDTO(
            id: $this->integerValue($row, 'id'),
            categoryId: $this->integerValue($row, 'category_id'),
            languageCode: $this->nullableStringValue($row, 'language_code'),
            name: $this->stringValue($row, 'name'),
            description: $this->nullableStringValue($row, 'description'),
            createdAt: $this->timestampValue($row, 'created_at'),
            updatedAt: $this->timestampValue($row, 'updated_at'),
            deletedAt: $this->nullableTimestampValue($row, 'deleted_at'),
        );
    }

    /** @param array<string, mixed> $row */
    private function hydrateImageAssignment(array $row): CategoryImageAssignmentDTO
    {
        return new CategoryImageAssignmentDTO(
            id: $this->integerValue($row, 'id'),
            categoryId: $this->integerValue($row, 'category_id'),
            mediaAssetId: $this->integerValue($row, 'media_asset_id'),
            languageCode: $this->nullableStringValue($row, 'language_code'),
            platform: $this->nullableStringValue($row, 'platform'),
            displayOrder: $this->integerValue($row, 'display_order'),
            createdAt: $this->timestampValue($row, 'created_at'),
            updatedAt: $this->timestampValue($row, 'updated_at'),
            deletedAt: $this->nullableTimestampValue($row, 'deleted_at'),
            roleId: $this->nullableIntegerValue($row, 'role_id'),
        );
    }

    /** @param array<string, mixed> $row */
    private function hydrateImageRole(array $row): CategoryImageRoleDTO
    {
        $status = $this->stringValue($row, 'status');
        try {
            $roleStatus = CategoryImageRoleStatusEnum::from($status);
        } catch (\ValueError $exception) {
            throw CategoryPersistenceException::invalidStorageValue('status', $exception);
        }

        return new CategoryImageRoleDTO(
            id: $this->integerValue($row, 'id'),
            roleKey: $this->stringValue($row, 'role_key'),
            status: $roleStatus,
            createdAt: $this->timestampValue($row, 'created_at'),
            updatedAt: $this->timestampValue($row, 'updated_at'),
            deletedAt: $this->nullableTimestampValue($row, 'deleted_at'),
        );
    }

    /** @param array<string, mixed> $row */
    private function hydrateContentField(array $row): CategoryContentFieldDTO
    {
        $format = $this->stringValue($row, 'format');
        try {
            $fieldFormat = \Maatify\Category\Enum\CategoryContentFieldFormatEnum::from($format);
        } catch (\ValueError $exception) {
            throw CategoryPersistenceException::invalidStorageValue('format', $exception);
        }

        return new CategoryContentFieldDTO(
            id: $this->integerValue($row, 'id'),
            categoryId: $this->integerValue($row, 'category_id'),
            fieldKey: $this->stringValue($row, 'field_key'),
            languageCode: $this->nullableStringValue($row, 'language_code'),
            platform: $this->nullableStringValue($row, 'platform'),
            format: $fieldFormat,
            value: $this->stringValue($row, 'value'),
            displayOrder: $this->integerValue($row, 'display_order'),
            createdAt: $this->timestampValue($row, 'created_at'),
            updatedAt: $this->timestampValue($row, 'updated_at'),
            deletedAt: $this->nullableTimestampValue($row, 'deleted_at'),
        );
    }

    /** @param array<string, mixed> $row */
    private function integerValue(array $row, string $column): int
    {
        $value = $row[$column] ?? null;
        if (!is_int($value) && !is_string($value)) {
            throw CategoryPersistenceException::unexpectedColumnType($column);
        }

        return (int) $value;
    }

    /** @param array<string, mixed> $row */
    private function stringValue(array $row, string $column): string
    {
        $value = $row[$column] ?? null;
        if (!is_string($value)) {
            throw CategoryPersistenceException::unexpectedColumnType($column);
        }

        return $value;
    }

    /** @param array<string, mixed> $row */
    private function nullableStringValue(array $row, string $column): ?string
    {
        $value = $row[$column] ?? null;
        if ($value !== null && !is_string($value)) {
            throw CategoryPersistenceException::unexpectedColumnType($column);
        }

        return $value;
    }

    /** @param array<string, mixed> $row */
    private function nullableIntegerValue(array $row, string $column): ?int
    {
        $value = $row[$column] ?? null;
        if ($value === null) {
            return null;
        }
        if (!is_int($value) && !is_string($value)) {
            throw CategoryPersistenceException::unexpectedColumnType($column);
        }

        return (int) $value;
    }

    /** @param array<string, mixed> $row */
    private function timestampValue(array $row, string $column): DateTimeImmutable
    {
        $value = $this->stringValue($row, $column);

        try {
            return new DateTimeImmutable($value, $this->clock->getTimezone());
        } catch (\Exception $exception) {
            throw CategoryPersistenceException::invalidStorageValue($column, $exception);
        }
    }

    /** @param array<string, mixed> $row */
    private function nullableTimestampValue(array $row, string $column): ?DateTimeImmutable
    {
        $value = $row[$column] ?? null;
        if ($value === null) {
            return null;
        }
        if (!is_string($value)) {
            throw CategoryPersistenceException::unexpectedColumnType($column);
        }

        try {
            return new DateTimeImmutable($value, $this->clock->getTimezone());
        } catch (\Exception $exception) {
            throw CategoryPersistenceException::invalidStorageValue($column, $exception);
        }
    }
}
