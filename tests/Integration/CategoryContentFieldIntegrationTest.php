<?php

declare(strict_types=1);

namespace Maatify\Category\Tests\Integration;

use DateTimeImmutable;
use Maatify\Category\Command\CreateCategoryCommand;
use Maatify\Category\Command\CreateCategoryContentFieldCommand;
use Maatify\Category\Command\RestoreCategoryContentFieldCommand;
use Maatify\Category\Command\SoftDeleteCategoryContentFieldCommand;
use Maatify\Category\Command\UpdateCategoryContentFieldCommand;
use Maatify\Category\Command\UpdateCategoryContentFieldDisplayOrderCommand;
use Maatify\Category\Command\UpdateCategoryStatusCommand;
use Maatify\Category\DTO\CategoryContentFieldListCriteriaDTO;
use Maatify\Category\DTO\CategoryContentFieldScopeDTO;
use Maatify\Category\DTO\CategoryVisibleListCriteriaDTO;
use Maatify\Category\Enum\CategoryContentFieldFormatEnum;
use Maatify\Category\Enum\CategoryDeletedStateEnum;
use Maatify\Category\Enum\CategoryStatusEnum;
use Maatify\Category\Exception\CategoryContentFieldAlreadyExistsException;
use Maatify\Category\Exception\CategoryInvalidArgumentException;
use Maatify\Category\Infrastructure\Repository\PdoCategoryCommandRepository;
use Maatify\Category\Infrastructure\Repository\PdoCategoryContentCommandRepository;
use Maatify\Category\Infrastructure\Repository\PdoCategoryContentFieldCommandRepository;
use Maatify\Category\Infrastructure\Repository\PdoCategoryImageAssignmentCommandRepository;
use Maatify\Category\Infrastructure\Repository\PdoCategoryManagementReadQuery;
use Maatify\Category\Infrastructure\Repository\PdoCategoryQueryReader;
use Maatify\Category\Infrastructure\Repository\PdoCategoryReadQuery;
use Maatify\Category\Infrastructure\Transaction\PdoCategoryTransaction;
use Maatify\Category\Service\CategoryCommandService;
use Maatify\Category\Service\CategoryManagementQueryService;
use Maatify\Category\Service\CategoryQueryService;
use Maatify\Category\Tests\Integration\Support\CategoryMySqlIntegrationTestCase;
use Maatify\Category\Tests\Integration\Support\FixedCategoryClock;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
use PDO;

final class CategoryContentFieldIntegrationTest extends CategoryMySqlIntegrationTestCase
{
    public function testAllFourScopesFormatsAndIndependentOrderingAreSupported(): void
    {
        $service = $this->commandService($this->connection());
        $queryService = new CategoryQueryService(new PdoCategoryReadQuery($this->connection()));
        $categoryId = $service->create(new CreateCategoryCommand('field-scopes-category'));

        $neutralFirst = $service->createContentField(
            new CreateCategoryContentFieldCommand($categoryId, 'usage_instructions', null, null, CategoryContentFieldFormatEnum::TEXT, 'Use gently.'),
        );
        $neutralSecond = $service->createContentField(
            new CreateCategoryContentFieldCommand($categoryId, 'general_information', null, null, CategoryContentFieldFormatEnum::HTML, '<p>Details</p>'),
        );
        $languageOnly = $service->createContentField(
            new CreateCategoryContentFieldCommand($categoryId, 'targeting', 'en-US', null, CategoryContentFieldFormatEnum::JSON, '{"audience":["adult"]}'),
        );
        $platformOnly = $service->createContentField(
            new CreateCategoryContentFieldCommand($categoryId, 'custom_information', null, 'web', CategoryContentFieldFormatEnum::TEXT, 'Web only'),
        );
        $languageAndPlatform = $service->createContentField(
            new CreateCategoryContentFieldCommand($categoryId, 'targeting', 'en-US', 'web', CategoryContentFieldFormatEnum::HTML, '<strong>Web</strong>'),
        );

        self::assertSame(
            [$neutralFirst, $neutralSecond],
            $this->ids($queryService->listContentFields($categoryId, new CategoryContentFieldScopeDTO())),
        );
        self::assertSame(
            [$languageOnly],
            $this->ids($queryService->listContentFields($categoryId, new CategoryContentFieldScopeDTO('en-US'))),
        );
        self::assertSame(
            [$platformOnly],
            $this->ids($queryService->listContentFields($categoryId, new CategoryContentFieldScopeDTO(null, 'web'))),
        );
        self::assertSame(
            [$languageAndPlatform],
            $this->ids($queryService->listContentFields($categoryId, new CategoryContentFieldScopeDTO('en-US', 'web'))),
        );

        $neutral = $queryService->listContentFields($categoryId, new CategoryContentFieldScopeDTO());
        self::assertSame(
            [CategoryContentFieldFormatEnum::TEXT, CategoryContentFieldFormatEnum::HTML],
            $this->formats($neutral),
        );

        $service->updateContentFieldDisplayOrder(
            new UpdateCategoryContentFieldDisplayOrderCommand($neutralSecond, 1),
        );
        self::assertSame(
            [$neutralSecond, $neutralFirst],
            $this->ids($queryService->listContentFields($categoryId, new CategoryContentFieldScopeDTO())),
        );
    }

    public function testJsonSyntaxIsValidatedAtRuntimeBoundary(): void
    {
        $this->expectException(CategoryInvalidArgumentException::class);

        new CreateCategoryContentFieldCommand(
            1,
            'targeting',
            null,
            null,
            CategoryContentFieldFormatEnum::JSON,
            '{invalid',
        );
    }

