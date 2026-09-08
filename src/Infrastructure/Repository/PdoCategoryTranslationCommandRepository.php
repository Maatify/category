<?php

declare(strict_types=1);

namespace Maatify\Category\Infrastructure\Repository;

use DateTimeImmutable;
use Maatify\Category\Contract\CategoryTranslationCommandRepositoryInterface;
use Maatify\Category\Command\CreateCategoryTranslationCommand;
use Maatify\Category\Command\RestoreCategoryTranslationCommand;
use Maatify\Category\Command\SoftDeleteCategoryTranslationCommand;
use Maatify\Category\Command\UpdateCategoryTranslationCommand;
use Maatify\Category\Exception\CategoryPersistenceException;
use Maatify\Category\Exception\CategoryTranslationAlreadyExistsException;
use PDO;
use PDOException;

/** PDO write adapter for Category translation content. */
final readonly class PdoCategoryTranslationCommandRepository implements CategoryTranslationCommandRepositoryInterface
{
    private const TRANSLATION_TABLE = 'maa_category_category_translations';

    public function __construct(private PDO $pdo) {}

    public function create(CreateCategoryTranslationCommand $command, DateTimeImmutable $occurredAt): int
    {
        try {
            $statement = $this->pdo->prepare(
                'INSERT INTO `' . self::TRANSLATION_TABLE . '` '
                . '(`category_id`, `language_code`, `name`, `description`, '
                . '`created_at`, `updated_at`, `deleted_at`) '
                . 'VALUES (:category_id, :language_code, :name, :description, '
                . ':created_at, :updated_at, NULL)',
            );
            $timestamp = $this->formatTimestamp($occurredAt);
            $statement->execute([
                'category_id' => $command->categoryId,
                'language_code' => $command->languageCode,
                'name' => $command->name,
                'description' => $command->description,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        } catch (PDOException $exception) {
            $driverCode = $exception->errorInfo[1] ?? null;
            if ((is_int($driverCode) || is_string($driverCode)) && (int) $driverCode === 1062) {
                throw CategoryTranslationAlreadyExistsException::withIdentity(
                    $command->categoryId,
                    $command->languageCode,
                    $exception,
                );
            }

            throw $exception;
        }

        $id = $this->pdo->lastInsertId();
        if ($id === false || !ctype_digit($id) || (int) $id < 1) {
            throw CategoryPersistenceException::invalidTranslationAutoIncrementIdentity();
        }

        return (int) $id;
    }

    public function update(UpdateCategoryTranslationCommand $command, DateTimeImmutable $occurredAt): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE `' . self::TRANSLATION_TABLE . '` '
            . 'SET `name` = :name, `description` = :description, `updated_at` = :updated_at '
            . 'WHERE `id` = :id AND `deleted_at` IS NULL',
        );
        $statement->execute([
            'name' => $command->name,
            'description' => $command->description,
            'updated_at' => $this->formatTimestamp($occurredAt),
            'id' => $command->translationId,
        ]);

        return $statement->rowCount() > 0;
    }

    public function softDelete(
        SoftDeleteCategoryTranslationCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool {
        $statement = $this->pdo->prepare(
            'UPDATE `' . self::TRANSLATION_TABLE . '` '
            . 'SET `deleted_at` = :deleted_at, `updated_at` = :updated_at '
            . 'WHERE `id` = :id AND `deleted_at` IS NULL',
        );
        $timestamp = $this->formatTimestamp($occurredAt);
        $statement->execute([
            'deleted_at' => $timestamp,
            'updated_at' => $timestamp,
            'id' => $command->translationId,
        ]);

        return $statement->rowCount() > 0;
    }

    public function restore(
        RestoreCategoryTranslationCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool {
        $statement = $this->pdo->prepare(
            'UPDATE `' . self::TRANSLATION_TABLE . '` '
            . 'SET `deleted_at` = NULL, `updated_at` = :updated_at '
            . 'WHERE `id` = :id AND `deleted_at` IS NOT NULL',
        );
        $statement->execute([
            'updated_at' => $this->formatTimestamp($occurredAt),
            'id' => $command->translationId,
        ]);

        return $statement->rowCount() > 0;
    }

    private function formatTimestamp(DateTimeImmutable $occurredAt): string
    {
        return $occurredAt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
}
