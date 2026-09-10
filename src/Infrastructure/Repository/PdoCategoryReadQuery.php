<?php

declare(strict_types=1);

namespace Maatify\Category\Infrastructure\Repository;

use DateTimeImmutable;
use Maatify\Category\Contract\CategoryReadQueryInterface;
use Maatify\Category\DTO\CategoryCollectionDTO;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CategoryContentCollectionDTO;
use Maatify\Category\DTO\CategoryContentDTO;
use Maatify\Category\DTO\CategoryVisibleListCriteriaDTO;
use Maatify\Category\DTO\CategoryImageAssignmentCollectionDTO;
use Maatify\Category\DTO\CategoryImageAssignmentDTO;
use Maatify\Category\DTO\CategoryImageAssignmentScopeDTO;
use Maatify\Category\DTO\CategoryContentFieldCollectionDTO;
use Maatify\Category\DTO\CategoryContentFieldDTO;
use Maatify\Category\DTO\CategoryContentFieldScopeDTO;
use Maatify\Category\Enum\CategoryContentFieldFormatEnum;
use Maatify\Category\Enum\CategoryStatusEnum;
use Maatify\Category\Exception\CategoryPersistenceException;
use Maatify\SharedCommon\Contracts\ClockInterface;
use PDO;
use PDOStatement;

