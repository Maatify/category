<?php

declare(strict_types=1);

namespace Maatify\Category\Tests\Integration;

use Maatify\Category\Command\CreateCategoryCommand;
use Maatify\Category\Command\CreateCategoryImageAssignmentCommand;
use Maatify\Category\Command\CreateCategoryImageRoleCommand;
use Maatify\Category\Command\RestoreCategoryImageRoleCommand;
use Maatify\Category\Command\SoftDeleteCategoryImageRoleCommand;
use Maatify\Category\Command\UpdateCategoryImageRoleStatusCommand;
use Maatify\Category\DTO\CategoryImageAssignmentListCriteriaDTO;
use Maatify\Category\DTO\CategoryImageAssignmentScopeDTO;
use Maatify\Category\DTO\CategoryImageRoleListCriteriaDTO;
use Maatify\Category\Enum\CategoryDeletedStateEnum;
use Maatify\Category\Enum\CategoryImageRoleStatusEnum;
use Maatify\Category\Exception\CategoryImageAssignmentAlreadyExistsException;
use Maatify\Category\Exception\CategoryImageRoleAlreadyExistsException;
use Maatify\Category\Exception\CategoryImageRoleNotFoundException;
use Maatify\Category\Exception\CategoryImageRoleUnavailableException;
use Maatify\Category\Infrastructure\Repository\PdoCategoryCommandRepository;
use Maatify\Category\Infrastructure\Repository\PdoCategoryContentCommandRepository;
use Maatify\Category\Infrastructure\Repository\PdoCategoryContentFieldCommandRepository;
use Maatify\Category\Infrastructure\Repository\PdoCategoryImageAssignmentCommandRepository;
use Maatify\Category\Infrastructure\Repository\PdoCategoryImageRoleCommandRepository;
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

final class CategoryImageRoleIntegrationTest extends CategoryMySqlIntegrationTestCase
{
    public function testRoleLifecycleIdentityAndManagementFilters(): void
    {
        $connection = $this->connection();
        $service = $this->commandService($connection);
        $management = new CategoryManagementQueryService(new PdoCategoryManagementReadQuery($connection));

        $galleryId = $service->createImageRole(new CreateCategoryImageRoleCommand('gallery'));
        $heroId = $service->createImageRole(
            new CreateCategoryImageRoleCommand('hero', CategoryImageRoleStatusEnum::INACTIVE),
        );

        self::assertSame('gallery', $management->getImageRoleById($galleryId)->roleKey);
        self::assertSame($galleryId, $management->getImageRoleByKey('gallery')->id);
        self::assertSame(
            [$galleryId, $heroId],
            $this->roleIds($management->listImageRoles(new CategoryImageRoleListCriteriaDTO())),
        );
        self::assertSame(
            [$galleryId],
            $this->roleIds($management->listImageRoles(new CategoryImageRoleListCriteriaDTO(
                status: CategoryImageRoleStatusEnum::ACTIVE,
            ))),
        );

        try {
            $service->createImageRole(new CreateCategoryImageRoleCommand('gallery'));
            self::fail('Role keys must be unique.');
        } catch (CategoryImageRoleAlreadyExistsException $exception) {
            self::assertStringContainsString('gallery', $exception->getMessage());
        }

        $service->updateImageRoleStatus(
            new UpdateCategoryImageRoleStatusCommand($galleryId, CategoryImageRoleStatusEnum::INACTIVE),
        );
        self::assertSame(
            CategoryImageRoleStatusEnum::INACTIVE,
            $management->getImageRoleById($galleryId)->status,
        );

        $service->softDeleteImageRole(new SoftDeleteCategoryImageRoleCommand($galleryId));
        try {
            $management->getImageRoleById($galleryId);
            self::fail('Soft-deleted Roles must be hidden from the default management read.');
        } catch (CategoryImageRoleNotFoundException $exception) {
            self::assertStringContainsString((string) $galleryId, $exception->getMessage());
        }
        self::assertSame(
            $galleryId,
            $management->getImageRoleByKey('gallery', CategoryDeletedStateEnum::DELETED_ONLY)->id,
        );
        self::assertSame(
            [$galleryId],
            $this->roleIds($management->listImageRoles(new CategoryImageRoleListCriteriaDTO(
                deletedState: CategoryDeletedStateEnum::DELETED_ONLY,
            ))),
        );

        try {
            $service->createImageRole(new CreateCategoryImageRoleCommand('gallery'));
            self::fail('Soft-deleted Role keys must remain reserved.');
        } catch (CategoryImageRoleAlreadyExistsException $exception) {
            self::assertStringContainsString('gallery', $exception->getMessage());
        }

        $service->restoreImageRole(new RestoreCategoryImageRoleCommand($galleryId));
        self::assertSame($galleryId, $management->getImageRoleById($galleryId)->id);
        self::assertSame(
            CategoryImageRoleStatusEnum::INACTIVE,
            $management->getImageRoleById($galleryId)->status,
        );
        $service->updateImageRoleStatus(
            new UpdateCategoryImageRoleStatusCommand($galleryId, CategoryImageRoleStatusEnum::ACTIVE),
        );
        self::assertSame($galleryId, $management->getImageRoleByKey('gallery')->id);
    }

