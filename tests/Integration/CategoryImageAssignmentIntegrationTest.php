<?php

declare(strict_types=1);

namespace Maatify\Category\Tests\Integration;

use Maatify\Category\Lifecycle\Command\CreateCategoryCommand;
use Maatify\Category\ImageRole\Lifecycle\Command\CreateCategoryImageRoleCommand;
use Maatify\Category\ImageAssignment\Assignment\Command\CreateCategoryImageAssignmentCommand;
use Maatify\Category\ImageAssignment\Default\Command\ClearCategoryImageAssignmentDefaultCommand;
use Maatify\Category\Lifecycle\Command\SoftDeleteCategoryCommand;
use Maatify\Category\ImageAssignment\Lifecycle\Command\SoftDeleteCategoryImageAssignmentCommand;
use Maatify\Category\ImageAssignment\Lifecycle\Command\RestoreCategoryImageAssignmentCommand;
use Maatify\Category\ImageAssignment\Default\Command\SetCategoryImageAssignmentDefaultCommand;
use Maatify\Category\ImageAssignment\Ordering\Command\UpdateCategoryImageAssignmentDisplayOrderCommand;
use Maatify\Category\Lifecycle\Command\UpdateCategoryStatusCommand;
use Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentListCriteriaDTO;
use Maatify\Category\ImageAssignment\CategoryImageAssignmentScopeDTO;
use Maatify\Category\Query\DTO\CategoryVisibleListCriteriaDTO;
use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;
use Maatify\Category\Lifecycle\Enum\CategoryStatusEnum;
use Maatify\Category\ImageAssignment\Assignment\Exception\CategoryImageAssignmentAlreadyExistsException;
use Maatify\Category\ImageAssignment\Assignment\Exception\CategoryImageAssignmentNotFoundException;
use Maatify\Category\Lifecycle\Exception\CategoryNotFoundException;
use Maatify\Category\Infrastructure\PdoCategoryCommandRepository;
use Maatify\Category\Content\Infrastructure\PdoCategoryContentCommandRepository;
use Maatify\Category\ImageAssignment\Infrastructure\PdoCategoryImageAssignmentCommandRepository;
use Maatify\Category\ImageRole\Infrastructure\PdoCategoryImageRoleCommandRepository;
use Maatify\Category\ContentField\Infrastructure\PdoCategoryContentFieldCommandRepository;
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

