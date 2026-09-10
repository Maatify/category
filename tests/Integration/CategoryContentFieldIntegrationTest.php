<?php

declare(strict_types=1);

namespace Maatify\Category\Tests\Integration;

use Maatify\Category\Lifecycle\Command\CreateCategoryCommand;
use Maatify\Category\ContentField\Mutation\Command\CreateCategoryContentFieldCommand;
use Maatify\Category\ContentField\Mutation\Command\RestoreCategoryContentFieldCommand;
use Maatify\Category\ContentField\Mutation\Command\SoftDeleteCategoryContentFieldCommand;
use Maatify\Category\ContentField\Mutation\Command\UpdateCategoryContentFieldCommand;
use Maatify\Category\ContentField\Ordering\Command\UpdateCategoryContentFieldDisplayOrderCommand;
use Maatify\Category\Lifecycle\Command\UpdateCategoryStatusCommand;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldListCriteriaDTO;
use Maatify\Category\ContentField\CategoryContentFieldScopeDTO;
use Maatify\Category\ContentField\Mutation\Enum\CategoryContentFieldFormatEnum;
use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;
use Maatify\Category\Lifecycle\Enum\CategoryStatusEnum;
use Maatify\Category\ContentField\Mutation\Exception\CategoryContentFieldAlreadyExistsException;
use Maatify\Category\Common\Exception\CategoryInvalidArgumentException;
use Maatify\Category\Infrastructure\PdoCategoryCommandRepository;
use Maatify\Category\Content\Infrastructure\PdoCategoryContentCommandRepository;
use Maatify\Category\ContentField\Infrastructure\PdoCategoryContentFieldCommandRepository;
use Maatify\Category\ImageAssignment\Infrastructure\PdoCategoryImageAssignmentCommandRepository;
use Maatify\Category\Query\Infrastructure\PdoCategoryManagementReadQuery;
use Maatify\Category\Query\Infrastructure\PdoCategoryQueryReader;
use Maatify\Category\Query\Infrastructure\PdoCategoryReadQuery;
use Maatify\Category\Api\Service\CategoryCommandService;
use Maatify\Category\Api\Service\CategoryManagementQueryService;
use Maatify\Category\Api\Service\CategoryQueryService;
use Maatify\Category\Tests\Integration\Support\CategoryMySqlIntegrationTestCase;
use Maatify\Category\Tests\Integration\Support\FixedCategoryClock;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
use Maatify\Persistence\Pdo\Transaction\PdoTransactionRunner;
use PDO;

final class CategoryContentFieldIntegrationTest extends CategoryMySqlIntegrationTestCase
{
    public function testAllFourScopesFormatsAndIndependentOrderingAreSupported(): void
    {
        $service = $this->commandService($this->connection());
        $clock = new FixedCategoryClock();
        $queryService = new CategoryQueryService(new PdoCategoryReadQuery($this->connection(), $clock));
        $management = new CategoryManagementQueryService(new PdoCategoryManagementReadQuery($this->connection(), $clock));
        $categoryId = $service->create(new CreateCategoryCommand('field-scopes-category'));

        $neutralFirst = $service->createContentField(
            new CreateCategoryContentFieldCommand($categoryId, 'alpha_information', null, null, CategoryContentFieldFormatEnum::TEXT, 'Use gently.'),
        );
        $neutralSecond = $service->createContentField(
            new CreateCategoryContentFieldCommand($categoryId, 'zeta_instructions', null, null, CategoryContentFieldFormatEnum::HTML, '<p>Details</p>'),
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
            new UpdateCategoryContentFieldDisplayOrderCommand($neutralFirst, 2),
        );
        self::assertSame(
            [$neutralSecond, $neutralFirst],
            $this->ids($queryService->listContentFields($categoryId, new CategoryContentFieldScopeDTO())),
        );
        self::assertSame(
            [$neutralSecond, $neutralFirst],
            $this->ids($management->listContentFields(new CategoryContentFieldListCriteriaDTO(
                categoryId: $categoryId,
                scope: new CategoryContentFieldScopeDTO(),
            ))),
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
        $fieldId = $service->createContentField(
            new CreateCategoryContentFieldCommand($categoryId, 'targeting', null, null, CategoryContentFieldFormatEnum::TEXT, 'neutral'),
        );

        $service->softDeleteContentField(new SoftDeleteCategoryContentFieldCommand($fieldId));

        try {
            $service->createContentField(
                new CreateCategoryContentFieldCommand($categoryId, 'targeting', null, null, CategoryContentFieldFormatEnum::HTML, '<p>duplicate</p>'),
            );
            self::fail('A soft-deleted field must continue reserving its exact identity.');
        } catch (CategoryContentFieldAlreadyExistsException) {
        }

        $service->restoreContentField(new RestoreCategoryContentFieldCommand($fieldId));
        $restored = (new CategoryManagementQueryService(
            new PdoCategoryManagementReadQuery($this->connection(), new FixedCategoryClock()),
        ))->getContentFieldById($fieldId);
        self::assertSame($fieldId, $restored->id);
        self::assertSame($categoryId, $restored->categoryId);
        self::assertSame('targeting', $restored->fieldKey);
        self::assertNull($restored->languageCode);
        self::assertNull($restored->platform);
        self::assertNull($restored->deletedAt);
    }

    public function testSameKeyIsAllowedInAnotherScopeAndManagementScopeFilterIsExact(): void
    {
        $service = $this->commandService($this->connection());
        $management = new CategoryManagementQueryService(new PdoCategoryManagementReadQuery($this->connection(), new FixedCategoryClock()));
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
        $clock = new FixedCategoryClock();
        $queryService = new CategoryQueryService(new PdoCategoryReadQuery($connection, $clock));
        $management = new CategoryManagementQueryService(new PdoCategoryManagementReadQuery($connection, $clock));
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
    private function ids(\Maatify\Category\ContentField\Query\DTO\CategoryContentFieldCollectionDTO $fields): array
    {
        $ids = [];
        foreach ($fields as $field) {
            $ids[] = $field->id;
        }

        return $ids;
    }

    /** @return list<CategoryContentFieldFormatEnum> */
    private function formats(\Maatify\Category\ContentField\Query\DTO\CategoryContentFieldCollectionDTO $fields): array
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
            new PdoCategoryQueryReader($connection, new FixedCategoryClock()),
            new PdoCategoryContentCommandRepository($connection),
            new PdoCategoryImageAssignmentCommandRepository($connection, new ScopedOrderingManager()),
            new PdoCategoryContentFieldCommandRepository($connection, new ScopedOrderingManager()),
            new PdoTransactionRunner($connection),
            new FixedCategoryClock('2026-01-01 00:00:00 Africa/Cairo'),
        );
    }
}
