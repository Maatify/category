<?php

declare(strict_types=1);

namespace Maatify\Category\ContentField\Query\Infrastructure;

use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;
use Maatify\Category\Common\Exception\CategoryPersistenceException;
use Maatify\Category\Common\Infrastructure\PdoReadQuerySupport;
use Maatify\Category\ContentField\CategoryContentFieldFormatEnum;
use Maatify\Category\ContentField\Query\Contract\CategoryContentFieldManagementReadQueryInterface;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldCollectionDTO;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldDTO;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldListCriteriaDTO;
use Maatify\SharedCommon\Contracts\ClockInterface;
use PDO;

/** Dedicated PDO adapter for Content Field management reads. */
final readonly class PdoCategoryContentFieldManagementReadQuery extends PdoReadQuerySupport implements CategoryContentFieldManagementReadQueryInterface
{
    private const CONTENT_FIELD_TABLE = 'maa_category_category_content_fields';

    public function __construct(
        private PDO $pdo,
        private ClockInterface $clock,
    ) {}

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

        return is_array($row) ? $this->hydrateField($row) : null;
    }

    public function listContentFields(
        CategoryContentFieldListCriteriaDTO $criteria,
    ): CategoryContentFieldCollectionDTO {
        $where = [];
        /** @var array<string, int|string> $params */
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
        $this->appendScopeFilter($where, $params, $criteria->scope);

        $statement = $this->pdo->prepare(
            $this->contentFieldSelect()
            . ($where === [] ? '' : ' WHERE ' . implode(' AND ', $where))
            . ' ORDER BY `field`.`category_id` ASC, `field`.`ordering_scope` ASC, '
            . '`field`.`display_order` ASC, `field`.`id` ASC LIMIT :max_results',
        );
        $this->executeBounded($statement, $params, $criteria->maxResults);
        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->hydrateField($row);
        }

        /** @var list<CategoryContentFieldDTO> $items */
        return new CategoryContentFieldCollectionDTO($items);
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

    /**
     * @param list<string> $where
     * @param array<string, int|string> $params
     */
    private function appendScopeFilter(
        array &$where,
        array &$params,
        ?\Maatify\Category\ContentField\CategoryContentFieldScopeDTO $scope,
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

    private function contentFieldSelect(): string
    {
        return 'SELECT `field`.`id`, `field`.`category_id`, `field`.`field_key`, '
            . '`field`.`language_code`, `field`.`platform`, `field`.`format`, `field`.`value`, '
            . '`field`.`display_order`, `field`.`ordering_scope`, `field`.`created_at`, '
            . '`field`.`updated_at`, `field`.`deleted_at` '
            . 'FROM `' . self::CONTENT_FIELD_TABLE . '` AS `field`';
    }

    /** @param array<string, mixed> $row */
    private function hydrateField(array $row): CategoryContentFieldDTO
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
            createdAt: $this->timestampValue($row, 'created_at', $this->clock),
            updatedAt: $this->timestampValue($row, 'updated_at', $this->clock),
            deletedAt: $this->nullableTimestampValue($row, 'deleted_at', $this->clock),
        );
    }
}
