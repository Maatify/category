<?php

declare(strict_types=1);

namespace Maatify\Category\Infrastructure\Repository;

use DateTimeImmutable;
use DateTimeZone;
use Maatify\Category\Contract\CategoryManagementReadQueryInterface;
use Maatify\Category\DTO\CategoryCollectionDTO;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CategoryListCriteriaDTO;
use Maatify\Category\DTO\CategoryContentCollectionDTO;
use Maatify\Category\DTO\CategoryContentDTO;
use Maatify\Category\DTO\CategoryContentListCriteriaDTO;
use Maatify\Category\DTO\CategoryImageAssignmentCollectionDTO;
use Maatify\Category\DTO\CategoryImageAssignmentDTO;
use Maatify\Category\DTO\CategoryImageAssignmentListCriteriaDTO;
use Maatify\Category\DTO\CategoryContentFieldCollectionDTO;
use Maatify\Category\DTO\CategoryContentFieldDTO;
use Maatify\Category\DTO\CategoryContentFieldListCriteriaDTO;
use Maatify\Category\Enum\CategoryContentFieldFormatEnum;
use Maatify\Category\Enum\CategoryDeletedStateEnum;
use Maatify\Category\Enum\CategoryStatusEnum;
use Maatify\Category\Exception\CategoryPersistenceException;
use PDO;

