<?php

declare(strict_types=1);

namespace Maatify\Category\Service;

use Maatify\Category\Contract\CategoryCommandRepositoryInterface;
use Maatify\Category\Contract\CategoryCommandServiceInterface;
use Maatify\Category\Contract\CategoryQueryReaderInterface;
use Maatify\Category\Contract\CategoryContentCommandRepositoryInterface;
use Maatify\Category\Contract\CategoryImageAssignmentCommandRepositoryInterface;
use Maatify\Category\Contract\CategoryImageRoleCommandRepositoryInterface;
use Maatify\Category\Contract\CategoryContentFieldCommandRepositoryInterface;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CategoryContentDTO;
use Maatify\Category\DTO\CategoryImageAssignmentDTO;
use Maatify\Category\DTO\CategoryImageRoleDTO;
use Maatify\Category\DTO\CategoryContentFieldDTO;
use Maatify\Category\Command\ClearCategoryImageAssignmentDefaultCommand;
use Maatify\Category\Command\CreateCategoryCommand;
use Maatify\Category\Command\CreateCategoryContentCommand;
use Maatify\Category\Command\CreateCategoryImageAssignmentCommand;
use Maatify\Category\Command\CreateCategoryImageRoleCommand;
use Maatify\Category\Command\CreateCategoryContentFieldCommand;
use Maatify\Category\Command\MoveCategoryCommand;
use Maatify\Category\Command\RestoreCategoryCommand;
use Maatify\Category\Command\RestoreCategoryContentCommand;
use Maatify\Category\Command\RestoreCategoryImageAssignmentCommand;
use Maatify\Category\Command\RestoreCategoryImageRoleCommand;
use Maatify\Category\Command\SoftDeleteCategoryCommand;
use Maatify\Category\Command\SoftDeleteCategoryContentCommand;
use Maatify\Category\Command\SoftDeleteCategoryImageAssignmentCommand;
use Maatify\Category\Command\SetCategoryImageAssignmentDefaultCommand;
use Maatify\Category\Command\SoftDeleteCategoryImageRoleCommand;
use Maatify\Category\Command\UpdateCategoryDisplayOrderCommand;
use Maatify\Category\Command\UpdateCategoryStatusCommand;
use Maatify\Category\Command\UpdateCategoryContentCommand;
use Maatify\Category\Command\UpdateCategoryImageAssignmentDisplayOrderCommand;
use Maatify\Category\Command\UpdateCategoryImageRoleStatusCommand;
use Maatify\Category\Command\UpdateCategoryContentFieldCommand;
use Maatify\Category\Command\UpdateCategoryContentFieldDisplayOrderCommand;
use Maatify\Category\Command\SoftDeleteCategoryContentFieldCommand;
use Maatify\Category\Command\RestoreCategoryContentFieldCommand;
use Maatify\Category\Exception\CategoryCodeAlreadyExistsException;
use Maatify\Category\Exception\CategoryCycleException;
use Maatify\Category\Exception\CategoryHasNonDeletedChildrenException;
use Maatify\Category\Exception\CategoryNotFoundException;
use Maatify\Category\Exception\CategoryContentNotFoundException;
use Maatify\Category\Exception\CategoryImageAssignmentNotFoundException;
use Maatify\Category\Exception\CategoryImageRoleNotFoundException;
use Maatify\Category\Exception\CategoryImageRoleUnavailableException;
use Maatify\Category\Exception\CategoryPersistenceException;
use Maatify\Category\Exception\CategoryContentFieldNotFoundException;
use Maatify\Category\Enum\CategoryImageRoleStatusEnum;
use Maatify\SharedCommon\Contracts\ClockInterface;
use Maatify\Persistence\Pdo\Transaction\TransactionRunnerInterface;

