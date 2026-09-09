<?php

declare(strict_types=1);

namespace Maatify\Category\Tests\Unit\Command;

use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use Maatify\Category\Command\CreateCategoryTranslationCommand;
use Maatify\Category\Command\CreateCategoryCommand;
use Maatify\Category\Command\MoveCategoryCommand;
use Maatify\Category\Command\RestoreCategoryCommand;
use Maatify\Category\Command\RestoreCategoryTranslationCommand;
use Maatify\Category\Command\SoftDeleteCategoryCommand;
use Maatify\Category\Command\SoftDeleteCategoryTranslationCommand;
use Maatify\Category\Command\UpdateCategoryDisplayOrderCommand;
use Maatify\Category\Command\UpdateCategoryStatusCommand;
use Maatify\Category\Command\UpdateCategoryTranslationCommand;
use Maatify\Category\Contract\CategoryCommandServiceInterface;
use Maatify\Category\DTO\CategoryIdDTO;
use Maatify\Category\Enum\CategoryStatusEnum;
use Maatify\Category\Exception\CategoryInvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TypeError;

final class CategoryCommandTest extends TestCase
{
    public function testCanonicalStringIdentityIsNormalizedToAnInteger(): void
    {
        $identity = new CategoryIdDTO('42', 'categoryId');

        self::assertSame(42, $identity->value);
    }

    #[DataProvider('invalidIdentityProvider')]
    public function testEveryCommandRejectsZeroNegativeAndNonCanonicalIdentities(int|string $value): void
    {
        $factories = [
            'CreateCategory.parentId' => static fn (int|string $id): object => new CreateCategoryCommand('code', $id),
            'CreateCategoryTranslation.categoryId' => static fn (int|string $id): object => new CreateCategoryTranslationCommand($id, 'en-US', 'Name', null),
            'MoveCategory.categoryId' => static fn (int|string $id): object => new MoveCategoryCommand($id, null),
            'MoveCategory.parentId' => static fn (int|string $id): object => new MoveCategoryCommand(42, $id),
            'RestoreCategory.categoryId' => static fn (int|string $id): object => new RestoreCategoryCommand($id),
            'RestoreCategoryTranslation.translationId' => static fn (int|string $id): object => new RestoreCategoryTranslationCommand($id),
            'SoftDeleteCategory.categoryId' => static fn (int|string $id): object => new SoftDeleteCategoryCommand($id),
            'SoftDeleteCategoryTranslation.translationId' => static fn (int|string $id): object => new SoftDeleteCategoryTranslationCommand($id),
            'UpdateCategoryDisplayOrder.categoryId' => static fn (int|string $id): object => new UpdateCategoryDisplayOrderCommand($id, 1),
            'UpdateCategoryStatus.categoryId' => static fn (int|string $id): object => new UpdateCategoryStatusCommand($id, CategoryStatusEnum::ACTIVE),
            'UpdateCategoryTranslation.translationId' => static fn (int|string $id): object => new UpdateCategoryTranslationCommand($id, 'Name', null),
        ];

        foreach ($factories as $name => $factory) {
            $this->assertInvalidArgument(
                static fn (): object => $factory($value),
                sprintf('%s must reject identity %s.', $name, var_export($value, true)),
            );
        }
    }

    /** @return iterable<string, array{int|string}> */
    public static function invalidIdentityProvider(): iterable
    {
        yield 'zero integer' => [0];
        yield 'negative integer' => [-1];
        yield 'zero' => ['0'];
        yield 'leading zero' => ['07'];
        yield 'positive sign' => ['+7'];
        yield 'negative sign' => ['-7'];
        yield 'whitespace' => ['7 '];
        yield 'decimal' => ['7.0'];
        yield 'scientific notation' => ['7e1'];
        yield 'overflow' => [str_repeat('9', strlen((string) PHP_INT_MAX) + 1)];
    }

    public function testEveryCommandNormalizesCanonicalPositiveStringIdentities(): void
    {
        self::assertSame(42, (new CreateCategoryCommand('code', '42'))->parentId);
        self::assertSame(42, (new CreateCategoryTranslationCommand('42', 'en-US', 'Name', null))->categoryId);
        self::assertSame(42, (new MoveCategoryCommand('42', '43'))->categoryId);
        self::assertSame(43, (new MoveCategoryCommand('42', '43'))->parentId);
        self::assertSame(42, (new RestoreCategoryCommand('42'))->categoryId);
        self::assertSame(42, (new RestoreCategoryTranslationCommand('42'))->translationId);
        self::assertSame(42, (new SoftDeleteCategoryCommand('42'))->categoryId);
        self::assertSame(42, (new SoftDeleteCategoryTranslationCommand('42'))->translationId);
        self::assertSame(42, (new UpdateCategoryDisplayOrderCommand('42', 1))->categoryId);
        self::assertSame(42, (new UpdateCategoryStatusCommand('42', CategoryStatusEnum::ACTIVE))->categoryId);
        self::assertSame(42, (new UpdateCategoryTranslationCommand('42', 'Name', null))->translationId);
    }

