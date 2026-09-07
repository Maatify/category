<?php

declare(strict_types=1);

namespace Maatify\Category\Exception;

use Maatify\Exceptions\Exception\Conflict\GenericConflictMaatifyException;
use Throwable;

final class CategoryTranslationAlreadyExistsException extends GenericConflictMaatifyException
    implements CategoryExceptionInterface
{
    public static function withIdentity(
        int $categoryId,
        string $languageCode,
        ?Throwable $previous = null,
    ): self
    {
        return new self(
            sprintf(
                'Category Translation for Category %d and language "%s" already exists.',
                $categoryId,
                $languageCode,
            ),
            0,
            $previous,
        );
    }
}