/** Coordinates Category business rules and consumes Host-provided mutation time. */
final readonly class CategoryCommandService implements CategoryCommandServiceInterface
{
    public function __construct(
        private CategoryCommandRepositoryInterface $commandRepository,
        private CategoryQueryReaderInterface $queryReader,
        private CategoryContentCommandRepositoryInterface $contentCommandRepository,
        private CategoryImageAssignmentCommandRepositoryInterface $imageAssignmentCommandRepository,
        private CategoryContentFieldCommandRepositoryInterface $contentFieldCommandRepository,
        private TransactionRunnerInterface $transaction,
        private ClockInterface $clock,
        private ?CategoryImageRoleCommandRepositoryInterface $imageRoleCommandRepository = null,
    ) {}

    public function create(CreateCategoryCommand $command): int
    {
        return $this->transaction->run(function () use ($command): int {
            if ($this->queryReader->findByCode($command->code) !== null) {
                throw CategoryCodeAlreadyExistsException::withCode($command->code);
            }

            if ($command->parentId !== null) {
                // Serialize parent validation with soft-delete and other
                // hierarchy mutations before inserting the child.
                $this->requireActiveCategoryForUpdate($command->parentId);
            }

            return $this->commandRepository->create($command, $this->clock->now());
        });
    }

    public function createContent(CreateCategoryContentCommand $command): int
    {
        return $this->transaction->run(function () use ($command): int {
            $this->requireActiveCategoryForUpdate($command->categoryId);

            return $this->contentCommandRepository->create($command, $this->clock->now());
        });
    }

    public function createImageAssignment(CreateCategoryImageAssignmentCommand $command): int
    {
        return $this->transaction->run(function () use ($command): int {
            $this->requireActiveCategoryForUpdate($command->categoryId);
            $this->requireActiveImageRoleForUpdate($command->roleId);

            return $this->imageAssignmentCommandRepository->create($command, $this->clock->now());
        });
    }

    public function setImageAssignmentDefault(SetCategoryImageAssignmentDefaultCommand $command): void
    {
        $this->transaction->run(function () use ($command): void {
            if (!$this->imageAssignmentCommandRepository->setDefault($command, $this->clock->now())) {
                throw CategoryImageAssignmentNotFoundException::withId($command->assignmentId);
            }
        });
    }

    public function clearImageAssignmentDefault(ClearCategoryImageAssignmentDefaultCommand $command): void
    {
        $this->transaction->run(function () use ($command): void {
            if (!$this->imageAssignmentCommandRepository->clearDefault($command, $this->clock->now())) {
                throw CategoryImageAssignmentNotFoundException::withId($command->assignmentId);
            }
        });
    }

    public function createImageRole(CreateCategoryImageRoleCommand $command): int
    {
        return $this->transaction->run(function () use ($command): int {
            return $this->requireImageRoleCommandRepository()->create($command, $this->clock->now());
        });
    }

    public function updateImageRoleStatus(UpdateCategoryImageRoleStatusCommand $command): void
    {
        $this->transaction->run(function () use ($command): void {
            $this->requireActiveImageRoleForStatusUpdate($command->roleId);

            if (!$this->requireImageRoleCommandRepository()->updateStatus($command, $this->clock->now())) {
                throw CategoryImageRoleNotFoundException::withId($command->roleId);
            }
        });
    }

    public function softDeleteImageRole(SoftDeleteCategoryImageRoleCommand $command): void
    {
        $this->transaction->run(function () use ($command): void {
            $role = $this->requireImageRoleForUpdate($command->roleId);
            if ($role->deletedAt !== null) {
                throw CategoryImageRoleNotFoundException::withId($command->roleId);
            }

            if (!$this->requireImageRoleCommandRepository()->softDelete($command, $this->clock->now())) {
                throw CategoryImageRoleNotFoundException::withId($command->roleId);
            }
        });
    }

    public function restoreImageRole(RestoreCategoryImageRoleCommand $command): void
    {
        $this->transaction->run(function () use ($command): void {
            $this->requireImageRoleForUpdate($command->roleId);

            if (!$this->requireImageRoleCommandRepository()->restore($command, $this->clock->now())) {
                throw CategoryImageRoleNotFoundException::withId($command->roleId);
            }
        });
    }

    public function createContentField(CreateCategoryContentFieldCommand $command): int
    {
        return $this->transaction->run(function () use ($command): int {
            $this->requireActiveCategoryForUpdate($command->categoryId);

            return $this->contentFieldCommandRepository->create($command, $this->clock->now());
        });
    }

    public function move(MoveCategoryCommand $command): void
    {
        $this->transaction->run(function () use ($command): void {
            $category = $this->requireActiveCategoryForUpdate($command->categoryId);

            if ($command->parentId !== null) {
                $this->assertMoveDoesNotCreateCycle($category->id, $command->parentId);
            }

            if (!$this->commandRepository->move($command, $this->clock->now())) {
                throw CategoryNotFoundException::withId($command->categoryId);
            }
        });
    }

    public function softDelete(SoftDeleteCategoryCommand $command): void
    {
        $this->transaction->run(function () use ($command): void {
            $this->requireActiveCategoryForUpdate($command->categoryId);

            if ($this->queryReader->hasNonDeletedChildrenForUpdate($command->categoryId)) {
                throw CategoryHasNonDeletedChildrenException::withId($command->categoryId);
            }

            if (!$this->commandRepository->softDelete($command, $this->clock->now())) {
                throw CategoryNotFoundException::withId($command->categoryId);
            }
        });
    }

    public function restore(RestoreCategoryCommand $command): void
    {
        $this->transaction->run(function () use ($command): void {
            $this->requireCategoryForUpdate($command->categoryId);

            if (!$this->commandRepository->restore($command, $this->clock->now())) {
                throw CategoryNotFoundException::withId($command->categoryId);
            }
        });
    }

    public function updateStatus(UpdateCategoryStatusCommand $command): void
    {
        $this->transaction->run(function () use ($command): void {
            $this->requireActiveCategoryForUpdate($command->categoryId);

            if (!$this->commandRepository->updateStatus($command, $this->clock->now())) {
                throw CategoryNotFoundException::withId($command->categoryId);
            }
        });
    }

    public function updateDisplayOrder(UpdateCategoryDisplayOrderCommand $command): void
    {
        $this->transaction->run(function () use ($command): void {
            $this->requireActiveCategoryForUpdate($command->categoryId);

            if (!$this->commandRepository->updateDisplayOrder($command, $this->clock->now())) {
                throw CategoryNotFoundException::withId($command->categoryId);
            }
        });
    }

    public function updateContent(UpdateCategoryContentCommand $command): void
    {
        $this->transaction->run(function () use ($command): void {
            $this->requireActiveContentForUpdate($command->contentId);

            if (!$this->contentCommandRepository->update($command, $this->clock->now())) {
                throw CategoryContentNotFoundException::withId($command->contentId);
            }
        });
    }

    public function updateImageAssignmentDisplayOrder(
        UpdateCategoryImageAssignmentDisplayOrderCommand $command,
    ): void {
        $this->transaction->run(function () use ($command): void {
            $this->requireActiveImageAssignmentForUpdate($command->assignmentId);

            if (!$this->imageAssignmentCommandRepository->updateDisplayOrder($command, $this->clock->now())) {
                throw CategoryImageAssignmentNotFoundException::withId($command->assignmentId);
            }
        });
    }

    public function updateContentField(UpdateCategoryContentFieldCommand $command): void
    {
        $this->transaction->run(function () use ($command): void {
            $this->requireActiveContentFieldForUpdate($command->fieldId);

            if (!$this->contentFieldCommandRepository->update($command, $this->clock->now())) {
                throw CategoryContentFieldNotFoundException::withId($command->fieldId);
            }
        });
    }

    public function updateContentFieldDisplayOrder(
        UpdateCategoryContentFieldDisplayOrderCommand $command,
    ): void {
        $this->transaction->run(function () use ($command): void {
            $this->requireActiveContentFieldForUpdate($command->fieldId);

            if (!$this->contentFieldCommandRepository->updateDisplayOrder($command, $this->clock->now())) {
                throw CategoryContentFieldNotFoundException::withId($command->fieldId);
            }
        });
    }

    public function softDeleteContent(SoftDeleteCategoryContentCommand $command): void
    {
        $this->transaction->run(function () use ($command): void {
            $this->requireActiveContentForUpdate($command->contentId);

            if (!$this->contentCommandRepository->softDelete($command, $this->clock->now())) {
                throw CategoryContentNotFoundException::withId($command->contentId);
            }
        });
    }

    public function softDeleteImageAssignment(SoftDeleteCategoryImageAssignmentCommand $command): void
    {
        $this->transaction->run(function () use ($command): void {
            $this->requireActiveImageAssignmentForUpdate($command->assignmentId);

            if (!$this->imageAssignmentCommandRepository->softDelete($command, $this->clock->now())) {
                throw CategoryImageAssignmentNotFoundException::withId($command->assignmentId);
            }
        });
    }

    public function restoreContent(RestoreCategoryContentCommand $command): void
    {
        $this->transaction->run(function () use ($command): void {
            $this->requireContentForUpdate($command->contentId);

            if (!$this->contentCommandRepository->restore($command, $this->clock->now())) {
                throw CategoryContentNotFoundException::withId($command->contentId);
            }
        });
    }

    public function restoreImageAssignment(RestoreCategoryImageAssignmentCommand $command): void
    {
        $this->transaction->run(function () use ($command): void {
            $this->requireImageAssignmentForUpdate($command->assignmentId);

            if (!$this->imageAssignmentCommandRepository->restore($command, $this->clock->now())) {
                throw CategoryImageAssignmentNotFoundException::withId($command->assignmentId);
            }
        });
    }

    public function softDeleteContentField(SoftDeleteCategoryContentFieldCommand $command): void
    {
        $this->transaction->run(function () use ($command): void {
            $this->requireActiveContentFieldForUpdate($command->fieldId);

            if (!$this->contentFieldCommandRepository->softDelete($command, $this->clock->now())) {
                throw CategoryContentFieldNotFoundException::withId($command->fieldId);
            }
        });
    }

    public function restoreContentField(RestoreCategoryContentFieldCommand $command): void
    {
        $this->transaction->run(function () use ($command): void {
            $this->requireContentFieldForUpdate($command->fieldId);

            if (!$this->contentFieldCommandRepository->restore($command, $this->clock->now())) {
                throw CategoryContentFieldNotFoundException::withId($command->fieldId);
            }
        });
    }

    private function requireActiveCategoryForUpdate(int $categoryId): CategoryDTO
    {
        $category = $this->queryReader->findActiveByIdForUpdate($categoryId);

        if ($category === null) {
            throw CategoryNotFoundException::withId($categoryId);
        }

        return $category;
    }

    private function requireCategoryForUpdate(int $categoryId): CategoryDTO
    {
        $category = $this->queryReader->findByIdForUpdate($categoryId);

        if ($category === null) {
            throw CategoryNotFoundException::withId($categoryId);
        }

        return $category;
    }

    private function requireActiveContentForUpdate(int $contentId): CategoryContentDTO
    {
        $content = $this->requireContentForUpdate($contentId);

        if ($content->deletedAt !== null) {
            throw CategoryContentNotFoundException::withId($contentId);
        }

        return $content;
    }

    private function requireContentForUpdate(int $contentId): CategoryContentDTO
    {
        $content = $this->queryReader->findContentByIdForUpdate($contentId);

        if ($content === null) {
            throw CategoryContentNotFoundException::withId($contentId);
        }

        return $content;
    }

    private function requireActiveImageAssignmentForUpdate(int $assignmentId): CategoryImageAssignmentDTO
    {
        $assignment = $this->requireImageAssignmentForUpdate($assignmentId);

        if ($assignment->deletedAt !== null) {
            throw CategoryImageAssignmentNotFoundException::withId($assignmentId);
        }

        return $assignment;
    }

    private function requireImageAssignmentForUpdate(int $assignmentId): CategoryImageAssignmentDTO
    {
        $assignment = $this->queryReader->findImageAssignmentByIdForUpdate($assignmentId);

        if ($assignment === null) {
            throw CategoryImageAssignmentNotFoundException::withId($assignmentId);
        }

        return $assignment;
    }

    private function requireActiveImageRoleForUpdate(?int $roleId): void
    {
        if ($roleId === null) {
            return;
        }

        $role = $this->requireImageRoleForUpdate($roleId);
        if ($role->deletedAt !== null || $role->status !== CategoryImageRoleStatusEnum::ACTIVE) {
            throw CategoryImageRoleUnavailableException::withId($roleId);
        }
    }

    private function requireActiveImageRoleForStatusUpdate(int $roleId): void
    {
        $role = $this->requireImageRoleForUpdate($roleId);
        if ($role->deletedAt !== null) {
            throw CategoryImageRoleNotFoundException::withId($roleId);
        }
    }

    private function requireImageRoleForUpdate(int $roleId): CategoryImageRoleDTO
    {
        $role = $this->queryReader->findImageRoleByIdForUpdate($roleId);

        if ($role === null) {
            throw CategoryImageRoleNotFoundException::withId($roleId);
        }

        return $role;
    }

    private function requireImageRoleCommandRepository(): CategoryImageRoleCommandRepositoryInterface
    {
        if ($this->imageRoleCommandRepository === null) {
            throw CategoryPersistenceException::imageRoleRepositoryNotConfigured();
        }

        return $this->imageRoleCommandRepository;
    }

    private function requireActiveContentFieldForUpdate(int $fieldId): CategoryContentFieldDTO
    {
        $field = $this->requireContentFieldForUpdate($fieldId);

        if ($field->deletedAt !== null) {
            throw CategoryContentFieldNotFoundException::withId($fieldId);
        }

        return $field;
    }

    private function requireContentFieldForUpdate(int $fieldId): CategoryContentFieldDTO
    {
        $field = $this->queryReader->findContentFieldByIdForUpdate($fieldId);

        if ($field === null) {
            throw CategoryContentFieldNotFoundException::withId($fieldId);
        }

        return $field;
    }

    private function assertMoveDoesNotCreateCycle(int $categoryId, int $newParentId): void
    {
        /** @var array<int, true> $visited */
        $visited = [$categoryId => true];
        $currentId = $newParentId;

        while (true) {
            if (isset($visited[$currentId])) {
                throw CategoryCycleException::forMove($categoryId, $newParentId);
            }

            $visited[$currentId] = true;
            $parent = $this->requireActiveCategoryForUpdate($currentId);
            if ($parent->parentId === null) {
                return;
            }

            $currentId = $parent->parentId;
        }
    }
}