    public function testRequiredStringsRejectEmptyAndWhitespaceOnlyValues(): void
    {
        foreach (['empty' => '', 'spaces' => '   ', 'tabs' => "\t\n"] as $label => $value) {
            $this->assertInvalidArgument(
                static fn (): object => new CreateCategoryCommand($value),
                sprintf('CreateCategory.code must reject %s input.', $label),
            );
            $this->assertInvalidArgument(
                static fn (): object => new CreateCategoryTranslationCommand(1, $value, 'Name', null),
                sprintf('CreateCategoryTranslation.languageCode must reject %s input.', $label),
            );
            $this->assertInvalidArgument(
                static fn (): object => new CreateCategoryTranslationCommand(1, 'en-US', $value, null),
                sprintf('CreateCategoryTranslation.name must reject %s input.', $label),
            );
            $this->assertInvalidArgument(
                static fn (): object => new UpdateCategoryTranslationCommand(1, $value, null),
                sprintf('UpdateCategoryTranslation.name must reject %s input.', $label),
            );
        }
    }

    public function testStorageStringLengthBoundariesAreEnforced(): void
    {
        self::assertSame(100, mb_strlen((new CreateCategoryCommand(str_repeat('x', 100)))->code));
        $this->assertInvalidArgument(
            static fn (): object => new CreateCategoryCommand(str_repeat('x', 101)),
            'Category code must reject more than 100 characters.',
        );

        self::assertSame(
            16,
            mb_strlen((new CreateCategoryTranslationCommand(1, str_repeat('x', 16), 'Name', null))->languageCode),
        );
        $this->assertInvalidArgument(
            static fn (): object => new CreateCategoryTranslationCommand(1, str_repeat('x', 17), 'Name', null),
            'Language code must reject more than 16 characters.',
        );

        self::assertSame(
            255,
            mb_strlen((new CreateCategoryTranslationCommand(1, 'en-US', str_repeat('x', 255), null))->name),
        );
        $this->assertInvalidArgument(
            static fn (): object => new CreateCategoryTranslationCommand(1, 'en-US', str_repeat('x', 256), null),
            'Created translation name must reject more than 255 characters.',
        );
        self::assertSame(
            255,
            mb_strlen((new UpdateCategoryTranslationCommand(1, str_repeat('x', 255), null))->name),
        );
        $this->assertInvalidArgument(
            static fn (): object => new UpdateCategoryTranslationCommand(1, str_repeat('x', 256), null),
            'Updated translation name must reject more than 255 characters.',
        );
    }

    public function testStatusInputsAreCategoryStatusEnums(): void
    {
        $createStatus = (new ReflectionMethod(CreateCategoryCommand::class, '__construct'))->getParameters()[2]->getType();
        $updateStatus = (new ReflectionMethod(UpdateCategoryStatusCommand::class, '__construct'))->getParameters()[1]->getType();

        self::assertInstanceOf(ReflectionNamedType::class, $createStatus);
        self::assertInstanceOf(ReflectionNamedType::class, $updateStatus);
        self::assertSame(CategoryStatusEnum::class, $createStatus->getName());
        self::assertSame(CategoryStatusEnum::class, $updateStatus->getName());
        self::assertSame(CategoryStatusEnum::INACTIVE, (new CreateCategoryCommand('code', null, CategoryStatusEnum::INACTIVE))->status);
        self::assertSame(CategoryStatusEnum::ACTIVE, (new UpdateCategoryStatusCommand(1, CategoryStatusEnum::ACTIVE))->status);

        $this->assertTypeError(
            static fn (): object => (new ReflectionClass(CreateCategoryCommand::class))->newInstanceArgs(['code', null, 'active']),
            'CreateCategory.status must reject raw strings.',
        );
        $this->assertTypeError(
            static fn (): object => (new ReflectionClass(UpdateCategoryStatusCommand::class))->newInstanceArgs([1, 'active']),
            'UpdateCategoryStatus.status must reject raw strings.',
        );
    }

    public function testMoveRejectsDirectSelfReferenceAfterIdentityNormalization(): void
    {
        $this->assertInvalidArgument(
            static fn (): object => new MoveCategoryCommand('7', 7),
            'MoveCategory must reject a canonical string/int self-parent reference.',
        );
        $this->assertInvalidArgument(
            static fn (): object => new MoveCategoryCommand(7, '7'),
            'MoveCategory must reject an int/canonical string self-parent reference.',
        );
    }