final class CategoryImageAssignmentIntegrationTest extends CategoryMySqlIntegrationTestCase
{
    public function testDefaultIsExplicitPerExactScopeAndIndependentFromOrdering(): void
    {
        $connection = $this->connection();
        $service = $this->commandService($connection);
        $clock = new FixedCategoryClock();
        $mutationReader = new PdoCategoryQueryReader($connection, $clock);
        $queryService = new CategoryQueryService(new PdoCategoryReadQuery($connection, $clock));
        $managementService = new CategoryManagementQueryService(new PdoCategoryManagementReadQuery($connection, $clock));
        $categoryId = $service->create(new CreateCategoryCommand('image-default-category'));
        $roleId = $service->createImageRole(new CreateCategoryImageRoleCommand('default-gallery'));

        $neutralFirst = $service->createImageAssignment(
            new CreateCategoryImageAssignmentCommand($categoryId, 100),
        );
        $neutralSecond = $service->createImageAssignment(
            new CreateCategoryImageAssignmentCommand($categoryId, 101),
        );
        $languageOnly = $service->createImageAssignment(
            new CreateCategoryImageAssignmentCommand($categoryId, 102, 'en-US'),
        );
        $platformOnly = $service->createImageAssignment(
            new CreateCategoryImageAssignmentCommand($categoryId, 103, null, 'web'),
        );
        $roleScoped = $service->createImageAssignment(
            new CreateCategoryImageAssignmentCommand($categoryId, 104, 'en-US', 'web', $roleId),
        );

        self::assertSame(0, $this->defaultCount($connection));
        self::assertFalse($managementService->getImageAssignmentById($neutralFirst)->isDefault);

        $service->setImageAssignmentDefault(new SetCategoryImageAssignmentDefaultCommand($neutralFirst));
        self::assertSame($neutralFirst, $this->defaultIdForScope($connection, $categoryId, null, null, null));
        self::assertTrue($managementService->getImageAssignmentById($neutralFirst)->isDefault);
        $hydratedAssignment = $mutationReader->findImageAssignmentById($neutralFirst);
        self::assertNotNull($hydratedAssignment);
        self::assertTrue($hydratedAssignment->isDefault);
        $visibleDefaultId = null;
        foreach ($queryService->listImageAssignments($categoryId, new CategoryImageAssignmentScopeDTO()) as $assignment) {
            if ($assignment->isDefault) {
                $visibleDefaultId = $assignment->id;
            }
        }
        self::assertSame($neutralFirst, $visibleDefaultId);

        $service->setImageAssignmentDefault(new SetCategoryImageAssignmentDefaultCommand($neutralSecond));
        self::assertSame($neutralSecond, $this->defaultIdForScope($connection, $categoryId, null, null, null));
        self::assertFalse($managementService->getImageAssignmentById($neutralFirst)->isDefault);
        self::assertTrue($managementService->getImageAssignmentById($neutralSecond)->isDefault);

        $service->setImageAssignmentDefault(new SetCategoryImageAssignmentDefaultCommand($languageOnly));
        $service->setImageAssignmentDefault(new SetCategoryImageAssignmentDefaultCommand($platformOnly));
        $service->setImageAssignmentDefault(new SetCategoryImageAssignmentDefaultCommand($roleScoped));
        self::assertSame(4, $this->defaultCount($connection));
        self::assertSame($languageOnly, $this->defaultIdForScope($connection, $categoryId, 'en-US', null, null));
        self::assertSame($platformOnly, $this->defaultIdForScope($connection, $categoryId, null, 'web', null));
        self::assertSame($roleScoped, $this->defaultIdForScope($connection, $categoryId, 'en-US', 'web', $roleId));

        $service->clearImageAssignmentDefault(new ClearCategoryImageAssignmentDefaultCommand($neutralSecond));
        self::assertSame(3, $this->defaultCount($connection));
        self::assertNull($this->defaultIdForScope($connection, $categoryId, null, null, null));

        $service->setImageAssignmentDefault(new SetCategoryImageAssignmentDefaultCommand($neutralFirst));
        $service->updateImageAssignmentDisplayOrder(
            new UpdateCategoryImageAssignmentDisplayOrderCommand($neutralFirst, 2),
        );
        self::assertSame(
            [$neutralSecond, $neutralFirst],
            $this->ids($queryService->listImageAssignments($categoryId, new CategoryImageAssignmentScopeDTO())),
        );
        self::assertTrue($managementService->getImageAssignmentById($neutralFirst)->isDefault);
        self::assertSame(4, $this->defaultCount($connection));
    }

    public function testSoftDeleteClearsDefaultWithoutPromotionAndRestoreRemainsNonDefault(): void
    {
        $connection = $this->connection();
        $service = $this->commandService($connection);
        $clock = new FixedCategoryClock();
        $queryService = new CategoryQueryService(new PdoCategoryReadQuery($connection, $clock));
        $managementService = new CategoryManagementQueryService(new PdoCategoryManagementReadQuery($connection, $clock));
        $categoryId = $service->create(new CreateCategoryCommand('image-default-lifecycle-category'));
        $firstId = $service->createImageAssignment(new CreateCategoryImageAssignmentCommand($categoryId, 200));
        $secondId = $service->createImageAssignment(new CreateCategoryImageAssignmentCommand($categoryId, 201));

        $service->setImageAssignmentDefault(new SetCategoryImageAssignmentDefaultCommand($firstId));
        $service->softDeleteImageAssignment(new SoftDeleteCategoryImageAssignmentCommand($firstId));

        self::assertSame(0, $this->defaultCount($connection));
        self::assertFalse(
            $managementService->getImageAssignmentById($firstId, CategoryDeletedStateEnum::DELETED_ONLY)->isDefault,
        );
        self::assertFalse($managementService->getImageAssignmentById($secondId)->isDefault);
        self::assertSame(
            [$secondId],
            $this->ids($queryService->listImageAssignments($categoryId, new CategoryImageAssignmentScopeDTO())),
        );

        try {
            $service->setImageAssignmentDefault(new SetCategoryImageAssignmentDefaultCommand($firstId));
            self::fail('A soft-deleted assignment must not be eligible for a default.');
        } catch (CategoryImageAssignmentNotFoundException) {
        }

        $service->restoreImageAssignment(new RestoreCategoryImageAssignmentCommand($firstId));
        self::assertFalse($managementService->getImageAssignmentById($firstId)->isDefault);
        self::assertSame(0, $this->defaultCount($connection));
    }