    public function testRoleAssignmentUsesExactNullableScopeAndRoleVisibility(): void
    {
        $connection = $this->connection();
        $service = $this->commandService($connection);
        $consumer = new CategoryQueryService(new PdoCategoryReadQuery($connection));
        $management = new CategoryManagementQueryService(new PdoCategoryManagementReadQuery($connection));
        $categoryId = $service->create(new CreateCategoryCommand('role-assignment-category'));
        $galleryId = $service->createImageRole(new CreateCategoryImageRoleCommand('gallery'));
        $heroId = $service->createImageRole(new CreateCategoryImageRoleCommand('hero'));

        $genericId = $service->createImageAssignment(
            new CreateCategoryImageAssignmentCommand($categoryId, 1000, 'ar', 'ios'),
        );
        $galleryFirstId = $service->createImageAssignment(
            new CreateCategoryImageAssignmentCommand($categoryId, 1000, 'ar', 'ios', $galleryId),
        );
        $gallerySecondId = $service->createImageAssignment(
            new CreateCategoryImageAssignmentCommand($categoryId, 1001, 'ar', 'ios', $galleryId),
        );
        $heroIdAssignment = $service->createImageAssignment(
            new CreateCategoryImageAssignmentCommand($categoryId, 1000, 'ar', 'ios', $heroId),
        );

        self::assertSame(
            [$genericId],
            $this->assignmentIds($consumer->listImageAssignments(
                $categoryId,
                new CategoryImageAssignmentScopeDTO('ar', 'ios'),
            )),
        );
        self::assertSame(
            [$galleryFirstId, $gallerySecondId],
            $this->assignmentIds($consumer->listImageAssignments(
                $categoryId,
                new CategoryImageAssignmentScopeDTO('ar', 'ios', $galleryId),
            )),
        );
        self::assertSame(
            [$heroIdAssignment],
            $this->assignmentIds($consumer->listImageAssignments(
                $categoryId,
                new CategoryImageAssignmentScopeDTO('ar', 'ios', $heroId),
            )),
        );
        self::assertSame(
            [$genericId, $galleryFirstId, $gallerySecondId, $heroIdAssignment],
            $this->assignmentIds($management->listImageAssignments(
                new CategoryImageAssignmentListCriteriaDTO(categoryId: $categoryId),
            )),
        );
        self::assertSame(
            [$genericId],
            $this->assignmentIds($management->listImageAssignments(
                new CategoryImageAssignmentListCriteriaDTO(
                    categoryId: $categoryId,
                    scope: new CategoryImageAssignmentScopeDTO('ar', 'ios', null),
                ),
            )),
        );

        try {
            $service->createImageAssignment(
                new CreateCategoryImageAssignmentCommand($categoryId, 1000, 'ar', 'ios', $galleryId),
            );
            self::fail('The exact Category/Image/Role/scope identity must be unique.');
        } catch (CategoryImageAssignmentAlreadyExistsException $exception) {
            self::assertStringContainsString((string) $galleryId, $exception->getMessage());
        }

        $service->updateImageRoleStatus(
            new UpdateCategoryImageRoleStatusCommand($galleryId, CategoryImageRoleStatusEnum::INACTIVE),
        );
        self::assertTrue($consumer->listImageAssignments(
            $categoryId,
            new CategoryImageAssignmentScopeDTO('ar', 'ios', $galleryId),
        )->isEmpty());
        self::assertSame(
            [$galleryFirstId, $gallerySecondId],
            $this->assignmentIds($management->listImageAssignments(new CategoryImageAssignmentListCriteriaDTO(
                categoryId: $categoryId,
                scope: new CategoryImageAssignmentScopeDTO('ar', 'ios', $galleryId),
            ))),
        );

        $service->updateImageRoleStatus(
            new UpdateCategoryImageRoleStatusCommand($galleryId, CategoryImageRoleStatusEnum::ACTIVE),
        );
        self::assertCount(2, $consumer->listImageAssignments(
            $categoryId,
            new CategoryImageAssignmentScopeDTO('ar', 'ios', $galleryId),
        ));
        $service->softDeleteImageRole(new SoftDeleteCategoryImageRoleCommand($galleryId));
        self::assertTrue($consumer->listImageAssignments(
            $categoryId,
            new CategoryImageAssignmentScopeDTO('ar', 'ios', $galleryId),
        )->isEmpty());

        $service->restoreImageRole(new RestoreCategoryImageRoleCommand($galleryId));
        self::assertCount(2, $consumer->listImageAssignments(
            $categoryId,
            new CategoryImageAssignmentScopeDTO('ar', 'ios', $galleryId),
        ));
    }