    public function testDisplayOrderMustBeGreaterThanZero(): void
    {
        self::assertSame(1, (new UpdateCategoryDisplayOrderCommand(1, 1))->displayOrder);

        foreach ([0, -1] as $displayOrder) {
            $this->assertInvalidArgument(
                static fn (): object => new UpdateCategoryDisplayOrderCommand(1, $displayOrder),
                sprintf('Display order %d must be rejected.', $displayOrder),
            );
        }
    }

    public function testCreateCategoryHasNoDisplayOrderInput(): void
    {
        $parameters = (new ReflectionMethod(CreateCategoryCommand::class, '__construct'))->getParameters();

        self::assertSame(
            ['code', 'parentId', 'status'],
            array_map(static fn (\ReflectionParameter $parameter): string => $parameter->getName(), $parameters),
        );
        self::assertFalse(property_exists(CreateCategoryCommand::class, 'displayOrder'));
    }

    public function testCategoryCodeIsOnlyAcceptedDuringCreation(): void
    {
        $mutationCommands = [
            MoveCategoryCommand::class,
            RestoreCategoryCommand::class,
            RestoreCategoryTranslationCommand::class,
            SoftDeleteCategoryCommand::class,
            SoftDeleteCategoryTranslationCommand::class,
            UpdateCategoryDisplayOrderCommand::class,
            UpdateCategoryStatusCommand::class,
            UpdateCategoryTranslationCommand::class,
            CreateCategoryTranslationCommand::class,
        ];

        foreach ($mutationCommands as $commandClass) {
            self::assertFalse(property_exists($commandClass, 'code'), $commandClass . ' must not mutate Category code.');
        }
        self::assertTrue(property_exists(CreateCategoryCommand::class, 'code'));
    }

    public function testTranslationUpdateCannotMutateCategoryOrLanguageIdentity(): void
    {
        $parameters = (new ReflectionMethod(UpdateCategoryTranslationCommand::class, '__construct'))->getParameters();
        $propertyNames = array_map(
            static fn (ReflectionProperty $property): string => $property->getName(),
            (new ReflectionClass(UpdateCategoryTranslationCommand::class))->getProperties(),
        );

        self::assertSame(
            ['translationId', 'name', 'description'],
            array_map(static fn (\ReflectionParameter $parameter): string => $parameter->getName(), $parameters),
        );
        self::assertNotContains('categoryId', $propertyNames);
        self::assertNotContains('languageCode', $propertyNames);
    }

    public function testAllMutationIntentsRemainTypedCommands(): void
    {
        $expectedCommands = [
            'create' => CreateCategoryCommand::class,
            'createTranslation' => CreateCategoryTranslationCommand::class,
            'move' => MoveCategoryCommand::class,
            'softDelete' => SoftDeleteCategoryCommand::class,
            'softDeleteTranslation' => SoftDeleteCategoryTranslationCommand::class,
            'restore' => RestoreCategoryCommand::class,
            'restoreTranslation' => RestoreCategoryTranslationCommand::class,
            'updateStatus' => UpdateCategoryStatusCommand::class,
            'updateDisplayOrder' => UpdateCategoryDisplayOrderCommand::class,
            'updateTranslation' => UpdateCategoryTranslationCommand::class,
        ];

        $serviceReflection = new ReflectionClass(CategoryCommandServiceInterface::class);
        self::assertCount(10, $expectedCommands);

        foreach ($expectedCommands as $methodName => $expectedCommand) {
            $method = $serviceReflection->getMethod($methodName);
            $parameters = $method->getParameters();
            self::assertCount(1, $parameters, $methodName . ' must have one typed Command input.');

            $parameterType = $parameters[0]->getType();
            self::assertInstanceOf(ReflectionNamedType::class, $parameterType);
            self::assertSame($expectedCommand, $parameterType->getName(), $methodName . ' must use its dedicated Command.');

            $commandReflection = new ReflectionClass($expectedCommand);
            self::assertTrue($commandReflection->isFinal());
            self::assertTrue($commandReflection->isReadOnly());
            self::assertTrue($commandReflection->implementsInterface(\JsonSerializable::class));
        }
    }

    /** @param callable(): object $factory */
    private function assertInvalidArgument(callable $factory, string $message): void
    {
        try {
            $factory();
        } catch (CategoryInvalidArgumentException) {
            self::addToAssertionCount(1);

            return;
        }

        self::fail($message);
    }

    /** @param callable(): object $factory */
    private function assertTypeError(callable $factory, string $message): void
    {
        try {
            $factory();
        } catch (TypeError) {
            self::addToAssertionCount(1);

            return;
        }

        self::fail($message);
    }
}