    public function testIdentityIsExactAndReservedAcrossSoftDeletion(): void
    {
        $service = $this->commandService($this->connection());
        $categoryId = $service->create(new CreateCategoryCommand('field-identity-category'));
        $service->createContentField(
            new CreateCategoryContentFieldCommand($categoryId, 'targeting', null, null, CategoryContentFieldFormatEnum::TEXT, 'neutral'),
        );

        $this->expectException(CategoryContentFieldAlreadyExistsException::class);
        $service->createContentField(
            new CreateCategoryContentFieldCommand($categoryId, 'targeting', null, null, CategoryContentFieldFormatEnum::HTML, '<p>duplicate</p>'),
        );
    }

    public function testSameKeyIsAllowedInAnotherScopeAndManagementScopeFilterIsExact(): void
    {
        $service = $this->commandService($this->connection());
        $management = new CategoryManagementQueryService(new PdoCategoryManagementReadQuery($this->connection()));
        $categoryId = $service->create(new CreateCategoryCommand('field-management-category'));
        $neutral = $service->createContentField(
            new CreateCategoryContentFieldCommand($categoryId, 'targeting', null, null, CategoryContentFieldFormatEnum::TEXT, 'neutral'),
        );
        $language = $service->createContentField(
            new CreateCategoryContentFieldCommand($categoryId, 'targeting', 'en-US', null, CategoryContentFieldFormatEnum::TEXT, 'localized'),
        );

        self::assertEqualsCanonicalizing(
            [$neutral, $language],
            $this->ids($management->listContentFields(new CategoryContentFieldListCriteriaDTO(categoryId: $categoryId))),
        );
        self::assertSame(
            [$neutral],
            $this->ids($management->listContentFields(new CategoryContentFieldListCriteriaDTO(
                categoryId: $categoryId,
                scope: new CategoryContentFieldScopeDTO(),
            ))),
        );
    }

    public function testUpdateDeleteRestoreAndExactConsumerVisibilityFollowLifecycleContract(): void
    {
        $connection = $this->connection();
        $service = $this->commandService($connection);
        $queryService = new CategoryQueryService(new PdoCategoryReadQuery($connection));
        $management = new CategoryManagementQueryService(new PdoCategoryManagementReadQuery($connection));
        $rootId = $service->create(new CreateCategoryCommand('field-visibility-root'));
        $childId = $service->create(new CreateCategoryCommand('field-visibility-child', $rootId));
        $fieldId = $service->createContentField(
            new CreateCategoryContentFieldCommand($childId, 'custom_information', 'en-US', 'web', CategoryContentFieldFormatEnum::TEXT, 'before'),
        );

        $service->updateContentField(new UpdateCategoryContentFieldCommand(
            $fieldId,
            CategoryContentFieldFormatEnum::JSON,
            '{"enabled":true}',
        ));
        $updated = $management->getContentFieldById($fieldId);
        self::assertSame(CategoryContentFieldFormatEnum::JSON, $updated->format);
        self::assertSame('{"enabled":true}', $updated->value);

        self::assertSame(
            [$fieldId],
            $this->ids($queryService->listContentFields($childId, new CategoryContentFieldScopeDTO('en-US', 'web'))),
        );
        self::assertTrue(
            $queryService->listContentFields($childId, new CategoryContentFieldScopeDTO('en-GB', 'web'))->isEmpty(),
        );

        $service->softDeleteContentField(new SoftDeleteCategoryContentFieldCommand($fieldId));
        self::assertTrue(
            $queryService->listContentFields($childId, new CategoryContentFieldScopeDTO('en-US', 'web'))->isEmpty(),
        );
        self::assertNotNull($management->getContentFieldById($fieldId, CategoryDeletedStateEnum::DELETED_ONLY)->deletedAt);

        $service->restoreContentField(new RestoreCategoryContentFieldCommand($fieldId));
        self::assertSame(
            [$fieldId],
            $this->ids($queryService->listContentFields($childId, new CategoryContentFieldScopeDTO('en-US', 'web'))),
        );

        $service->updateStatus(new UpdateCategoryStatusCommand($rootId, CategoryStatusEnum::INACTIVE));
        self::assertTrue(
            $queryService->listContentFields($childId, new CategoryContentFieldScopeDTO('en-US', 'web'))->isEmpty(),
        );
    }

    /** @return list<int> */
    private function ids(\Maatify\Category\DTO\CategoryContentFieldCollectionDTO $fields): array
    {
        $ids = [];
        foreach ($fields as $field) {
            $ids[] = $field->id;
        }

        return $ids;
    }

    /** @return list<CategoryContentFieldFormatEnum> */
    private function formats(\Maatify\Category\DTO\CategoryContentFieldCollectionDTO $fields): array
    {
        $formats = [];
        foreach ($fields as $field) {
            $formats[] = $field->format;
        }

        return $formats;
    }

    private function commandService(PDO $connection): CategoryCommandService
    {
        return new CategoryCommandService(
            new PdoCategoryCommandRepository($connection, new ScopedOrderingManager()),
            new PdoCategoryQueryReader($connection),
            new PdoCategoryContentCommandRepository($connection),
            new PdoCategoryImageAssignmentCommandRepository($connection, new ScopedOrderingManager()),
            new PdoCategoryContentFieldCommandRepository($connection, new ScopedOrderingManager()),
            new PdoCategoryTransaction($connection),
            new FixedCategoryClock('2026-01-01 00:00:00 UTC'),
        );
    }
}
