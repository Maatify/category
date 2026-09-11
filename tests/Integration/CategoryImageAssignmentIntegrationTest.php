<?php

declare(strict_types=1);

namespace Maatify\Category\Tests\Integration;

use Maatify\Category\Factory\CategoryFactory;
use Maatify\Category\Api\CategoryApiInterface;
use Maatify\Category\ImageAssignment\Api\Contract\ImageAssignmentApiInterface;
use Maatify\Category\ImageRole\Api\Contract\ImageRoleApiInterface;
use Maatify\Category\Lifecycle\Command\CreateCategoryCommand;
use Maatify\Category\ImageRole\Lifecycle\Command\CreateCategoryImageRoleCommand;
use Maatify\Category\ImageAssignment\Assignment\Command\CreateCategoryImageAssignmentCommand;
use Maatify\Category\ImageAssignment\Default\Command\ClearCategoryImageAssignmentDefaultCommand;
use Maatify\Category\Lifecycle\Command\SoftDeleteCategoryCommand;
use Maatify\Category\ImageAssignment\Lifecycle\Command\SoftDeleteCategoryImageAssignmentCommand;
use Maatify\Category\ImageAssignment\Lifecycle\Command\RestoreCategoryImageAssignmentCommand;
use Maatify\Category\ImageAssignment\Default\Command\SetCategoryImageAssignmentDefaultCommand;
use Maatify\Category\ImageAssignment\Ordering\Command\UpdateCategoryImageAssignmentDisplayOrderCommand;
use Maatify\Category\ImageAssignment\Query\DTO\CategoryImageAssignmentListCriteriaDTO;
use Maatify\Category\Lifecycle\Command\UpdateCategoryStatusCommand;
use Maatify\Category\ImageAssignment\CategoryImageAssignmentScopeDTO;
use Maatify\Category\Query\DTO\CategoryVisibleListCriteriaDTO;
use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;
use Maatify\Category\Lifecycle\Enum\CategoryStatusEnum;
use Maatify\Category\ImageAssignment\Assignment\Exception\CategoryImageAssignmentAlreadyExistsException;
use Maatify\Category\ImageAssignment\Exception\CategoryImageAssignmentNotFoundException;
use Maatify\Category\Exception\CategoryNotFoundException;
use Maatify\Category\ImageAssignment\Query\Infrastructure\PdoCategoryImageAssignmentQueryReader;
use Maatify\Category\Tests\Integration\Support\CategoryMySqlIntegrationTestCase;
use Maatify\Category\Tests\Integration\Support\FixedCategoryClock;
use Maatify\Persistence\Pdo\Pagination\PageRequest;
use PDO;

