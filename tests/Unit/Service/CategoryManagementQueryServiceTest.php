<?php

declare(strict_types=1);

namespace Maatify\Category\Tests\Unit\Service;

use DateTimeImmutable;
use Maatify\Category\Contract\CategoryManagementReadQueryInterface;
use Maatify\Category\DTO\CategoryCollectionDTO;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CategoryListCriteriaDTO;
use Maatify\Category\DTO\CategoryContentCollectionDTO;
use Maatify\Category\DTO\CategoryContentDTO;
use Maatify\Category\DTO\CategoryContentListCriteriaDTO;
use Maatify\Category\DTO\CategoryImageAssignmentCollectionDTO;
use Maatify\Category\DTO\CategoryImageAssignmentDTO;
use Maatify\Category\DTO\CategoryImageAssignmentListCriteriaDTO;
use Maatify\Category\Enum\CategoryDeletedStateEnum;
use Maatify\Category\Enum\CategoryStatusEnum;
use Maatify\Category\Exception\CategoryInvalidArgumentException;
use Maatify\Category\Exception\CategoryNotFoundException;
use Maatify\Category\Exception\CategoryContentNotFoundException;
use Maatify\Category\Service\CategoryManagementQueryService;
use PHPUnit\Framework\TestCase;

final class CategoryManagementQueryServiceTest extends TestCase
{
    public function testGetByIdPassesTheExplicitDeletedStateAndReturnsTheCategory(): void
    {
        $category = $this->category(7);
        $reader = $this->createMock(CategoryManagementReadQueryInterface::class);
        $reader->expects(self::once())
            ->method('findById')
            ->with(7, CategoryDeletedStateEnum::DELETED_ONLY)
            ->willReturn($category);

        self::assertSame(
            $category,
            (new CategoryManagementQueryService($reader))->getById(7, CategoryDeletedStateEnum::DELETED_ONLY),
        );
    }

    public function testMissingCategoryAndContentUseTheirSpecificNotFoundExceptions(): void
    {
        $reader = $this->createStub(CategoryManagementReadQueryInterface::class);
        $reader->method('findById')->willReturn(null);
        $reader->method('findContentById')->willReturn(null);
        $service = new CategoryManagementQueryService($reader);

        try {
            $service->getById(7);
            self::fail('A missing Category must throw its not-found exception.');
        } catch (CategoryNotFoundException) {
            // Expected.
        }

        $this->expectException(CategoryContentNotFoundException::class);
        $service->getContentById(9);
    }

    public function testListCriteriaArePassedToTheDedicatedReader(): void
    {
        $categories = new CategoryCollectionDTO([$this->category(1)]);
        $contents = new CategoryContentCollectionDTO([$this->content(2)]);
        $imageAssignments = new CategoryImageAssignmentCollectionDTO([$this->imageAssignment(3)]);
        $categoryCriteria = new CategoryListCriteriaDTO(
            status: CategoryStatusEnum::INACTIVE,
            deletedState: CategoryDeletedStateEnum::INCLUDE_DELETED,
            maxResults: 2,
        );
        $contentCriteria = new CategoryContentListCriteriaDTO(
            categoryId: 1,
            deletedState: CategoryDeletedStateEnum::DELETED_ONLY,
            maxResults: 3,
        );
        $imageCriteria = new CategoryImageAssignmentListCriteriaDTO(
            categoryId: 1,
            maxResults: 3,
        );
        $reader = $this->createMock(CategoryManagementReadQueryInterface::class);
        $reader->expects(self::once())->method('listCategories')->with($categoryCriteria)->willReturn($categories);
        $reader->expects(self::once())->method('listRootCategories')->with($categoryCriteria)->willReturn($categories);
        $reader->expects(self::once())->method('listChildren')->with(1, $categoryCriteria)->willReturn($categories);
        $reader->expects(self::once())->method('listContents')->with($contentCriteria)->willReturn($contents);
        $reader->expects(self::once())
            ->method('listImageAssignments')
            ->with($imageCriteria)
            ->willReturn($imageAssignments);
        $service = new CategoryManagementQueryService($reader);

        self::assertSame($categories, $service->listCategories($categoryCriteria));
        self::assertSame($categories, $service->listRootCategories($categoryCriteria));
        self::assertSame($categories, $service->listChildren(1, $categoryCriteria));
        self::assertSame($contents, $service->listContents($contentCriteria));
        self::assertSame($imageAssignments, $service->listImageAssignments($imageCriteria));
    }

    public function testCriteriaRejectNonPositiveCategoryIds(): void
    {
        $this->expectException(CategoryInvalidArgumentException::class);

        new CategoryContentListCriteriaDTO(categoryId: 0);
    }

    public function testCriteriaRejectOutOfBoundsMaximums(): void
    {
        $this->expectException(CategoryInvalidArgumentException::class);

        new CategoryListCriteriaDTO(maxResults: CategoryListCriteriaDTO::MAX_MAX_RESULTS + 1);
    }

    private function category(int $id): CategoryDTO
    {
        $timestamp = new DateTimeImmutable('2026-01-01 00:00:00 UTC');

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
        $timestamp = new DateTimeImmutable('2026-01-01 00:00:00 UTC');

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
        $timestamp = new DateTimeImmutable('2026-01-01 00:00:00 UTC');

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