/** Dedicated PDO adapter for visible Category query behavior. */
final readonly class PdoCategoryReadQuery implements CategoryReadQueryInterface
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

    public function findVisibleById(int $categoryId): ?CategoryDTO
    {
        $statement = $this->pdo->prepare(
            'WITH RECURSIVE `category_ancestors` AS ('
            . 'SELECT `id`, `parent_id`, `status`, `deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` '
            . 'WHERE `id` = :ancestor_category_id '
            . 'UNION ALL '
            . 'SELECT `parent`.`id`, `parent`.`parent_id`, `parent`.`status`, `parent`.`deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` AS `parent` '
            . 'INNER JOIN `category_ancestors` AS `child` '
            . 'ON `child`.`parent_id` = `parent`.`id`'
            . ') '
            . 'SELECT `id`, `parent_id`, `code`, `status`, `display_order`, '
            . '`created_at`, `updated_at`, `deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` AS `category` '
            . 'WHERE `category`.`id` = :visible_category_id '
            . 'AND `category`.`status` = \'active\' '
            . 'AND `category`.`deleted_at` IS NULL '
            . 'AND NOT EXISTS ('
            . 'SELECT 1 FROM `category_ancestors` AS `ancestor` '
            . 'WHERE `ancestor`.`status` <> \'active\' '
            . 'OR `ancestor`.`deleted_at` IS NOT NULL'
            . ') '
            . 'LIMIT 1',
        );
        $statement->execute([
            'ancestor_category_id' => $categoryId,
            'visible_category_id' => $categoryId,
        ]);
        /** @var array<string, mixed>|false $row */
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrateCategory($row) : null;
    }

    public function listVisibleRootCategories(
        CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO(),
    ): CategoryCollectionDTO
    {
        $statement = $this->pdo->prepare(
            'SELECT `id`, `parent_id`, `code`, `status`, `display_order`, '
            . '`created_at`, `updated_at`, `deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` '
            . 'WHERE `parent_id` IS NULL '
            . 'AND `status` = \'active\' '
            . 'AND `deleted_at` IS NULL '
            . 'ORDER BY `display_order` ASC, `id` ASC '
            . 'LIMIT :max_results',
        );
        if ($statement === false) {
            throw CategoryPersistenceException::queryFailed('visible root Categories');
        }
        $this->executeBounded($statement, $criteria);

        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $this->hydrateCategories($rows);
    }

    public function listVisibleChildren(
        int $parentId,
        CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO(),
    ): CategoryCollectionDTO
    {
        $statement = $this->pdo->prepare(
            'WITH RECURSIVE `category_ancestors` AS ('
            . 'SELECT `id`, `parent_id`, `status`, `deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` '
            . 'WHERE `id` = :ancestor_start_id '
            . 'UNION ALL '
            . 'SELECT `parent`.`id`, `parent`.`parent_id`, `parent`.`status`, `parent`.`deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` AS `parent` '
            . 'INNER JOIN `category_ancestors` AS `child` '
            . 'ON `child`.`parent_id` = `parent`.`id`'
            . ') '
            . 'SELECT `child`.`id`, `child`.`parent_id`, `child`.`code`, `child`.`status`, '
            . '`child`.`display_order`, `child`.`created_at`, `child`.`updated_at`, `child`.`deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` AS `child` '
            . 'WHERE `child`.`parent_id` = :children_parent_id '
            . 'AND `child`.`status` = \'active\' '
            . 'AND `child`.`deleted_at` IS NULL '
            . 'AND EXISTS ('
            . 'SELECT 1 FROM `category_ancestors` AS `requested_parent` '
            . 'WHERE `requested_parent`.`id` = :requested_parent_id'
            . ') '
            . 'AND NOT EXISTS ('
            . 'SELECT 1 FROM `category_ancestors` AS `ancestor` '
            . 'WHERE `ancestor`.`status` <> \'active\' '
            . 'OR `ancestor`.`deleted_at` IS NOT NULL'
            . ') '
            . 'ORDER BY `child`.`display_order` ASC, `child`.`id` ASC '
            . 'LIMIT :max_results',
        );
        $this->executeBounded(
            $statement,
            $criteria,
            [
                'ancestor_start_id' => $parentId,
                'children_parent_id' => $parentId,
                'requested_parent_id' => $parentId,
            ],
        );
        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $this->hydrateCategories($rows);
    }

    public function listVisibleContents(
        int $categoryId,
        CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO(),
    ): CategoryContentCollectionDTO
    {
        $statement = $this->pdo->prepare(
            'WITH RECURSIVE `category_ancestors` AS ('
            . 'SELECT `id`, `parent_id`, `status`, `deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` '
            . 'WHERE `id` = :content_ancestor_start_id '
            . 'UNION ALL '
            . 'SELECT `parent`.`id`, `parent`.`parent_id`, `parent`.`status`, `parent`.`deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` AS `parent` '
            . 'INNER JOIN `category_ancestors` AS `child` '
            . 'ON `child`.`parent_id` = `parent`.`id`'
            . ') '
            . 'SELECT `content`.`id`, `content`.`category_id`, '
            . '`content`.`language_code`, `content`.`name`, `content`.`description`, '
            . '`content`.`created_at`, `content`.`updated_at`, `content`.`deleted_at` '
            . 'FROM `' . self::CONTENT_TABLE . '` AS `content` '
            . 'WHERE `content`.`category_id` = :content_category_id '
            . 'AND `content`.`deleted_at` IS NULL '
            . 'AND EXISTS ('
            . 'SELECT 1 FROM `category_ancestors` AS `visible_category` '
            . 'WHERE `visible_category`.`id` = :visible_content_category_id'
            . ') '
            . 'AND NOT EXISTS ('
            . 'SELECT 1 FROM `category_ancestors` AS `ancestor` '
            . 'WHERE `ancestor`.`status` <> \'active\' '
            . 'OR `ancestor`.`deleted_at` IS NOT NULL'
            . ') '
            . 'ORDER BY `content`.`language_code` ASC, `content`.`id` ASC '
            . 'LIMIT :max_results',
        );
        $this->executeBounded(
            $statement,
            $criteria,
            [
                'content_ancestor_start_id' => $categoryId,
                'content_category_id' => $categoryId,
                'visible_content_category_id' => $categoryId,
            ],
        );
        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        return $this->hydrateContents($rows);
    }

    public function listVisibleImageAssignments(
        int $categoryId,
        CategoryImageAssignmentScopeDTO $scope,
        CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO(),
    ): CategoryImageAssignmentCollectionDTO {
        $scopeWhere = [];
        $scopeParams = [];
        if ($scope->languageCode === null) {
            $scopeWhere[] = '`assignment`.`language_code` IS NULL';
        } else {
            $scopeWhere[] = '`assignment`.`language_code` = :image_language_code';
            $scopeParams['image_language_code'] = $scope->languageCode;
        }
        if ($scope->platform === null) {
            $scopeWhere[] = '`assignment`.`platform` IS NULL';
        } else {
            $scopeWhere[] = '`assignment`.`platform` = :image_platform';
            $scopeParams['image_platform'] = $scope->platform;
        }
        if ($scope->roleId === null) {
            $scopeWhere[] = '`assignment`.`role_id` IS NULL';
        } else {
            $scopeWhere[] = '`assignment`.`role_id` = :image_role_id';
            $scopeParams['image_role_id'] = $scope->roleId;
        }

        $statement = $this->pdo->prepare(
            'WITH RECURSIVE `category_ancestors` AS ('
            . 'SELECT `id`, `parent_id`, `status`, `deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` '
            . 'WHERE `id` = :image_ancestor_start_id '
            . 'UNION ALL '
            . 'SELECT `parent`.`id`, `parent`.`parent_id`, `parent`.`status`, `parent`.`deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` AS `parent` '
            . 'INNER JOIN `category_ancestors` AS `child` '
            . 'ON `child`.`parent_id` = `parent`.`id`'
            . ') '
            . 'SELECT `assignment`.`id`, `assignment`.`category_id`, '
            . '`assignment`.`media_asset_id`, `assignment`.`role_id`, '
            . '`assignment`.`language_code`, `assignment`.`platform`, '
            . '`assignment`.`display_order`, `assignment`.`created_at`, '
            . '`assignment`.`updated_at`, `assignment`.`deleted_at` '
            . 'FROM `' . self::IMAGE_ASSIGNMENT_TABLE . '` AS `assignment` '
            . 'WHERE `assignment`.`category_id` = :image_category_id '
            . 'AND `assignment`.`deleted_at` IS NULL '
            . 'AND EXISTS ('
            . 'SELECT 1 FROM `category_ancestors` AS `visible_category` '
            . 'WHERE `visible_category`.`id` = :image_visible_category_id'
            . ') '
            . 'AND NOT EXISTS ('
            . 'SELECT 1 FROM `category_ancestors` AS `ancestor` '
            . 'WHERE `ancestor`.`status` <> \'active\' '
            . 'OR `ancestor`.`deleted_at` IS NOT NULL'
            . ') '
            . 'AND (`assignment`.`role_id` IS NULL OR EXISTS ('
            . 'SELECT 1 FROM `' . self::IMAGE_ROLE_TABLE . '` AS `role` '
            . 'WHERE `role`.`id` = `assignment`.`role_id` '
            . 'AND `role`.`status` = \'active\' '
            . 'AND `role`.`deleted_at` IS NULL'
            . ')) '
            . 'AND ' . implode(' AND ', $scopeWhere) . ' '
            . 'ORDER BY `assignment`.`display_order` ASC, `assignment`.`id` ASC '
            . 'LIMIT :max_results',
        );
        $this->executeBounded(
            $statement,
            $criteria,
            array_merge(
                [
                    'image_ancestor_start_id' => $categoryId,
                    'image_category_id' => $categoryId,
                    'image_visible_category_id' => $categoryId,
                ],
                $scopeParams,
            ),
        );
        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->hydrateImageAssignment($row);
        }

        /** @var list<CategoryImageAssignmentDTO> $items */
        return new CategoryImageAssignmentCollectionDTO($items);
    }

    public function listVisibleContentFields(
        int $categoryId,
        CategoryContentFieldScopeDTO $scope,
        CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO(),
    ): CategoryContentFieldCollectionDTO {
        $scopeWhere = [];
        $scopeParams = [];
        if ($scope->languageCode === null) {
            $scopeWhere[] = '`field`.`language_code` IS NULL';
        } else {
            $scopeWhere[] = '`field`.`language_code` = :field_language_code';
            $scopeParams['field_language_code'] = $scope->languageCode;
        }
        if ($scope->platform === null) {
            $scopeWhere[] = '`field`.`platform` IS NULL';
        } else {
            $scopeWhere[] = '`field`.`platform` = :field_platform';
            $scopeParams['field_platform'] = $scope->platform;
        }

        $statement = $this->pdo->prepare(
            'WITH RECURSIVE `category_ancestors` AS ('
            . 'SELECT `id`, `parent_id`, `status`, `deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` '
            . 'WHERE `id` = :field_ancestor_start_id '
            . 'UNION ALL '
            . 'SELECT `parent`.`id`, `parent`.`parent_id`, `parent`.`status`, `parent`.`deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` AS `parent` '
            . 'INNER JOIN `category_ancestors` AS `child` '
            . 'ON `child`.`parent_id` = `parent`.`id`'
            . ') '
            . 'SELECT `field`.`id`, `field`.`category_id`, `field`.`field_key`, '
            . '`field`.`language_code`, `field`.`platform`, `field`.`format`, `field`.`value`, '
            . '`field`.`display_order`, `field`.`created_at`, `field`.`updated_at`, `field`.`deleted_at` '
            . 'FROM `' . self::CONTENT_FIELD_TABLE . '` AS `field` '
            . 'WHERE `field`.`category_id` = :field_category_id '
            . 'AND `field`.`deleted_at` IS NULL '
            . 'AND EXISTS ('
            . 'SELECT 1 FROM `category_ancestors` AS `visible_category` '
            . 'WHERE `visible_category`.`id` = :visible_field_category_id'
            . ') '
            . 'AND NOT EXISTS ('
            . 'SELECT 1 FROM `category_ancestors` AS `ancestor` '
            . 'WHERE `ancestor`.`status` <> \'active\' '
            . 'OR `ancestor`.`deleted_at` IS NOT NULL'
            . ') '
            . 'AND ' . implode(' AND ', $scopeWhere) . ' '
            . 'ORDER BY `field`.`display_order` ASC, `field`.`id` ASC '
            . 'LIMIT :max_results',
        );
        $this->executeBounded(
            $statement,
            $criteria,
            array_merge(
                [
                    'field_ancestor_start_id' => $categoryId,
                    'field_category_id' => $categoryId,
                    'visible_field_category_id' => $categoryId,
                ],
                $scopeParams,
            ),
        );
        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->hydrateContentField($row);
        }

        /** @var list<CategoryContentFieldDTO> $items */
        return new CategoryContentFieldCollectionDTO($items);
    }

    /** @param array<string, int|string> $params */
    private function executeBounded(
        PDOStatement $statement,
        CategoryVisibleListCriteriaDTO $criteria,
        array $params = [],
    ): void {
        foreach ($params as $name => $value) {
            $statement->bindValue(
                ':' . $name,
                $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR,
            );
        }
        $statement->bindValue(':max_results', $criteria->maxResults, PDO::PARAM_INT);
        $statement->execute();
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

    /** @param list<array<string, mixed>> $rows */
    private function hydrateCategories(array $rows): CategoryCollectionDTO
    {
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->hydrateCategory($row);
        }

        /** @var list<CategoryDTO> $items */
        return new CategoryCollectionDTO($items);
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

    /** @param list<array<string, mixed>> $rows */
    private function hydrateContents(array $rows): CategoryContentCollectionDTO
    {
        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->hydrateContent($row);
        }

        /** @var list<CategoryContentDTO> $items */
        return new CategoryContentCollectionDTO($items);
    }

    /** @param array<string, mixed> $row */
    private function hydrateImageAssignment(array $row): CategoryImageAssignmentDTO
    {
        return new CategoryImageAssignmentDTO(
            id: $this->integerValue($row, 'id'),
            categoryId: $this->integerValue($row, 'category_id'),
            mediaAssetId: $this->integerValue($row, 'media_asset_id'),
            roleId: $this->nullableIntegerValue($row, 'role_id'),
            languageCode: $this->nullableStringValue($row, 'language_code'),
            platform: $this->nullableStringValue($row, 'platform'),
            displayOrder: $this->integerValue($row, 'display_order'),
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
            $fieldFormat = CategoryContentFieldFormatEnum::from($format);
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
