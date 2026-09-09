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
use Maatify\Category\Enum\CategoryDeletedStateEnum;
use Maatify\Category\Enum\CategoryStatusEnum;
use Maatify\Category\Exception\CategoryPersistenceException;
use PDO;

/** PDO adapter for bounded management reads without consumer visibility rules. */
final readonly class PdoCategoryManagementReadQuery implements CategoryManagementReadQueryInterface
{
    private const CATEGORY_TABLE = 'maa_category_categories';
    private const CONTENT_TABLE = 'maa_category_category_contents';

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
