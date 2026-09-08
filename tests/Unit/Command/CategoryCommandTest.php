<?php

declare(strict_types=1);

namespace Maatify\Category\Tests\Unit\Command;

use ReflectionClass;
use ReflectionProperty;
use Maatify\Category\Command\CreateCategoryCommand;
use Maatify\Category\Command\MoveCategoryCommand;
use Maatify\Category\Command\UpdateCategoryDisplayOrderCommand;
use Maatify\Category\Command\UpdateCategoryTranslationCommand;
use Maatify\Category\DTO\CategoryIdDTO;
use Maatify\Category\Exception\CategoryInvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CategoryCommandTest extends TestCase
{
    public function testCanonicalStringIdentityIsNormalizedToAnInteger(): void
    {
        $identity = new CategoryIdDTO('42', 'categoryId');

        self::assertSame(42, $identity->value);
    }

    #[DataProvider('invalidIdentityProvider')]
    public function testNonCanonicalStringIdentitiesAreRejected(string $value): void
    {
        $this->expectException(CategoryInvalidArgumentException::class);

        new CategoryIdDTO($value, 'categoryId');
    }

    /** @return iterable<string, array{string}> */
    public static function invalidIdentityProvider(): iterable
    {
        yield 'zero' => ['0'];
        yield 'leading zero' => ['07'];
        yield 'positive sign' => ['+7'];
        yield 'negative sign' => ['-7'];
        yield 'whitespace' => ['7 '];
        yield 'decimal' => ['7.0'];
        yield 'scientific notation' => ['7e1'];
        yield 'overflow' => [str_repeat('9', strlen((string) PHP_INT_MAX) + 1)];
    }

    public function testCreateRejectsAnOverlongCode(): void
    {
        $this->expectException(CategoryInvalidArgumentException::class);

        new CreateCategoryCommand(str_repeat('x', 101));
    }

    public function testDisplayOrderMustBePositive(): void
    {
        $this->expectException(CategoryInvalidArgumentException::class);

        new UpdateCategoryDisplayOrderCommand(1, 0);
    }

    public function testTranslationUpdateHasNoLogicalIdentityInputs(): void
    {
        $update = new UpdateCategoryTranslationCommand(4, 'Shirts', null);
        $propertyNames = array_map(
            static fn (ReflectionProperty $property): string => $property->getName(),
            (new ReflectionClass($update))->getProperties(),
        );

        self::assertSame(4, $update->translationId);
        self::assertNotContains('categoryId', $propertyNames);
        self::assertNotContains('languageCode', $propertyNames);
        self::assertNotContains('id', $propertyNames);
        self::assertFalse(property_exists(MoveCategoryCommand::class, 'code'));
    }
}