final class CategoryImageAssignmentIntegrationTest extends CategoryMySqlIntegrationTestCase
{
    public function testDefaultIsExplicitPerExactScopeAndIndependentFromOrdering(): void
    {
        $connection = $this->connection();
        $service = $this->commandService($connection);
        $clock = new FixedCategoryClock();
        $mutationReader = $this->queryReader($connection, $clock);
        $imageService = $this->imageService($connection);
        $roleService = $this->roleService($connection);
        $categoryId = $service->create(new CreateCategoryCommand('image-default-category'));
        $roleId = $roleService->create(new CreateCategoryImageRoleCommand('default-gallery'));

        $neutralFirst = $imageService->create(
            new CreateCategoryImageAssignmentCommand($categoryId, 100),
        );
        $neutralSecond = $imageService->create(
            new CreateCategoryImageAssignmentCommand($categoryId, 101),
        );
        $languageOnly = $imageService->create(
            new CreateCategoryImageAssignmentCommand($categoryId, 102, 'en-US'),
        );
        $platformOnly = $imageService->create(
            new CreateCategoryImageAssignmentCommand($categoryId, 103, null, 'web'),
        );
        $roleScoped = $imageService->create(
            new CreateCategoryImageAssignmentCommand($categoryId, 104, 'en-US', 'web', $roleId),
        );

        self::assertSame(0, $this->defaultCount($connection));
        self::assertFalse($imageService->getByIdForManagement($neutralFirst)->isDefault);

        $imageService->setDefault(new SetCategoryImageAssignmentDefaultCommand($neutralFirst));
        self::assertSame($neutralFirst, $this->defaultIdForScope($connection, $categoryId, null, null, null));
        self::assertTrue($imageService->getByIdForManagement($neutralFirst)->isDefault);
        $hydratedAssignment = $mutationReader->findImageAssignmentById($neutralFirst);
        self::assertNotNull($hydratedAssignment);
        self::assertTrue($hydratedAssignment->isDefault);
        $visibleDefaultId = null;
        foreach ($imageService->listVisibleForCategory($categoryId, new CategoryImageAssignmentScopeDTO()) as $assignment) {
            if ($assignment->isDefault) {
                $visibleDefaultId = $assignment->id;
            }
        }
        self::assertSame($neutralFirst, $visibleDefaultId);

        $imageService->setDefault(new SetCategoryImageAssignmentDefaultCommand($neutralSecond));
        self::assertSame($neutralSecond, $this->defaultIdForScope($connection, $categoryId, null, null, null));
        self::assertFalse($imageService->getByIdForManagement($neutralFirst)->isDefault);
        self::assertTrue($imageService->getByIdForManagement($neutralSecond)->isDefault);

        $imageService->setDefault(new SetCategoryImageAssignmentDefaultCommand($languageOnly));
        $imageService->setDefault(new SetCategoryImageAssignmentDefaultCommand($platformOnly));
        $imageService->setDefault(new SetCategoryImageAssignmentDefaultCommand($roleScoped));
        self::assertSame(4, $this->defaultCount($connection));
        self::assertSame($languageOnly, $this->defaultIdForScope($connection, $categoryId, 'en-US', null, null));
        self::assertSame($platformOnly, $this->defaultIdForScope($connection, $categoryId, null, 'web', null));
        self::assertSame($roleScoped, $this->defaultIdForScope($connection, $categoryId, 'en-US', 'web', $roleId));

        $imageService->clearDefault(new ClearCategoryImageAssignmentDefaultCommand($neutralSecond));
        self::assertSame(3, $this->defaultCount($connection));
        self::assertNull($this->defaultIdForScope($connection, $categoryId, null, null, null));

        $imageService->setDefault(new SetCategoryImageAssignmentDefaultCommand($neutralFirst));
        $imageService->updateDisplayOrder(
            new UpdateCategoryImageAssignmentDisplayOrderCommand($neutralFirst, 2),
        );
        self::assertSame(
            [$neutralSecond, $neutralFirst],
            $this->ids($imageService->listVisibleForCategory($categoryId, new CategoryImageAssignmentScopeDTO())),
        );
        self::assertTrue($imageService->getByIdForManagement($neutralFirst)->isDefault);
        self::assertSame(4, $this->defaultCount($connection));
    }

    public function testSoftDeleteClearsDefaultWithoutPromotionAndRestoreRemainsNonDefault(): void
    {
        $connection = $this->connection();
        $service = $this->commandService($connection);
        $clock = new FixedCategoryClock();
        $imageService = $this->imageService($connection);
        $categoryId = $service->create(new CreateCategoryCommand('image-default-lifecycle-category'));
        $firstId = $imageService->create(new CreateCategoryImageAssignmentCommand($categoryId, 200));
        $secondId = $imageService->create(new CreateCategoryImageAssignmentCommand($categoryId, 201));

        $imageService->setDefault(new SetCategoryImageAssignmentDefaultCommand($firstId));
        $imageService->softDelete(new SoftDeleteCategoryImageAssignmentCommand($firstId));

        self::assertSame(0, $this->defaultCount($connection));
        self::assertFalse(
            $imageService->getByIdForManagement($firstId, CategoryDeletedStateEnum::DELETED_ONLY)->isDefault,
        );
        self::assertFalse($imageService->getByIdForManagement($secondId)->isDefault);
        self::assertSame(
            [$secondId],
            $this->ids($imageService->listVisibleForCategory($categoryId, new CategoryImageAssignmentScopeDTO())),
        );

        try {
            $imageService->setDefault(new SetCategoryImageAssignmentDefaultCommand($firstId));
            self::fail('A soft-deleted assignment must not be eligible for a default.');
        } catch (CategoryImageAssignmentNotFoundException) {
        }

        $imageService->restore(new RestoreCategoryImageAssignmentCommand($firstId));
        self::assertFalse($imageService->getByIdForManagement($firstId)->isDefault);
        self::assertSame(0, $this->defaultCount($connection));
    }

