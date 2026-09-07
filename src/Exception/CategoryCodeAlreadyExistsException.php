<?php

declare(strict_types=1);

namespace Maatify\Category\Exception;

use Maatify\Category\Exception\CategoryExceptionInterface;
use Maatify\Exceptions\Exception\Conflict\GenericConflictMaatifyException;

final class CategoryCodeAlreadyExistsException extends GenericConflictMaatifyException
    implements CategoryExceptionInterface
{
    public static function withCode(string $code): self
    {
        return new self(sprintf('Category code "%s" already exists.', $code));
    }
}
