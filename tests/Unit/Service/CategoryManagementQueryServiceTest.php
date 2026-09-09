<?php

declare(strict_types=1);

namespace Maatify\Category\Tests\Unit\Service;

use DateTimeImmutable;
use Maatify\Category\Contract\CategoryManagementReadQueryInterface;
use Maatify\Category\DTO\CategoryCollectionDTO;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CategoryListCriteriaDTO;
use Maatify\Category\DTO\CategoryTranslationCollectionDTO;
use Maatify\Category\DTO\CategoryTranslationDTO;
use Maatify\Category\DTO\CategoryTranslationListCriteriaDTO;
use Maatify\Category\Enum\CategoryDeletedStateEnum;
use Maatify\Category\Enum\CategoryStatusEnum;
use Maatify\Category\Exception\CategoryInvalidArgumentException;
use Maatify\Category\Exception\CategoryNotFoundException;
use Maatify\Category\Exception\CategoryTranslationNotFoundException;
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

    public function testMissingCategoryAndTranslationUseTheirSpecificNotFoundExceptions(): void
    {
        $reader = $this->createStub(CategoryManagementReadQueryInterface::class);
        $reader->method('findById')->willReturn(null);
        $reader->method('findTranslationById')->willReturn(null);
        $service = new CategoryManagementQueryService($reader);

        try {
            $service->getById(7);
            self::fail('A missing Category must throw its not-found exception.');
        } catch (CategoryNotFoundException) {
            // Expected.
        }

        $this->expectException(CategoryTranslationNotFoundException::class);
        $service->getTranslationById(9);
    }

    public function testListCriteriaArePassedToTheDedicatedReader(): void
    {
        $categories = new CategoryCollectionDTO([$this->category(1)]);
        $translations = new CategoryTranslationCollectionDTO([$this->translation(2)]);
        $categoryCriteria = new CategoryListCriteriaDTO(
            status: CategoryStatusEnum::INACTIVE,
            deletedState: CategoryDeletedStateEnum::INCLUDE_DELETED,
            maxResults: 2,
        );
        $translationCriteria = new CategoryTranslationListCriteriaDTO(
            categoryId: 1,
            deletedState: CategoryDeletedStateEnum::DELETED_ONLY,
            maxResults: 3,
        );
        $reader = $this->createMock(CategoryManagementReadQueryInterface::class);
        $reader->expects(self::once())->method('listCategories')->with($categoryCriteria)->willReturn($categories);
        $reader->expects(self::once())->method('listRootCategories')->with($categoryCriteria)->willReturn($categories);
        $reader->expects(self::once())->method('listChildren')->with(1, $categoryCriteria)->willReturn($categories);
        $reader->expects(self::once())->method('listTranslations')->with($translationCriteria)->willReturn($translations);
        $service = new CategoryManagementQueryService($reader);

        self::assertSame($categories, $service->listCategories($categoryCriteria));
        self::assertSame($categories, $service->listRootCategories($categoryCriteria));
        self::assertSame($categories, $service->listChildren(1, $categoryCriteria));
        self::assertSame($translations, $service->listTranslations($translationCriteria));
    }

    public function testCriteriaRejectNonPositiveCategoryIds(): void
    {
        $this->expectException(CategoryInvalidArgumentException::class);

        new CategoryTranslationListCriteriaDTO(categoryId: 0);
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

    private function translation(int $id): CategoryTranslationDTO
    {
        $timestamp = new DateTimeImmutable('2026-01-01 00:00:00 UTC');

        return new CategoryTranslationDTO(
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
}
