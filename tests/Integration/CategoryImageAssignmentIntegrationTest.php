<?php

declare(strict_types=1);

namespace Maatify\Category\Tests\Integration;

use Maatify\Category\Command\CreateCategoryCommand;
use Maatify\Category\Command\CreateCategoryImageAssignmentCommand;
use Maatify\Category\Command\SoftDeleteCategoryCommand;
use Maatify\Category\Command\SoftDeleteCategoryImageAssignmentCommand;
use Maatify\Category\Command\RestoreCategoryImageAssignmentCommand;
use Maatify\Category\Command\UpdateCategoryImageAssignmentDisplayOrderCommand;
use Maatify\Category\Command\UpdateCategoryStatusCommand;
use Maatify\Category\DTO\CategoryImageAssignmentListCriteriaDTO;
use Maatify\Category\DTO\CategoryImageAssignmentScopeDTO;
use Maatify\Category\DTO\CategoryVisibleListCriteriaDTO;
use Maatify\Category\Enum\CategoryDeletedStateEnum;
use Maatify\Category\Enum\CategoryStatusEnum;
use Maatify\Category\Exception\CategoryImageAssignmentAlreadyExistsException;
use Maatify\Category\Exception\CategoryNotFoundException;
use Maatify\Category\Infrastructure\Repository\PdoCategoryCommandRepository;
use Maatify\Category\Infrastructure\Repository\PdoCategoryContentCommandRepository;
use Maatify\Category\Infrastructure\Repository\PdoCategoryImageAssignmentCommandRepository;
use Maatify\Category\Infrastructure\Repository\PdoCategoryContentFieldCommandRepository;
use Maatify\Category\Infrastructure\Repository\PdoCategoryManagementReadQuery;
use Maatify\Category\Infrastructure\Repository\PdoCategoryQueryReader;
use Maatify\Category\Infrastructure\Repository\PdoCategoryReadQuery;
use Maatify\Category\Service\CategoryCommandService;
use Maatify\Category\Service\CategoryManagementQueryService;
use Maatify\Category\Service\CategoryQueryService;
use Maatify\Category\Tests\Integration\Support\CategoryMySqlIntegrationTestCase;
use Maatify\Category\Tests\Integration\Support\FixedCategoryClock;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
use Maatify\Persistence\Pdo\Transaction\PdoTransactionRunner;
use PDO;

final class CategoryImageAssignmentIntegrationTest extends CategoryMySqlIntegrationTestCase
{
    public function testAllFourScopesHaveIndependentOrderingAndExactVisibleReads(): void
    {
        $connection = $this->connection();
        $service = $this->commandService($connection);
        $queryService = new CategoryQueryService(new PdoCategoryReadQuery($connection));
        $categoryId = $service->create(new CreateCategoryCommand('image-scopes-category'));

        $neutralFirst = $service->createImageAssignment(
            new CreateCategoryImageAssignmentCommand($categoryId, 100),
        );
        $neutralSecond = $service->createImageAssignment(
            new CreateCategoryImageAssignmentCommand($categoryId, 101),
        );
        $languageOnly = $service->createImageAssignment(
            new CreateCategoryImageAssignmentCommand($categoryId, 100, 'en-US'),
        );
        $platformOnly = $service->createImageAssignment(
            new CreateCategoryImageAssignmentCommand($categoryId, 100, null, 'web'),
        );
        $languageAndPlatform = $service->createImageAssignment(
            new CreateCategoryImageAssignmentCommand($categoryId, 100, 'en-US', 'web'),
        );

        self::assertSame(
            [$neutralFirst, $neutralSecond],
            $this->ids($queryService->listImageAssignments($categoryId, new CategoryImageAssignmentScopeDTO())),
        );
        self::assertSame(
            [$languageOnly],
            $this->ids($queryService->listImageAssignments($categoryId, new CategoryImageAssignmentScopeDTO('en-US'))),
        );
        self::assertSame(
            [$platformOnly],
            $this->ids($queryService->listImageAssignments($categoryId, new CategoryImageAssignmentScopeDTO(null, 'web'))),
        );
        self::assertSame(
            [$languageAndPlatform],
            $this->ids($queryService->listImageAssignments($categoryId, new CategoryImageAssignmentScopeDTO('en-US', 'web'))),
        );

        $service->updateImageAssignmentDisplayOrder(
            new UpdateCategoryImageAssignmentDisplayOrderCommand($neutralSecond, 1),
        );
        self::assertSame(
            [$neutralSecond, $neutralFirst],
            $this->ids($queryService->listImageAssignments($categoryId, new CategoryImageAssignmentScopeDTO())),
        );
    }

