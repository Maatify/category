<?php

declare(strict_types=1);

namespace Maatify\Category\Infrastructure\Transaction;

use Closure;
use Maatify\Category\Contract\CategoryTransactionInterface;
use Maatify\Category\Exception\CategoryTransactionException;
use PDO;
use Throwable;

/** PDO transaction adapter for application-owned Category orchestration. */
final readonly class PdoCategoryTransaction implements CategoryTransactionInterface
{
    public function __construct(private PDO $pdo) {}

    public function run(Closure $operation): mixed
    {
        if ($this->pdo->inTransaction()) {
            throw CategoryTransactionException::alreadyActive();
        }

        $transactionStarted = false;

        try {
            $this->pdo->beginTransaction();
            $transactionStarted = true;

            $result = $operation();
            $this->pdo->commit();
            $transactionStarted = false;

            return $result;
        } catch (Throwable $exception) {
            $this->rollbackIfActive($transactionStarted);

            throw $exception;
        }
    }

    private function rollbackIfActive(bool $transactionStarted): void
    {
        if (!$transactionStarted) {
            return;
        }

        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