    public function testUnavailableAndMissingRolesAreRejectedForNewAssignments(): void
    {
        $connection = $this->connection();
        $service = $this->commandService($connection);
        $categoryId = $service->create(new CreateCategoryCommand('role-availability-category'));
        $inactiveRoleId = $service->createImageRole(new CreateCategoryImageRoleCommand(
            'inactive',
            CategoryImageRoleStatusEnum::INACTIVE,
        ));

        try {
            $service->createImageAssignment(
                new CreateCategoryImageAssignmentCommand($categoryId, 2000, null, null, $inactiveRoleId),
            );
            self::fail('Inactive Roles must reject new assignments.');
        } catch (CategoryImageRoleUnavailableException $exception) {
            self::assertStringContainsString('cannot accept new assignments', $exception->getMessage());
        }

        try {
            $service->createImageAssignment(
                new CreateCategoryImageAssignmentCommand($categoryId, 2001, null, null, 999999),
            );
            self::fail('Missing Roles must reject new assignments.');
        } catch (CategoryImageRoleNotFoundException $exception) {
            self::assertStringContainsString('999999', $exception->getMessage());
        }

        $service->updateImageRoleStatus(
            new UpdateCategoryImageRoleStatusCommand($inactiveRoleId, CategoryImageRoleStatusEnum::ACTIVE),
        );
        $service->softDeleteImageRole(new SoftDeleteCategoryImageRoleCommand($inactiveRoleId));
        try {
            $service->createImageAssignment(
                new CreateCategoryImageAssignmentCommand($categoryId, 2002, null, null, $inactiveRoleId),
            );
            self::fail('Soft-deleted Roles must reject new assignments.');
        } catch (CategoryImageRoleUnavailableException $exception) {
            self::assertStringContainsString('cannot accept new assignments', $exception->getMessage());
        }
    }

    /** @return list<int> */
    private function roleIds(\Maatify\Category\DTO\CategoryImageRoleCollectionDTO $roles): array
    {
        $ids = [];
        foreach ($roles as $role) {
            $ids[] = $role->id;
        }

        return $ids;
    }

    /** @return list<int> */
    private function assignmentIds(\Maatify\Category\DTO\CategoryImageAssignmentCollectionDTO $assignments): array
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
            new PdoCategoryTransaction($connection),
            new FixedCategoryClock('2026-01-01 00:00:00 UTC'),
            new PdoCategoryImageRoleCommandRepository($connection),
        );
    }
}
