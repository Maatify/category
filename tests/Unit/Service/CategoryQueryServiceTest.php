<?php

declare(strict_types=1);

namespace Maatify\Category\Tests\Unit\Service;

use DateTimeImmutable;
use Maatify\Category\Query\Contract\CategoryReadQueryInterface;
use Maatify\Category\Query\DTO\CategoryCollectionDTO;
use Maatify\Category\Query\DTO\CategoryDTO;
use Maatify\Category\Content\Query\DTO\CategoryContentCollectionDTO;
use Maatify\Category\Content\Query\DTO\CategoryContentDTO;
use Maatify\Category\Query\DTO\CategoryVisibleListCriteriaDTO;
use Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentCollectionDTO;
use Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentDTO;
use Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentScopeDTO;
use Maatify\Category\Lifecycle\Enum\CategoryStatusEnum;
use Maatify\Category\Common\Exception\CategoryInvalidArgumentException;
use Maatify\Category\Lifecycle\Exception\CategoryNotFoundException;
use Maatify\Category\Api\Service\CategoryQueryService;
use PHPUnit\Framework\TestCase;

final class CategoryQueryServiceTest extends TestCase
{
    public function testGetByIdReturnsOnlyTheVisibleCategoryFromTheReader(): void
    {
        $category = $this->category(7);
        $reader = $this->createMock(CategoryReadQueryInterface::class);
        $reader->expects(self::once())
            ->method('findVisibleById')
            ->with(7)
            ->willReturn($category);

        $result = (new CategoryQueryService($reader))->getById(7);

        self::assertSame($category, $result);
    }

    public function testGetByIdRejectsAnUnavailableCategory(): void
    {
        $reader = $this->createStub(CategoryReadQueryInterface::class);
        $reader->method('findVisibleById')->willReturn(null);

        $this->expectException(CategoryNotFoundException::class);

        (new CategoryQueryService($reader))->getById(7);
    }

    public function testGetByIdRejectsANonPositiveIdentity(): void
    {
        $this->expectException(CategoryInvalidArgumentException::class);

        (new CategoryQueryService($this->createStub(CategoryReadQueryInterface::class)))->getById(0);
    }

    public function testVisibleListCriteriaRejectsOutOfBoundsMaximums(): void
    {
        $this->expectException(CategoryInvalidArgumentException::class);

        new CategoryVisibleListCriteriaDTO(CategoryVisibleListCriteriaDTO::MAX_MAX_RESULTS + 1);
    }

    public function testListOperationsReturnTypedCollectionsFromTheReader(): void
    {
        $categories = new CategoryCollectionDTO([$this->category(1)]);
        $contents = new CategoryContentCollectionDTO([$this->content(2)]);
        $imageAssignments = new CategoryImageAssignmentCollectionDTO([$this->imageAssignment(3)]);
        $scope = new CategoryImageAssignmentScopeDTO('en-US', 'web');
        $criteria = new CategoryVisibleListCriteriaDTO(2);
        $reader = $this->createMock(CategoryReadQueryInterface::class);
        $reader->expects(self::once())->method('listVisibleRootCategories')->with($criteria)->willReturn($categories);
        $reader->expects(self::once())->method('listVisibleChildren')->with(1, $criteria)->willReturn($categories);
        $reader->expects(self::once())->method('listVisibleContents')->with(1, $criteria)->willReturn($contents);
        $reader->expects(self::once())
            ->method('listVisibleImageAssignments')
            ->with(1, $scope, $criteria)
            ->willReturn($imageAssignments);
        $service = new CategoryQueryService($reader);

        self::assertSame($categories, $service->listRootCategories($criteria));
        self::assertSame($categories, $service->listChildren(1, $criteria));
        self::assertSame($contents, $service->listContents(1, $criteria));
        self::assertSame($imageAssignments, $service->listImageAssignments(1, $scope, $criteria));
    }

    private function category(int $id): CategoryDTO
    {
        $timestamp = new DateTimeImmutable('2026-01-01 00:00:00 Africa/Cairo');

        return new CategoryDTO(
            id: $id,
            parentId: null,
            code: 'category-' . $id,
            status: CategoryStatusEnum::ACTIVE,
            displayOrder: 1,
            createdAt: $timestamp,
            updatedAt: $timestamp,
            deletedAt: null,
        );
    }

    private function content(int $id): CategoryContentDTO
    {
        $timestamp = new DateTimeImmutable('2026-01-01 00:00:00 Africa/Cairo');

        return new CategoryContentDTO(
            id: $id,
            categoryId: 1,
            languageCode: 'en-US',
            name: 'Category',
            description: null,
            createdAt: $timestamp,
            updatedAt: $timestamp,
            deletedAt: null,
        );
    }

    private function imageAssignment(int $id): CategoryImageAssignmentDTO
    {
        $timestamp = new DateTimeImmutable('2026-01-01 00:00:00 Africa/Cairo');

        return new CategoryImageAssignmentDTO(
            id: $id,
            categoryId: 1,
            mediaAssetId: 900,
            languageCode: 'en-US',
            platform: 'web',
            displayOrder: 1,
            createdAt: $timestamp,
            updatedAt: $timestamp,
            deletedAt: null,
        );
    }
}
