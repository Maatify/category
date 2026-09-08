<?php

declare(strict_types=1);

namespace Maatify\Category\Tests\Unit\DTO;

use DateTimeImmutable;
use JsonSerializable;
use Maatify\Category\DTO\CategoryCollectionDTO;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CategoryIdDTO;
use Maatify\Category\DTO\CategoryTranslationCollectionDTO;
use Maatify\Category\DTO\CategoryTranslationDTO;
use Maatify\Category\Command\CreateCategoryCommand;
use Maatify\Category\Command\CreateCategoryTranslationCommand;
use Maatify\Category\Command\MoveCategoryCommand;
use Maatify\Category\Command\RestoreCategoryCommand;
use Maatify\Category\Command\RestoreCategoryTranslationCommand;
use Maatify\Category\Command\SoftDeleteCategoryCommand;
use Maatify\Category\Command\SoftDeleteCategoryTranslationCommand;
use Maatify\Category\Command\UpdateCategoryDisplayOrderCommand;
use Maatify\Category\Command\UpdateCategoryStatusCommand;
use Maatify\Category\Command\UpdateCategoryTranslationCommand;
use Maatify\Category\Enum\CategoryStatusEnum;
use PHPUnit\Framework\TestCase;

final class CategoryContractJsonSerializationTest extends TestCase
{
    public function testEveryPublicDtoAndCommandIsJsonSerializableWithStableScalarShape(): void
    {
        $timestamp = new DateTimeImmutable('2026-01-01 00:00:00 UTC');
        $category = new CategoryDTO(
            id: 7,
            parentId: 3,
            code: 'shirts',
            status: CategoryStatusEnum::INACTIVE,
            displayOrder: 4,
            createdAt: $timestamp,
            updatedAt: $timestamp,
            deletedAt: null,
        );
        $translation = new CategoryTranslationDTO(
            id: 11,
            categoryId: 7,
            languageCode: 'ar-EG',
            name: 'قمصان',
            description: 'وصف',
            createdAt: $timestamp,
            updatedAt: $timestamp,
            deletedAt: null,
        );

        $dtos = [
            new CategoryIdDTO(7),
            $category,
            $translation,
            new CreateCategoryCommand('shirts', 3, CategoryStatusEnum::INACTIVE),
            new CreateCategoryTranslationCommand(7, 'ar-EG', 'قمصان', 'وصف'),
            new MoveCategoryCommand(7, 3),
            new RestoreCategoryCommand(7),
            new RestoreCategoryTranslationCommand(11),
            new SoftDeleteCategoryCommand(7),
            new SoftDeleteCategoryTranslationCommand(11),
            new UpdateCategoryDisplayOrderCommand(7, 4),
            new UpdateCategoryStatusCommand(7, CategoryStatusEnum::INACTIVE),
            new UpdateCategoryTranslationCommand(11, 'قمصان', 'وصف'),
        ];

        foreach ($dtos as $dto) {
            self::assertInstanceOf(JsonSerializable::class, $dto);
            self::assertSame(
                $dto->jsonSerialize(),
                json_decode(json_encode($dto, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR),
            );
        }

        self::assertInstanceOf(JsonSerializable::class, new CategoryCollectionDTO([$category]));
        self::assertInstanceOf(JsonSerializable::class, new CategoryTranslationCollectionDTO([$translation]));

        self::assertSame([
            'id' => 7,
            'parentId' => 3,
            'code' => 'shirts',
            'status' => 'inactive',
            'displayOrder' => 4,
            'createdAt' => '2026-01-01T00:00:00+00:00',
            'updatedAt' => '2026-01-01T00:00:00+00:00',
            'deletedAt' => null,
        ], $category->jsonSerialize());
        self::assertSame([
            [
                'id' => 7,
                'parentId' => 3,
                'code' => 'shirts',
                'status' => 'inactive',
                'displayOrder' => 4,
                'createdAt' => '2026-01-01T00:00:00+00:00',
                'updatedAt' => '2026-01-01T00:00:00+00:00',
                'deletedAt' => null,
            ],
        ], json_decode(
            json_encode(new CategoryCollectionDTO([$category]), JSON_THROW_ON_ERROR),
            true,
            512,
            JSON_THROW_ON_ERROR,
        ));
        self::assertSame([
            [
                'id' => 11,
                'categoryId' => 7,
                'languageCode' => 'ar-EG',
                'name' => 'قمصان',
                'description' => 'وصف',
                'createdAt' => '2026-01-01T00:00:00+00:00',
                'updatedAt' => '2026-01-01T00:00:00+00:00',
                'deletedAt' => null,
            ],
        ], json_decode(
            json_encode(new CategoryTranslationCollectionDTO([$translation]), JSON_THROW_ON_ERROR),
            true,
            512,
            JSON_THROW_ON_ERROR,
        ));
    }
}