/** PDO adapter for bounded management reads without consumer visibility rules. */
final readonly class PdoCategoryManagementReadQuery implements CategoryManagementReadQueryInterface
{
    private const CATEGORY_TABLE = 'maa_category_categories';
    private const CONTENT_TABLE = 'maa_category_category_contents';
    private const IMAGE_ASSIGNMENT_TABLE = 'maa_category_category_image_assignments';
    private const CONTENT_FIELD_TABLE = 'maa_category_category_content_fields';

    public function __construct(private PDO $pdo) {}

    public function findById(int $categoryId, CategoryDeletedStateEnum $deletedState): ?CategoryDTO
    {
        $where = ['`id` = :category_id'];
        $params = ['category_id' => $categoryId];
        $this->appendDeletedStateFilter($where, $params, $deletedState, 'category');

        $statement = $this->pdo->prepare(
            $this->categorySelect() . ' WHERE ' . implode(' AND ', $where) . ' LIMIT 1',
        );
        $statement->execute($params);
        /** @var array<string, mixed>|false $row */
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrateCategory($row) : null;
    }

    public function listCategories(CategoryListCriteriaDTO $criteria): CategoryCollectionDTO
    {
        return $this->listCategoriesWithWhere($criteria, []);
    }

    public function listRootCategories(CategoryListCriteriaDTO $criteria): CategoryCollectionDTO
    {
        return $this->listCategoriesWithWhere($criteria, ['`parent_id` IS NULL']);
    }

    public function listChildren(int $parentId, CategoryListCriteriaDTO $criteria): CategoryCollectionDTO
    {
        return $this->listCategoriesWithWhere($criteria, [
            '`parent_id` = :parent_id',
        ], ['parent_id' => $parentId]);
    }

    public function findContentById(
        int $contentId,
        CategoryDeletedStateEnum $deletedState,
    ): ?CategoryContentDTO {
        $where = ['`id` = :content_id'];
        $params = ['content_id' => $contentId];
        $this->appendDeletedStateFilter($where, $params, $deletedState, 'content');

        $statement = $this->pdo->prepare(
            $this->contentSelect() . ' WHERE ' . implode(' AND ', $where) . ' LIMIT 1',
        );
        $statement->execute($params);
        /** @var array<string, mixed>|false $row */
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrateContent($row) : null;
    }

    public function listContents(
        CategoryContentListCriteriaDTO $criteria,
    ): CategoryContentCollectionDTO {
        $where = [];
        /** @var array<string, int|string> $params */
        $params = [];
        if ($criteria->categoryId !== null) {
            $where[] = '`category_id` = :content_category_id';
            $params['content_category_id'] = $criteria->categoryId;
        }
        $this->appendDeletedStateFilter($where, $params, $criteria->deletedState, 'content');

        $statement = $this->pdo->prepare(
            $this->contentSelect()
            . ($where === [] ? '' : ' WHERE ' . implode(' AND ', $where))
            . ' ORDER BY `language_code` ASC, `id` ASC LIMIT :max_results',
        );
        $this->executeBounded($statement, $params, $criteria->maxResults);
        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->hydrateContent($row);
        }

        /** @var list<CategoryContentDTO> $items */
        return new CategoryContentCollectionDTO($items);
    }

    public function findImageAssignmentById(
        int $assignmentId,
        CategoryDeletedStateEnum $deletedState,
    ): ?CategoryImageAssignmentDTO {
        $where = ['`id` = :assignment_id'];
        $params = ['assignment_id' => $assignmentId];
        $this->appendDeletedStateFilter($where, $params, $deletedState, 'assignment');

        $statement = $this->pdo->prepare(
            $this->imageAssignmentSelect() . ' WHERE ' . implode(' AND ', $where) . ' LIMIT 1',
        );
        $statement->execute($params);
        /** @var array<string, mixed>|false $row */
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrateImageAssignment($row) : null;
    }

    public function listImageAssignments(
        CategoryImageAssignmentListCriteriaDTO $criteria,
    ): CategoryImageAssignmentCollectionDTO {
        $where = [];
        $params = [];
        if ($criteria->categoryId !== null) {
            $where[] = '`assignment`.`category_id` = :image_category_id';
            $params['image_category_id'] = $criteria->categoryId;
        }
        $this->appendDeletedStateFilter($where, $params, $criteria->deletedState, 'assignment');

        if ($criteria->scope !== null) {
            if ($criteria->scope->languageCode === null) {
                $where[] = '`assignment`.`language_code` IS NULL';
            } else {
                $where[] = '`assignment`.`language_code` = :image_language_code';
                $params['image_language_code'] = $criteria->scope->languageCode;
            }
            if ($criteria->scope->platform === null) {
                $where[] = '`assignment`.`platform` IS NULL';
            } else {
                $where[] = '`assignment`.`platform` = :image_platform';
                $params['image_platform'] = $criteria->scope->platform;
            }
        }

        $statement = $this->pdo->prepare(
            $this->imageAssignmentSelect()
            . ($where === [] ? '' : ' WHERE ' . implode(' AND ', $where))
            . ' ORDER BY `assignment`.`category_id` ASC, `assignment`.`ordering_scope` ASC, '
            . '`assignment`.`display_order` ASC, `assignment`.`id` ASC LIMIT :max_results',
        );
        $this->executeBounded($statement, $params, $criteria->maxResults);
        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->hydrateImageAssignment($row);
        }

        /** @var list<CategoryImageAssignmentDTO> $items */
        return new CategoryImageAssignmentCollectionDTO($items);
    }

    public function findContentFieldById(
        int $fieldId,
        CategoryDeletedStateEnum $deletedState,
    ): ?CategoryContentFieldDTO {
        $where = ['`id` = :field_id'];
        $params = ['field_id' => $fieldId];
        $this->appendDeletedStateFilter($where, $params, $deletedState, 'field');

        $statement = $this->pdo->prepare(
            $this->contentFieldSelect() . ' WHERE ' . implode(' AND ', $where) . ' LIMIT 1',
        );
        $statement->execute($params);
        /** @var array<string, mixed>|false $row */
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrateContentField($row) : null;
    }

    public function listContentFields(
        CategoryContentFieldListCriteriaDTO $criteria,
    ): CategoryContentFieldCollectionDTO {
        $where = [];
        $params = [];
        if ($criteria->categoryId !== null) {
            $where[] = '`field`.`category_id` = :field_category_id';
            $params['field_category_id'] = $criteria->categoryId;
        }
        if ($criteria->fieldKey !== null) {
            $where[] = '`field`.`field_key` = :field_key';
            $params['field_key'] = $criteria->fieldKey;
        }
        $this->appendDeletedStateFilter($where, $params, $criteria->deletedState, 'field');
        $this->appendContentFieldScopeFilter($where, $params, $criteria->scope);

        $statement = $this->pdo->prepare(
            $this->contentFieldSelect()
            . ($where === [] ? '' : ' WHERE ' . implode(' AND ', $where))
            . ' ORDER BY `field`.`category_id` ASC, `field`.`ordering_scope` ASC, '
            . '`field`.`field_key` ASC, `field`.`display_order` ASC, `field`.`id` ASC LIMIT :max_results',
        );
        $this->executeBounded($statement, $params, $criteria->maxResults);
        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->hydrateContentField($row);
        }

        /** @var list<CategoryContentFieldDTO> $items */
        return new CategoryContentFieldCollectionDTO($items);
    }

    /**
     * @param list<string> $where
     * @param array<string, int|string> $params
     */
    private function listCategoriesWithWhere(
        CategoryListCriteriaDTO $criteria,
        array $where,
        array $params = [],
    ): CategoryCollectionDTO {
        if ($criteria->status !== null) {
            $where[] = '`status` = :category_status';
            $params['category_status'] = $criteria->status->value;
        }
        $this->appendDeletedStateFilter($where, $params, $criteria->deletedState, 'category');

        $statement = $this->pdo->prepare(
            $this->categorySelect()
            . ($where === [] ? '' : ' WHERE ' . implode(' AND ', $where))
            . ' ORDER BY `display_order` ASC, `id` ASC LIMIT :max_results',
        );
        $this->executeBounded($statement, $params, $criteria->maxResults);
        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->hydrateCategory($row);
        }

        /** @var list<CategoryDTO> $items */
        return new CategoryCollectionDTO($items);
    }

    /**
     * @param list<string> $where
     * @param array<string, int|string> $params
     */
    private function appendDeletedStateFilter(
        array &$where,
        array &$params,
        CategoryDeletedStateEnum $deletedState,
        string $tableAlias,
    ): void {
        if ($deletedState === CategoryDeletedStateEnum::NON_DELETED) {
            $where[] = sprintf('`%s`.`deleted_at` IS NULL', $tableAlias);
        } elseif ($deletedState === CategoryDeletedStateEnum::DELETED_ONLY) {
            $where[] = sprintf('`%s`.`deleted_at` IS NOT NULL', $tableAlias);
        }
    }

    private function categorySelect(): string
    {
        return 'SELECT `id`, `parent_id`, `code`, `status`, `display_order`, '
            . '`created_at`, `updated_at`, `deleted_at` '
            . 'FROM `' . self::CATEGORY_TABLE . '` AS `category`';
    }

    private function contentSelect(): string
    {
        return 'SELECT `id`, `category_id`, `language_code`, `name`, `description`, '
            . '`created_at`, `updated_at`, `deleted_at` '
            . 'FROM `' . self::CONTENT_TABLE . '` AS `content`';
    }

    private function imageAssignmentSelect(): string
    {
        return 'SELECT `assignment`.`id`, `assignment`.`category_id`, '
            . '`assignment`.`media_asset_id`, `assignment`.`language_code`, `assignment`.`platform`, '
            . '`assignment`.`display_order`, `assignment`.`created_at`, '
            . '`assignment`.`updated_at`, `assignment`.`deleted_at` '
            . 'FROM `' . self::IMAGE_ASSIGNMENT_TABLE . '` AS `assignment`';
    }

    private function contentFieldSelect(): string
    {
        return 'SELECT `field`.`id`, `field`.`category_id`, `field`.`field_key`, '
            . '`field`.`language_code`, `field`.`platform`, `field`.`format`, `field`.`value`, '
            . '`field`.`display_order`, `field`.`ordering_scope`, `field`.`created_at`, '
            . '`field`.`updated_at`, `field`.`deleted_at` '
            . 'FROM `' . self::CONTENT_FIELD_TABLE . '` AS `field`';
    }

    /**
     * @param list<string> $where
     * @param array<string, int|string> $params
     */
    private function appendContentFieldScopeFilter(
        array &$where,
        array &$params,
        ?\Maatify\Category\DTO\CategoryContentFieldScopeDTO $scope,
    ): void {
        if ($scope === null) {
            return;
        }

        if ($scope->languageCode === null) {
            $where[] = '`field`.`language_code` IS NULL';
        } else {
            $where[] = '`field`.`language_code` = :field_language_code';
            $params['field_language_code'] = $scope->languageCode;
        }
        if ($scope->platform === null) {
            $where[] = '`field`.`platform` IS NULL';
        } else {
            $where[] = '`field`.`platform` = :field_platform';
            $params['field_platform'] = $scope->platform;
        }
    }

    /** @param array<string, int|string> $params */
    private function executeBounded(\PDOStatement $statement, array $params, int $maxResults): void
    {
        foreach ($params as $name => $value) {
            $statement->bindValue(
                ':' . $name,
                $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR,
            );
        }
        $statement->bindValue(':max_results', $maxResults, PDO::PARAM_INT);
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
    private function timestampValue(array $row, string $column): DateTimeImmutable
    {
        $value = $this->stringValue($row, $column);

        try {
            return new DateTimeImmutable($value, new DateTimeZone('UTC'));
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
            return new DateTimeImmutable($value, new DateTimeZone('UTC'));
        } catch (\Exception $exception) {
            throw CategoryPersistenceException::invalidStorageValue($column, $exception);
        }
    }
}
