<?php

declare(strict_types=1);

namespace Maatify\Category\Tests\Unit\DTO;

use DateTimeImmutable;
use JsonSerializable;
use Maatify\Category\DTO\CategoryCollectionDTO;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CategoryIdDTO;
use Maatify\Category\DTO\CategoryContentCollectionDTO;
use Maatify\Category\DTO\CategoryContentDTO;
use Maatify\Category\DTO\CategoryContentFieldCollectionDTO;
use Maatify\Category\DTO\CategoryContentFieldDTO;
use Maatify\Category\DTO\CategoryContentFieldListCriteriaDTO;
use Maatify\Category\DTO\CategoryContentFieldScopeDTO;
use Maatify\Category\DTO\CategoryImageAssignmentCollectionDTO;
use Maatify\Category\DTO\CategoryImageAssignmentDTO;
use Maatify\Category\DTO\CategoryImageAssignmentListCriteriaDTO;
use Maatify\Category\DTO\CategoryImageAssignmentRoleFilterDTO;
use Maatify\Category\DTO\CategoryImageAssignmentScopeDTO;
use Maatify\Category\DTO\CategoryImageRoleCollectionDTO;
use Maatify\Category\DTO\CategoryImageRoleDTO;
use Maatify\Category\DTO\CategoryImageRoleListCriteriaDTO;
use Maatify\Category\Command\CreateCategoryCommand;
use Maatify\Category\Command\CreateCategoryContentCommand;
use Maatify\Category\Command\CreateCategoryContentFieldCommand;
use Maatify\Category\Command\CreateCategoryImageAssignmentCommand;
use Maatify\Category\Command\MoveCategoryCommand;
use Maatify\Category\Command\RestoreCategoryCommand;
use Maatify\Category\Command\RestoreCategoryContentCommand;
use Maatify\Category\Command\RestoreCategoryContentFieldCommand;
use Maatify\Category\Command\RestoreCategoryImageAssignmentCommand;
use Maatify\Category\Command\RestoreCategoryImageRoleCommand;
use Maatify\Category\Command\SoftDeleteCategoryCommand;
use Maatify\Category\Command\SoftDeleteCategoryContentCommand;
use Maatify\Category\Command\SoftDeleteCategoryContentFieldCommand;
use Maatify\Category\Command\SoftDeleteCategoryImageAssignmentCommand;
use Maatify\Category\Command\SoftDeleteCategoryImageRoleCommand;
use Maatify\Category\Command\UpdateCategoryDisplayOrderCommand;
use Maatify\Category\Command\UpdateCategoryStatusCommand;
use Maatify\Category\Command\UpdateCategoryContentCommand;
use Maatify\Category\Command\UpdateCategoryContentFieldCommand;
use Maatify\Category\Command\UpdateCategoryContentFieldDisplayOrderCommand;
use Maatify\Category\Command\UpdateCategoryImageAssignmentDisplayOrderCommand;
use Maatify\Category\Command\CreateCategoryImageRoleCommand;
use Maatify\Category\Command\UpdateCategoryImageRoleStatusCommand;
use Maatify\Category\Enum\CategoryStatusEnum;
use Maatify\Category\Enum\CategoryContentFieldFormatEnum;
use Maatify\Category\Enum\CategoryImageRoleStatusEnum;
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
        $content = new CategoryContentDTO(
            id: 11,
            categoryId: 7,
            languageCode: null,
            name: 'قمصان',
            description: 'وصف',
            createdAt: $timestamp,
            updatedAt: $timestamp,
            deletedAt: null,
        );
        $imageAssignment = new CategoryImageAssignmentDTO(
            id: 13,
            categoryId: 7,
            mediaAssetId: 900,
            languageCode: null,
            platform: 'web',
            displayOrder: 1,
            createdAt: $timestamp,
            updatedAt: $timestamp,
            deletedAt: null,
        );
        $contentField = new CategoryContentFieldDTO(
            id: 17,
            categoryId: 7,
            fieldKey: 'badge',
            languageCode: 'ar',
            platform: 'web',
            format: CategoryContentFieldFormatEnum::JSON,
            value: '{"enabled":true}',
            displayOrder: 2,
            createdAt: $timestamp,
            updatedAt: $timestamp,
            deletedAt: null,
        );
        $imageRole = new CategoryImageRoleDTO(
            id: 19,
            roleKey: 'gallery',
            status: CategoryImageRoleStatusEnum::ACTIVE,
            createdAt: $timestamp,
            updatedAt: $timestamp,
            deletedAt: null,
        );

        $dtos = [
            new CategoryIdDTO(7),
            $category,
            $content,
            new CreateCategoryCommand('shirts', 3, CategoryStatusEnum::INACTIVE),
            new CreateCategoryContentCommand(7, null, 'قمصان', 'وصف'),
            new CreateCategoryContentFieldCommand(7, 'badge', 'ar', 'web', CategoryContentFieldFormatEnum::JSON, '{"enabled":true}'),
            new CreateCategoryImageRoleCommand('gallery'),
            new UpdateCategoryImageRoleStatusCommand(19, CategoryImageRoleStatusEnum::INACTIVE),
            new CreateCategoryImageAssignmentCommand(7, 900, null, 'web'),
            new MoveCategoryCommand(7, 3),
            new RestoreCategoryCommand(7),
            new RestoreCategoryContentCommand(11),
            new RestoreCategoryContentFieldCommand(17),
            new RestoreCategoryImageAssignmentCommand(13),
            new RestoreCategoryImageRoleCommand(19),
            new SoftDeleteCategoryCommand(7),
            new SoftDeleteCategoryContentCommand(11),
            new SoftDeleteCategoryContentFieldCommand(17),
            new SoftDeleteCategoryImageAssignmentCommand(13),
            new SoftDeleteCategoryImageRoleCommand(19),
            new UpdateCategoryDisplayOrderCommand(7, 4),
            new UpdateCategoryStatusCommand(7, CategoryStatusEnum::INACTIVE),
            new UpdateCategoryContentCommand(11, 'قمصان', 'وصف'),
            new UpdateCategoryContentFieldCommand(17, CategoryContentFieldFormatEnum::TEXT, 'new badge'),
            new UpdateCategoryContentFieldDisplayOrderCommand(17, 3),
            new UpdateCategoryImageAssignmentDisplayOrderCommand(13, 2),
            new CategoryImageAssignmentScopeDTO(null, 'web', 19),
            new CategoryImageAssignmentListCriteriaDTO(
                scope: new CategoryImageAssignmentScopeDTO('ar', 'web'),
                roleFilter: CategoryImageAssignmentRoleFilterDTO::omitted(),
            ),
            $imageAssignment,
            $imageRole,
            new CategoryContentFieldScopeDTO('ar', 'web'),
            $contentField,
            new CategoryContentFieldListCriteriaDTO(7, 'badge', new CategoryContentFieldScopeDTO('ar', 'web')),
            new CategoryImageRoleListCriteriaDTO(CategoryImageRoleStatusEnum::ACTIVE),
        ];

        foreach ($dtos as $dto) {
            self::assertInstanceOf(JsonSerializable::class, $dto);
            self::assertSame(
                $dto->jsonSerialize(),
                json_decode(json_encode($dto, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR),
            );
        }

        self::assertInstanceOf(JsonSerializable::class, new CategoryCollectionDTO([$category]));
        self::assertInstanceOf(JsonSerializable::class, new CategoryContentCollectionDTO([$content]));
        self::assertInstanceOf(JsonSerializable::class, new CategoryImageAssignmentCollectionDTO([$imageAssignment]));
        self::assertInstanceOf(JsonSerializable::class, new CategoryImageRoleCollectionDTO([$imageRole]));
        self::assertInstanceOf(JsonSerializable::class, new CategoryContentFieldCollectionDTO([$contentField]));

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
                'languageCode' => null,
                'name' => 'قمصان',
                'description' => 'وصف',
                'createdAt' => '2026-01-01T00:00:00+00:00',
                'updatedAt' => '2026-01-01T00:00:00+00:00',
                'deletedAt' => null,
            ],
        ], json_decode(
            json_encode(new CategoryContentCollectionDTO([$content]), JSON_THROW_ON_ERROR),
            true,
            512,
            JSON_THROW_ON_ERROR,
        ));
    }
}
