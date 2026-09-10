<?php

declare(strict_types=1);

namespace Maatify\Category\Query\Infrastructure;

use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;
use Maatify\Category\Common\Exception\CategoryPersistenceException;
use Maatify\Category\Common\Infrastructure\PdoReadQuerySupport;
use Maatify\Category\Lifecycle\Enum\CategoryStatusEnum;
use Maatify\Category\Query\Contract\CategoryManagementReadQueryInterface;
use Maatify\Category\Query\DTO\CategoryCollectionDTO;
use Maatify\Category\Query\DTO\CategoryDTO;
use Maatify\Category\Query\DTO\CategoryListCriteriaDTO;
use Maatify\SharedCommon\Contracts\ClockInterface;
use PDO;

/** PDO adapter for bounded management reads without consumer visibility rules. */
final readonly class PdoCategoryManagementReadQuery implements CategoryManagementReadQueryInterface
{
    use PdoReadQuerySupport;

    private const CATEGORY_TABLE = 'maa_category_categories';

    public function __construct(
        private PDO $pdo,
        private ClockInterface $clock,
    ) {}

    /** Finds a Category using the requested explicit soft-deletion state. */
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

    /** Lists Categories in display-order/id order, bounded by the criteria. */
    public function listCategories(CategoryListCriteriaDTO $criteria): CategoryCollectionDTO
    {
        return $this->listCategoriesWithWhere($criteria, []);
    }

    /** Lists root Categories in display-order/id order, bounded by the criteria. */
    public function listRootCategories(CategoryListCriteriaDTO $criteria): CategoryCollectionDTO
    {
        return $this->listCategoriesWithWhere($criteria, ['`parent_id` IS NULL']);
    }

    /** Lists direct children in display-order/id order, bounded by the criteria. */
    public function listChildren(int $parentId, CategoryListCriteriaDTO $criteria): CategoryCollectionDTO
    {
        return $this->listCategoriesWithWhere($criteria, ['`parent_id` = :parent_id'], ['parent_id' => $parentId]);
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
            createdAt: $this->timestampValue($row, 'created_at', $this->clock),
            updatedAt: $this->timestampValue($row, 'updated_at', $this->clock),
            deletedAt: $this->nullableTimestampValue($row, 'deleted_at', $this->clock),
        );
    }
}
