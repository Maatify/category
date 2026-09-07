<?php

declare(strict_types=1);

namespace Maatify\Category\Exception;

use Maatify\Category\Exception\CategoryExceptionInterface;
use Maatify\Exceptions\Exception\NotFound\ResourceNotFoundMaatifyException;

final class CategoryTranslationNotFoundException extends ResourceNotFoundMaatifyException
    implements CategoryExceptionInterface
{
    public static function withId(int $translationId): self
    {
        return new self(sprintf('Category Translation with id %d was not found.', $translationId));
    }
}