    public function testSoftDeleteRestoreAndStableIdentityAreIndependentFromParentVisibility(): void
    {
        $connection = $this->connection();
        $service = $this->commandService($connection);
        $queryService = new CategoryQueryService(new PdoCategoryReadQuery($connection));
        $managementService = new CategoryManagementQueryService(new PdoCategoryManagementReadQuery($connection));
        $categoryId = $service->create(new CreateCategoryCommand('image-lifecycle-category'));
        $assignmentId = $service->createImageAssignment(
            new CreateCategoryImageAssignmentCommand($categoryId, 300, 'en-US', 'web'),
        );

        $service->softDeleteImageAssignment(new SoftDeleteCategoryImageAssignmentCommand($assignmentId));
        self::assertTrue(
            $queryService->listImageAssignments(
                $categoryId,
                new CategoryImageAssignmentScopeDTO('en-US', 'web'),
            )->isEmpty(),
        );
        self::assertNotNull(
            $managementService->getImageAssignmentById($assignmentId, CategoryDeletedStateEnum::DELETED_ONLY)->deletedAt,
        );

        $this->expectException(CategoryImageAssignmentAlreadyExistsException::class);
        $service->createImageAssignment(
            new CreateCategoryImageAssignmentCommand($categoryId, 300, 'en-US', 'web'),
        );
    }

    public function testDeletedAssignmentCanBeRestoredAndInactiveParentDoesNotBlockCreate(): void
    {
        $connection = $this->connection();
        $service = $this->commandService($connection);
        $queryService = new CategoryQueryService(new PdoCategoryReadQuery($connection));
        $categoryId = $service->create(new CreateCategoryCommand('image-parent-state-category'));
        $assignmentId = $service->createImageAssignment(
            new CreateCategoryImageAssignmentCommand($categoryId, 301),
        );

        $service->softDeleteImageAssignment(new SoftDeleteCategoryImageAssignmentCommand($assignmentId));
        $service->restoreImageAssignment(new RestoreCategoryImageAssignmentCommand($assignmentId));
        self::assertSame(
            [$assignmentId],
            $this->ids($queryService->listImageAssignments($categoryId, new CategoryImageAssignmentScopeDTO())),
        );

        $service->updateStatus(new UpdateCategoryStatusCommand($categoryId, CategoryStatusEnum::INACTIVE));
        $inactiveParentAssignment = $service->createImageAssignment(
            new CreateCategoryImageAssignmentCommand($categoryId, 302),
        );
        self::assertTrue(
            $queryService->listImageAssignments($categoryId, new CategoryImageAssignmentScopeDTO())->isEmpty(),
        );
        self::assertSame(
            [$assignmentId, $inactiveParentAssignment],
            $this->ids(
                (new CategoryManagementQueryService(new PdoCategoryManagementReadQuery($connection)))
                    ->listImageAssignments(new CategoryImageAssignmentListCriteriaDTO(categoryId: $categoryId)),
            ),
        );

        $service->softDelete(new SoftDeleteCategoryCommand($categoryId));
        $service->updateImageAssignmentDisplayOrder(
            new UpdateCategoryImageAssignmentDisplayOrderCommand($inactiveParentAssignment, 1),
        );
        $service->softDeleteImageAssignment(new SoftDeleteCategoryImageAssignmentCommand($inactiveParentAssignment));
        $service->restoreImageAssignment(new RestoreCategoryImageAssignmentCommand($inactiveParentAssignment));
        $this->expectException(CategoryNotFoundException::class);
        $service->createImageAssignment(new CreateCategoryImageAssignmentCommand($categoryId, 303));
    }

