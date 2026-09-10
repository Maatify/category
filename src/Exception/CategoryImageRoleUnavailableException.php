<?php

declare(strict_types=1);

namespace Maatify\Category\Exception;

use Maatify\Exceptions\Exception\Conflict\GenericConflictMaatifyException;

final class CategoryImageRoleUnavailableException extends GenericConflictMaatifyException
    implements CategoryExceptionInterface
{
    public static function withId(int $roleId): self
    {
        return new self(sprintf(
            'Category Image Role with id %d is inactive or soft-deleted and cannot accept new assignments.',
            $roleId,
        ));
    }
}
