<?php

declare(strict_types=1);

namespace Maatify\Category\ImageAssignment\Query\Infrastructure;

use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;
use Maatify\Category\Common\Exception\CategoryInvalidArgumentException;
use Maatify\Category\Common\Infrastructure\PdoReadQuerySupport;
use Maatify\Category\ImageAssignment\Query\Contract\CategoryImageAssignmentManagementReadQueryInterface;
use Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentCollectionDTO;
use Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentDTO;
use Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentListCriteriaDTO;
use Maatify\Category\ImageAssignment\Query\Enum\CategoryImageAssignmentRoleFilterModeEnum;
use Maatify\SharedCommon\Contracts\ClockInterface;
use PDO;

/** Dedicated PDO adapter for Image Assignment management reads. */
final readonly class PdoCategoryImageAssignmentManagementReadQuery implements CategoryImageAssignmentManagementReadQueryInterface
{
    use PdoReadQuerySupport;

    private const IMAGE_ASSIGNMENT_TABLE = 'maa_category_category_image_assignments';

    public function __construct(
        private PDO $pdo,
        private ClockInterface $clock,
    ) {}

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

        return is_array($row) ? $this->hydrateAssignment($row) : null;
    }

    public function listImageAssignments(
        CategoryImageAssignmentListCriteriaDTO $criteria,
    ): CategoryImageAssignmentCollectionDTO {
        $where = [];
        /** @var array<string, int|string> $params */
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

        switch ($criteria->roleFilter->mode) {
            case CategoryImageAssignmentRoleFilterModeEnum::OMITTED:
                break;
            case CategoryImageAssignmentRoleFilterModeEnum::EXACT_NULL:
                $where[] = '`assignment`.`role_id` IS NULL';
                break;
            case CategoryImageAssignmentRoleFilterModeEnum::CONCRETE:
                $roleId = $criteria->roleFilter->roleId;
                if ($roleId === null) {
                    throw CategoryInvalidArgumentException::invalidId('roleId');
                }
                $where[] = '`assignment`.`role_id` = :image_role_id';
                $params['image_role_id'] = $roleId;
                break;
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
            $items[] = $this->hydrateAssignment($row);
        }

        /** @var list<CategoryImageAssignmentDTO> $items */
        return new CategoryImageAssignmentCollectionDTO($items);
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

    private function imageAssignmentSelect(): string
    {
        return 'SELECT `assignment`.`id`, `assignment`.`category_id`, '
            . '`assignment`.`media_asset_id`, `assignment`.`role_id`, '
            . '`assignment`.`language_code`, `assignment`.`platform`, '
            . '`assignment`.`is_default`, `assignment`.`display_order`, '
            . '`assignment`.`created_at`, `assignment`.`updated_at`, '
            . '`assignment`.`deleted_at`, `assignment`.`ordering_scope` '
            . 'FROM `' . self::IMAGE_ASSIGNMENT_TABLE . '` AS `assignment`';
    }

    /** @param array<string, mixed> $row */
    private function hydrateAssignment(array $row): CategoryImageAssignmentDTO
    {
        return new CategoryImageAssignmentDTO(
            id: $this->integerValue($row, 'id'),
            categoryId: $this->integerValue($row, 'category_id'),
            mediaAssetId: $this->integerValue($row, 'media_asset_id'),
            roleId: $this->nullableIntegerValue($row, 'role_id'),
            languageCode: $this->nullableStringValue($row, 'language_code'),
            platform: $this->nullableStringValue($row, 'platform'),
            isDefault: $this->booleanValue($row, 'is_default'),
            displayOrder: $this->integerValue($row, 'display_order'),
            createdAt: $this->timestampValue($row, 'created_at', $this->clock),
            updatedAt: $this->timestampValue($row, 'updated_at', $this->clock),
            deletedAt: $this->nullableTimestampValue($row, 'deleted_at', $this->clock),
        );
    }
}