    public function testAllFourScopesHaveIndependentOrderingAndExactVisibleReads(): void
    {
        $connection = $this->connection();
        $service = $this->commandService($connection);
        $queryService = new CategoryQueryService(new PdoCategoryReadQuery($connection, new FixedCategoryClock()));
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
        $clock = new FixedCategoryClock();
        $queryService = new CategoryQueryService(new PdoCategoryReadQuery($connection, $clock));
        $managementService = new CategoryManagementQueryService(new PdoCategoryManagementReadQuery($connection, $clock));
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
        $queryService = new CategoryQueryService(new PdoCategoryReadQuery($connection, new FixedCategoryClock()));
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
                (new CategoryManagementQueryService(new PdoCategoryManagementReadQuery($connection, new FixedCategoryClock())))
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
        $queryService = new CategoryQueryService(new PdoCategoryReadQuery($connection, new FixedCategoryClock()));
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
        $managementService = new CategoryManagementQueryService(new PdoCategoryManagementReadQuery($connection, new FixedCategoryClock()));
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
    private function ids(\Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentCollectionDTO $assignments): array
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
            new PdoCategoryQueryReader($connection, new FixedCategoryClock()),
            new PdoCategoryContentCommandRepository($connection),
            new PdoCategoryImageAssignmentCommandRepository($connection, new ScopedOrderingManager()),
            new PdoCategoryContentFieldCommandRepository($connection, new ScopedOrderingManager()),
            new PdoTransactionRunner($connection),
            new FixedCategoryClock('2026-01-01 00:00:00 Africa/Cairo'),
            new PdoCategoryImageRoleCommandRepository($connection),
        );
    }

    private function defaultCount(PDO $connection): int
    {
        $statement = $connection->query(
            'SELECT COUNT(*) FROM `maa_category_category_image_assignments` '
            . 'WHERE `deleted_at` IS NULL AND `is_default` = 1',
        );

        if ($statement === false) {
            self::fail('Unable to count active default Image Assignments.');
        }

        return (int) $statement->fetchColumn();
    }

    private function defaultIdForScope(
        PDO $connection,
        int $categoryId,
        ?string $languageCode,
        ?string $platform,
        ?int $roleId,
    ): ?int {
        $statement = $connection->prepare(
            'SELECT `id` FROM `maa_category_category_image_assignments` '
            . 'WHERE `category_id` = :category_id '
            . 'AND `language_code` <=> :language_code '
            . 'AND `platform` <=> :platform '
            . 'AND `role_id` <=> :role_id '
            . 'AND `deleted_at` IS NULL AND `is_default` = 1 LIMIT 1',
        );
        $statement->execute([
            'category_id' => $categoryId,
            'language_code' => $languageCode,
            'platform' => $platform,
            'role_id' => $roleId,
        ]);
        $id = $statement->fetchColumn();

        if ($id === false) {
            return null;
        }
        if (!is_int($id) && !is_string($id)) {
            self::fail('The default assignment identity must be scalar.');
        }

        return (int) $id;
    }
}
