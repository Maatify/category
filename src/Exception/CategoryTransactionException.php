<?php

declare(strict_types=1);

namespace Maatify\Category\Exception;

use Maatify\Category\Exception\CategoryExceptionInterface;
use Maatify\Exceptions\Contracts\ErrorCodeInterface;
use Maatify\Exceptions\Enum\ErrorCodeEnum;
use Maatify\Exceptions\Exception\System\SystemMaatifyException;

final class CategoryTransactionException extends SystemMaatifyException
    implements CategoryExceptionInterface
{
    protected function defaultErrorCode(): ErrorCodeInterface
    {
        return ErrorCodeEnum::DATABASE_CONNECTION_FAILED;
    }

    public static function alreadyActive(): self
    {
        return new self('A Category transaction cannot start inside another active transaction.');
    }
}
