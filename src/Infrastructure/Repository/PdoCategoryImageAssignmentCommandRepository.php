<?php

declare(strict_types=1);

namespace Maatify\Category\Infrastructure\Repository;

use DateTimeImmutable;
use DateTimeZone;
use Maatify\Category\Command\CreateCategoryImageAssignmentCommand;
use Maatify\Category\Command\RestoreCategoryImageAssignmentCommand;
use Maatify\Category\Command\SoftDeleteCategoryImageAssignmentCommand;
use Maatify\Category\Command\UpdateCategoryImageAssignmentDisplayOrderCommand;
use Maatify\Category\Contract\CategoryImageAssignmentCommandRepositoryInterface;
use Maatify\Category\Exception\CategoryImageAssignmentAlreadyExistsException;
use Maatify\Category\Exception\CategoryPersistenceException;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingConfig;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
use PDO;
use PDOException;

/** PDO write adapter for Category-owned Image Assignment references. */
final readonly class PdoCategoryImageAssignmentCommandRepository implements CategoryImageAssignmentCommandRepositoryInterface
{
    private const ASSIGNMENT_TABLE = 'maa_category_category_image_assignments';

    public function __construct(
        private PDO $pdo,
        private ScopedOrderingManager $orderingManager,
    ) {}

    public function create(CreateCategoryImageAssignmentCommand $command, DateTimeImmutable $occurredAt): int
    {
        $orderingScope = $this->orderingScope(
            $command->categoryId,
            $command->languageCode,
            $command->platform,
            $command->roleId,
        );
        $this->lockCreationScope($orderingScope);
        $displayOrder = $this->orderingManager->getNextPosition(
            $this->pdo,
            $this->orderingConfig(),
            $orderingScope,
        );

        $statement = $this->pdo->prepare(
            'INSERT INTO `' . self::ASSIGNMENT_TABLE . '` '
            . '(`category_id`, `media_asset_id`, `language_code`, `platform`, '
            . '`role_id`, '
            . '`display_order`, `created_at`, `updated_at`, `deleted_at`) '
            . 'VALUES (:category_id, :media_asset_id, :language_code, :platform, '
            . ':role_id, '
            . ':display_order, :created_at, :updated_at, NULL)',
        );
        $timestamp = $this->formatTimestamp($occurredAt);
        try {
            $statement->execute([
                'category_id' => $command->categoryId,
                'media_asset_id' => $command->mediaAssetId,
                'language_code' => $command->languageCode,
                'platform' => $command->platform,
                'role_id' => $command->roleId,
                'display_order' => $displayOrder,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        } catch (PDOException $exception) {
            $driverCode = $exception->errorInfo[1] ?? null;
            if ((is_int($driverCode) || is_string($driverCode)) && (int) $driverCode === 1062) {
                throw CategoryImageAssignmentAlreadyExistsException::withIdentity(
                    $command->categoryId,
                    $command->mediaAssetId,
                    $command->languageCode,
                    $command->platform,
                    $exception,
                    $command->roleId,
                );
            }

            throw $exception;
        }

        $id = $this->pdo->lastInsertId();
        if ($id === false || !ctype_digit($id) || (int) $id < 1) {
            throw CategoryPersistenceException::invalidImageAssignmentAutoIncrementIdentity();
        }

        return (int) $id;
    }

    public function updateDisplayOrder(
        UpdateCategoryImageAssignmentDisplayOrderCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool {
        $orderingScope = $this->activeOrderingScope($command->assignmentId);
        if ($orderingScope === false) {
            return false;
        }

        return $this->orderingManager->moveWithinScope(
            $this->pdo,
            $this->orderingConfig(),
            $orderingScope,
            $command->assignmentId,
            $command->displayOrder,
            $this->formatTimestamp($occurredAt),
        );
    }

    public function softDelete(
        SoftDeleteCategoryImageAssignmentCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool {
        $statement = $this->pdo->prepare(
            'UPDATE `' . self::ASSIGNMENT_TABLE . '` '
            . 'SET `deleted_at` = :deleted_at, `updated_at` = :updated_at '
            . 'WHERE `id` = :id AND `deleted_at` IS NULL',
        );
        $timestamp = $this->formatTimestamp($occurredAt);
        $statement->execute([
            'deleted_at' => $timestamp,
            'updated_at' => $timestamp,
            'id' => $command->assignmentId,
        ]);

        return $statement->rowCount() > 0;
    }

    public function restore(
        RestoreCategoryImageAssignmentCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool {
        $statement = $this->pdo->prepare(
            'UPDATE `' . self::ASSIGNMENT_TABLE . '` '
            . 'SET `deleted_at` = NULL, `updated_at` = :updated_at '
            . 'WHERE `id` = :id AND `deleted_at` IS NOT NULL',
        );
        $statement->execute([
            'updated_at' => $this->formatTimestamp($occurredAt),
            'id' => $command->assignmentId,
        ]);

        return $statement->rowCount() > 0;
    }

    private function activeOrderingScope(int $assignmentId): string|false
    {
        $statement = $this->pdo->prepare(
            'SELECT `ordering_scope` FROM `' . self::ASSIGNMENT_TABLE . '` '
            . 'WHERE `id` = :id AND `deleted_at` IS NULL LIMIT 1',
        );
        $statement->execute(['id' => $assignmentId]);
        $value = $statement->fetchColumn();

        if ($value === false) {
            return false;
        }
        if (!is_string($value)) {
            throw CategoryPersistenceException::unexpectedColumnType('ordering_scope');
        }

        return $value;
    }

    /** The service owns the transaction around this lock and insert. */
    private function lockCreationScope(string $orderingScope): void
    {
        $statement = $this->pdo->prepare(
            'SELECT `id` FROM `' . self::ASSIGNMENT_TABLE . '` '
            . 'WHERE `ordering_scope` = :ordering_scope FOR UPDATE',
        );
        $statement->execute(['ordering_scope' => $orderingScope]);
    }

    private function orderingConfig(): ScopedOrderingConfig
    {
        return new ScopedOrderingConfig(
            table: self::ASSIGNMENT_TABLE,
            scopeColumn: 'ordering_scope',
            idColumn: 'id',
            orderColumn: 'display_order',
            deletedAtColumn: 'deleted_at',
            nullableScope: false,
            updatedAtColumn: 'updated_at',
        );
    }

    private function orderingScope(
        int $categoryId,
        ?string $languageCode,
        ?string $platform,
        ?int $roleId,
    ): string
    {
        $language = $languageCode === null
            ? 'N:'
            : 'L' . mb_strlen($languageCode) . ':' . $languageCode;
        $platformValue = $platform === null
            ? 'N:'
            : 'L' . mb_strlen($platform) . ':' . $platform;

        $role = $roleId === null ? 'N:' : 'R' . $roleId;

        return 'C' . $categoryId . '|' . $language . '|' . $platformValue . '|' . $role;
    }

    private function formatTimestamp(DateTimeImmutable $occurredAt): string
    {
        return $occurredAt->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
}
