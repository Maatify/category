<?php

declare(strict_types=1);

namespace Maatify\Category\ImageRole\Query\Infrastructure;

use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;
use Maatify\Category\Common\Exception\CategoryPersistenceException;
use Maatify\Category\Common\Infrastructure\PdoReadQuerySupport;
use Maatify\Category\ImageRole\CategoryImageRoleDTO;
use Maatify\Category\ImageRole\Lifecycle\Enum\CategoryImageRoleStatusEnum;
use Maatify\Category\ImageRole\Query\Contract\CategoryImageRoleManagementReadQueryInterface;
use Maatify\Category\ImageRole\Query\DTO\CategoryImageRoleCollectionDTO;
use Maatify\Category\ImageRole\Query\DTO\CategoryImageRoleListCriteriaDTO;
use Maatify\SharedCommon\Contracts\ClockInterface;
use PDO;

/** Dedicated PDO adapter for Image Role management reads. */
final readonly class PdoCategoryImageRoleManagementReadQuery extends PdoReadQuerySupport implements CategoryImageRoleManagementReadQueryInterface
{
    private const IMAGE_ROLE_TABLE = 'maa_category_category_image_roles';

    public function __construct(
        private PDO $pdo,
        private ClockInterface $clock,
    ) {}

    public function findImageRoleById(
        int $roleId,
        CategoryDeletedStateEnum $deletedState,
    ): ?CategoryImageRoleDTO {
        $where = ['`id` = :role_id'];
        $params = ['role_id' => $roleId];
        $this->appendDeletedStateFilter($where, $params, $deletedState, 'role');

        $statement = $this->pdo->prepare(
            $this->imageRoleSelect() . ' WHERE ' . implode(' AND ', $where) . ' LIMIT 1',
        );
        $statement->execute($params);
        /** @var array<string, mixed>|false $row */
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrateRole($row) : null;
    }

    public function findImageRoleByKey(
        string $roleKey,
        CategoryDeletedStateEnum $deletedState,
    ): ?CategoryImageRoleDTO {
        $where = ['`role_key` = :role_key'];
        $params = ['role_key' => $roleKey];
        $this->appendDeletedStateFilter($where, $params, $deletedState, 'role');

        $statement = $this->pdo->prepare(
            $this->imageRoleSelect() . ' WHERE ' . implode(' AND ', $where) . ' LIMIT 1',
        );
        $statement->execute($params);
        /** @var array<string, mixed>|false $row */
        $row = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($row) ? $this->hydrateRole($row) : null;
    }

    public function listImageRoles(CategoryImageRoleListCriteriaDTO $criteria): CategoryImageRoleCollectionDTO
    {
        $where = [];
        /** @var array<string, int|string> $params */
        $params = [];
        if ($criteria->status !== null) {
            $where[] = '`role`.`status` = :role_status';
            $params['role_status'] = $criteria->status->value;
        }
        $this->appendDeletedStateFilter($where, $params, $criteria->deletedState, 'role');

        $statement = $this->pdo->prepare(
            $this->imageRoleSelect()
            . ($where === [] ? '' : ' WHERE ' . implode(' AND ', $where))
            . ' ORDER BY BINARY `role`.`role_key` ASC, `role`.`id` ASC LIMIT :max_results',
        );
        $this->executeBounded($statement, $params, $criteria->maxResults);
        /** @var list<array<string, mixed>> $rows */
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($rows as $row) {
            $items[] = $this->hydrateRole($row);
        }

        /** @var list<CategoryImageRoleDTO> $items */
        return new CategoryImageRoleCollectionDTO($items);
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

    private function imageRoleSelect(): string
    {
        return 'SELECT `role`.`id`, `role`.`role_key`, `role`.`status`, '
            . '`role`.`created_at`, `role`.`updated_at`, `role`.`deleted_at` '
            . 'FROM `' . self::IMAGE_ROLE_TABLE . '` AS `role`';
    }

    /** @param array<string, mixed> $row */
    private function hydrateRole(array $row): CategoryImageRoleDTO
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
            createdAt: $this->timestampValue($row, 'created_at', $this->clock),
            updatedAt: $this->timestampValue($row, 'updated_at', $this->clock),
            deletedAt: $this->nullableTimestampValue($row, 'deleted_at', $this->clock),
        );
    }
}