    public function testAllFourScopesHaveIndependentOrderingAndExactVisibleReads(): void
    {
        $connection = $this->connection();
        $service = $this->commandService($connection);
        $imageService = $this->imageService($connection);
        $categoryId = $service->create(new CreateCategoryCommand('image-scopes-category'));

        $neutralFirst = $imageService->create(
            new CreateCategoryImageAssignmentCommand($categoryId, 100),
        );
        $neutralSecond = $imageService->create(
            new CreateCategoryImageAssignmentCommand($categoryId, 101),
        );
        $languageOnly = $imageService->create(
            new CreateCategoryImageAssignmentCommand($categoryId, 100, 'en-US'),
        );
        $platformOnly = $imageService->create(
            new CreateCategoryImageAssignmentCommand($categoryId, 100, null, 'web'),
        );
        $languageAndPlatform = $imageService->create(
            new CreateCategoryImageAssignmentCommand($categoryId, 100, 'en-US', 'web'),
        );

        self::assertSame(
            [$neutralFirst, $neutralSecond],
            $this->ids($imageService->listVisibleForCategory($categoryId, new CategoryImageAssignmentScopeDTO())),
        );
        self::assertSame(
            [$languageOnly],
            $this->ids($imageService->listVisibleForCategory($categoryId, new CategoryImageAssignmentScopeDTO('en-US'))),
        );
        self::assertSame(
            [$platformOnly],
            $this->ids($imageService->listVisibleForCategory($categoryId, new CategoryImageAssignmentScopeDTO(null, 'web'))),
        );
        self::assertSame(
            [$languageAndPlatform],
            $this->ids($imageService->listVisibleForCategory($categoryId, new CategoryImageAssignmentScopeDTO('en-US', 'web'))),
        );

        $imageService->updateDisplayOrder(
            new UpdateCategoryImageAssignmentDisplayOrderCommand($neutralSecond, 1),
        );
        self::assertSame(
            [$neutralSecond, $neutralFirst],
            $this->ids($imageService->listVisibleForCategory($categoryId, new CategoryImageAssignmentScopeDTO())),
        );
    }

    public function testSoftDeleteRestoreAndStableIdentityAreIndependentFromParentVisibility(): void
    {
        $connection = $this->connection();
        $service = $this->commandService($connection);
        $clock = new FixedCategoryClock();
        $imageService = $this->imageService($connection);
        $categoryId = $service->create(new CreateCategoryCommand('image-lifecycle-category'));
        $assignmentId = $imageService->create(
            new CreateCategoryImageAssignmentCommand($categoryId, 300, 'en-US', 'web'),
        );

        $imageService->softDelete(new SoftDeleteCategoryImageAssignmentCommand($assignmentId));
        self::assertTrue(
            $imageService->listVisibleForCategory(
                $categoryId,
                new CategoryImageAssignmentScopeDTO('en-US', 'web'),
            )->isEmpty(),
        );
        self::assertNotNull(
            $imageService->getByIdForManagement($assignmentId, CategoryDeletedStateEnum::DELETED_ONLY)->deletedAt,
        );

        $this->expectException(CategoryImageAssignmentAlreadyExistsException::class);
        $imageService->create(
            new CreateCategoryImageAssignmentCommand($categoryId, 300, 'en-US', 'web'),
        );
    }

    public function testDeletedAssignmentCanBeRestoredAndInactiveParentDoesNotBlockCreate(): void
    {
        $connection = $this->connection();
        $service = $this->commandService($connection);
        $imageService = $this->imageService($connection);
        $categoryId = $service->create(new CreateCategoryCommand('image-parent-state-category'));
        $assignmentId = $imageService->create(
            new CreateCategoryImageAssignmentCommand($categoryId, 301),
        );

        $imageService->softDelete(new SoftDeleteCategoryImageAssignmentCommand($assignmentId));
        $imageService->restore(new RestoreCategoryImageAssignmentCommand($assignmentId));
        self::assertSame(
            [$assignmentId],
            $this->ids($imageService->listVisibleForCategory($categoryId, new CategoryImageAssignmentScopeDTO())),
        );

        $service->updateStatus(new UpdateCategoryStatusCommand($categoryId, CategoryStatusEnum::INACTIVE));
        $inactiveParentAssignment = $imageService->create(
            new CreateCategoryImageAssignmentCommand($categoryId, 302),
        );
        self::assertTrue(
            $imageService->listVisibleForCategory($categoryId, new CategoryImageAssignmentScopeDTO())->isEmpty(),
        );
        self::assertSame(
            [$assignmentId, $inactiveParentAssignment],
            $this->ids(
                $imageService->listForManagement(new CategoryImageAssignmentListCriteriaDTO(categoryId: $categoryId)),
            ),
        );

        $service->softDelete(new SoftDeleteCategoryCommand($categoryId));
        $imageService->updateDisplayOrder(
            new UpdateCategoryImageAssignmentDisplayOrderCommand($inactiveParentAssignment, 1),
        );
        $imageService->softDelete(new SoftDeleteCategoryImageAssignmentCommand($inactiveParentAssignment));
        $imageService->restore(new RestoreCategoryImageAssignmentCommand($inactiveParentAssignment));
        $this->expectException(CategoryNotFoundException::class);
        $imageService->create(new CreateCategoryImageAssignmentCommand($categoryId, 303));
    }