    public function testVisibleReadsUseExactScopeWithoutFallbackAndRespectAncestors(): void
    {
        $connection = $this->connection();
        $service = $this->commandService($connection);
        $queryService = new CategoryQueryService(new PdoCategoryReadQuery($connection));
        $rootId = $service->create(new CreateCategoryCommand('image-visibility-root'));
        $childId = $service->create(new CreateCategoryCommand('image-visibility-child', $rootId));
        $assignmentId = $service->createImageAssignment(
            new CreateCategoryImageAssignmentCommand($childId, 400, 'en-US', 'web'),
        );

        self::assertSame(
            [$assignmentId],
            $this->ids($queryService->listImageAssignments($childId, new CategoryImageAssignmentScopeDTO('en-US', 'web'))),
        );
        self::assertTrue(
            $queryService->listImageAssignments($childId, new CategoryImageAssignmentScopeDTO('en-GB', 'web'))->isEmpty(),
        );
        self::assertTrue(
            $queryService->listImageAssignments($childId, new CategoryImageAssignmentScopeDTO())->isEmpty(),
        );

        $service->updateStatus(new UpdateCategoryStatusCommand($rootId, CategoryStatusEnum::INACTIVE));
        self::assertTrue(
            $queryService->listImageAssignments($childId, new CategoryImageAssignmentScopeDTO('en-US', 'web'))->isEmpty(),
        );
    }

    public function testManagementReadsDistinguishNoScopeFilterFromExactNullScope(): void
    {
        $connection = $this->connection();
        $service = $this->commandService($connection);
        $managementService = new CategoryManagementQueryService(new PdoCategoryManagementReadQuery($connection));
        $categoryId = $service->create(new CreateCategoryCommand('image-management-category'));
        $neutral = $service->createImageAssignment(new CreateCategoryImageAssignmentCommand($categoryId, 500));
        $neutralSecond = $service->createImageAssignment(new CreateCategoryImageAssignmentCommand($categoryId, 501));
        $service->createImageAssignment(new CreateCategoryImageAssignmentCommand($categoryId, 502, 'en-US'));
        $service->createImageAssignment(new CreateCategoryImageAssignmentCommand($categoryId, 503, null, 'web'));

        self::assertSame(
            4,
            $managementService->listImageAssignments(
                new CategoryImageAssignmentListCriteriaDTO(categoryId: $categoryId),
            )->count(),
        );
        self::assertSame(
            [$neutral, $neutralSecond],
            $this->ids($managementService->listImageAssignments(
                new CategoryImageAssignmentListCriteriaDTO(
                    categoryId: $categoryId,
                    scope: new CategoryImageAssignmentScopeDTO(),
                ),
            )),
        );
    }

    /** @return list<int> */
    private function ids(\Maatify\Category\DTO\CategoryImageAssignmentCollectionDTO $assignments): array
    {
        $ids = [];
        foreach ($assignments as $assignment) {
            $ids[] = $assignment->id;
        }

        return $ids;
    }

    private function commandService(PDO $connection): CategoryCommandService
    {
        return new CategoryCommandService(
            new PdoCategoryCommandRepository($connection, new ScopedOrderingManager()),
            new PdoCategoryQueryReader($connection),
            new PdoCategoryContentCommandRepository($connection),
            new PdoCategoryImageAssignmentCommandRepository($connection, new ScopedOrderingManager()),
            new PdoCategoryContentFieldCommandRepository($connection, new ScopedOrderingManager()),
            new PdoTransactionRunner($connection),
            new FixedCategoryClock('2026-01-01 00:00:00 UTC'),
        );
    }
}
