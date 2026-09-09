<?php

declare(strict_types=1);

namespace Maatify\Category\Service;

use Maatify\Category\Contract\CategoryCommandRepositoryInterface;
use Maatify\Category\Contract\CategoryCommandServiceInterface;
use Maatify\Category\Contract\CategoryQueryReaderInterface;
use Maatify\Category\Contract\CategoryTransactionInterface;
use Maatify\Category\Contract\CategoryContentCommandRepositoryInterface;
use Maatify\Category\Contract\CategoryImageAssignmentCommandRepositoryInterface;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CategoryContentDTO;
use Maatify\Category\DTO\CategoryImageAssignmentDTO;
use Maatify\Category\Command\CreateCategoryCommand;
use Maatify\Category\Command\CreateCategoryContentCommand;
use Maatify\Category\Command\CreateCategoryImageAssignmentCommand;
use Maatify\Category\Command\MoveCategoryCommand;
use Maatify\Category\Command\RestoreCategoryCommand;
use Maatify\Category\Command\RestoreCategoryContentCommand;
use Maatify\Category\Command\RestoreCategoryImageAssignmentCommand;
use Maatify\Category\Command\SoftDeleteCategoryCommand;
use Maatify\Category\Command\SoftDeleteCategoryContentCommand;
use Maatify\Category\Command\SoftDeleteCategoryImageAssignmentCommand;
use Maatify\Category\Command\UpdateCategoryDisplayOrderCommand;
use Maatify\Category\Command\UpdateCategoryStatusCommand;
use Maatify\Category\Command\UpdateCategoryContentCommand;
use Maatify\Category\Command\UpdateCategoryImageAssignmentDisplayOrderCommand;
use Maatify\Category\Exception\CategoryCodeAlreadyExistsException;
use Maatify\Category\Exception\CategoryCycleException;
use Maatify\Category\Exception\CategoryHasNonDeletedChildrenException;
use Maatify\Category\Exception\CategoryNotFoundException;
use Maatify\Category\Exception\CategoryContentNotFoundException;
use Maatify\Category\Exception\CategoryImageAssignmentNotFoundException;
use Maatify\SharedCommon\Contracts\ClockInterface;

/** Coordinates Category business rules and owns application mutation time. */
final readonly class CategoryCommandService implements CategoryCommandServiceInterface
{
    public function __construct(
        private CategoryCommandRepositoryInterface $commandRepository,
        private CategoryQueryReaderInterface $queryReader,
        private CategoryContentCommandRepositoryInterface $contentCommandRepository,
        private CategoryImageAssignmentCommandRepositoryInterface $imageAssignmentCommandRepository,
        private CategoryTransactionInterface $transaction,
        private ClockInterface $clock,
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

            return $this->imageAssignmentCommandRepository->create($command, $this->clock->now());
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
        $this->requireActiveCategory($command->categoryId);

        if (!$this->commandRepository->updateDisplayOrder($command, $this->clock->now())) {
            throw CategoryNotFoundException::withId($command->categoryId);
        }
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
        if (!$this->imageAssignmentCommandRepository->updateDisplayOrder($command, $this->clock->now())) {
            throw CategoryImageAssignmentNotFoundException::withId($command->assignmentId);
        }
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

    private function requireActiveCategory(int $categoryId): CategoryDTO
    {
        $category = $this->queryReader->findActiveById($categoryId);

        if ($category === null) {
            throw CategoryNotFoundException::withId($categoryId);
        }

        return $category;
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