    public function testVisibleReadsUseExactScopeWithoutFallbackAndRespectAncestors(): void
    {
        $connection = $this->connection();
        $service = $this->commandService($connection);
        $imageService = $this->imageService($connection);
        $rootId = $service->create(new CreateCategoryCommand('image-visibility-root'));
        $childId = $service->create(new CreateCategoryCommand('image-visibility-child', $rootId));
        $assignmentId = $imageService->create(
            new CreateCategoryImageAssignmentCommand($childId, 400, 'en-US', 'web'),
        );

        self::assertSame(
            [$assignmentId],
            $this->ids($imageService->listVisibleForCategory($childId, new CategoryImageAssignmentScopeDTO('en-US', 'web'))),
        );
        self::assertTrue(
            $imageService->listVisibleForCategory($childId, new CategoryImageAssignmentScopeDTO('en-GB', 'web'))->isEmpty(),
        );
        self::assertTrue(
            $imageService->listVisibleForCategory($childId, new CategoryImageAssignmentScopeDTO())->isEmpty(),
        );

        $service->updateStatus(new UpdateCategoryStatusCommand($rootId, CategoryStatusEnum::INACTIVE));
        self::assertTrue(
            $imageService->listVisibleForCategory($childId, new CategoryImageAssignmentScopeDTO('en-US', 'web'))->isEmpty(),
        );
    }

    public function testManagementPaginationPreservesExactScopeOrderingByDefault(): void
    {
        $connection = $this->connection();
        $service = $this->commandService($connection);
        $imageService = $this->imageService($connection);
        $categoryId = $service->create(new CreateCategoryCommand('image-pagination-category'));
        $neutralId = $imageService->create(new CreateCategoryImageAssignmentCommand($categoryId, 600));
        $localizedId = $imageService->create(
            new CreateCategoryImageAssignmentCommand($categoryId, 601, 'en-US'),
        );
        $neutralSecondId = $imageService->create(new CreateCategoryImageAssignmentCommand($categoryId, 602));

        $page = $imageService->paginateForManagement(
            new CategoryImageAssignmentListCriteriaDTO(categoryId: $categoryId),
            new PageRequest(perPage: 3),
        );
        $ids = [];
        foreach ($page->data as $assignment) {
            $ids[] = $assignment->id;
        }

        self::assertSame('business_order', $page->sortBy);
        self::assertSame([$localizedId, $neutralId, $neutralSecondId], $ids);

        $categorySortedPage = $imageService->paginateForManagement(
            new CategoryImageAssignmentListCriteriaDTO(categoryId: $categoryId),
            new PageRequest(perPage: 3, sortBy: 'category_id'),
        );
        $categorySortedIds = [];
        foreach ($categorySortedPage->data as $assignment) {
            $categorySortedIds[] = $assignment->id;
        }

        self::assertSame('category_id', $categorySortedPage->sortBy);
        self::assertSame([$neutralId, $localizedId, $neutralSecondId], $categorySortedIds);
    }

    public function testManagementReadsDistinguishNoScopeFilterFromExactNullScope(): void
    {
        $connection = $this->connection();
        $service = $this->commandService($connection);
        $imageService = $this->imageService($connection);
        $categoryId = $service->create(new CreateCategoryCommand('image-management-category'));
        $neutral = $imageService->create(new CreateCategoryImageAssignmentCommand($categoryId, 500));
        $neutralSecond = $imageService->create(new CreateCategoryImageAssignmentCommand($categoryId, 501));
        $imageService->create(new CreateCategoryImageAssignmentCommand($categoryId, 502, 'en-US'));
        $imageService->create(new CreateCategoryImageAssignmentCommand($categoryId, 503, null, 'web'));

        self::assertSame(
            4,
            $imageService->listForManagement(
                new CategoryImageAssignmentListCriteriaDTO(categoryId: $categoryId),
            )->count(),
        );
        self::assertSame(
            [$neutral, $neutralSecond],
            $this->ids($imageService->listForManagement(
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

    private function commandService(PDO $connection): CategoryApiInterface
    {
        return CategoryFactory::create(
            $connection,
            new FixedCategoryClock('2026-01-01 00:00:00 Africa/Cairo'),
        )->categories();
    }

    private function imageService(PDO $connection): ImageAssignmentApiInterface
    {
        return CategoryFactory::create($connection, new FixedCategoryClock())->images();
    }

    private function roleService(PDO $connection): ImageRoleApiInterface
    {
        return CategoryFactory::create($connection, new FixedCategoryClock())->imageRoles();
    }

    private function queryReader(PDO $connection, FixedCategoryClock $clock): PdoCategoryImageAssignmentQueryReader
    {
        return new PdoCategoryImageAssignmentQueryReader($connection, $clock);
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
