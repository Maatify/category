<?php

declare(strict_types=1);

namespace Maatify\Category\Service;

use Maatify\Category\Contract\CategoryCommandRepositoryInterface;
use Maatify\Category\Contract\CategoryCommandServiceInterface;
use Maatify\Category\Contract\CategoryQueryReaderInterface;
use Maatify\Category\Contract\CategoryTransactionInterface;
use Maatify\Category\Contract\CategoryTranslationCommandRepositoryInterface;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CreateCategoryDTO;
use Maatify\Category\DTO\MoveCategoryDTO;
use Maatify\Category\DTO\RestoreCategoryDTO;
use Maatify\Category\DTO\SoftDeleteCategoryDTO;
use Maatify\Category\DTO\UpdateCategoryDisplayOrderDTO;
use Maatify\Category\DTO\UpdateCategoryStatusDTO;
use Maatify\Category\DTO\UpdateCategoryTranslationDTO;
use Maatify\Category\Exception\CategoryCodeAlreadyExistsException;
use Maatify\Category\Exception\CategoryCycleException;
use Maatify\Category\Exception\CategoryHasNonDeletedChildrenException;
use Maatify\Category\Exception\CategoryNotFoundException;
use Maatify\Category\Exception\CategoryTranslationNotFoundException;
use Maatify\SharedCommon\Contracts\ClockInterface;

/** Coordinates Category business rules and owns application mutation time. */
final readonly class CategoryCommandService implements CategoryCommandServiceInterface
{
    public function __construct(
        private CategoryCommandRepositoryInterface $commandRepository,
        private CategoryQueryReaderInterface $queryReader,
        private CategoryTranslationCommandRepositoryInterface $translationCommandRepository,
        private CategoryTransactionInterface $transaction,
        private ClockInterface $clock,
    ) {}

    public function create(CreateCategoryDTO $command): int
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

    public function move(MoveCategoryDTO $command): void
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

    public function softDelete(SoftDeleteCategoryDTO $command): void
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

    public function restore(RestoreCategoryDTO $command): void
    {
        $this->transaction->run(function () use ($command): void {
            $this->requireCategoryForUpdate($command->categoryId);

            if (!$this->commandRepository->restore($command, $this->clock->now())) {
                throw CategoryNotFoundException::withId($command->categoryId);
            }
        });
    }

    public function updateStatus(UpdateCategoryStatusDTO $command): void
    {
        $this->requireActiveCategory($command->categoryId);

        if (!$this->commandRepository->updateStatus($command, $this->clock->now())) {
            throw CategoryNotFoundException::withId($command->categoryId);
        }
    }

    public function updateDisplayOrder(UpdateCategoryDisplayOrderDTO $command): void
    {
        $this->requireActiveCategory($command->categoryId);

        if (!$this->commandRepository->updateDisplayOrder($command, $this->clock->now())) {
            throw CategoryNotFoundException::withId($command->categoryId);
        }
    }

    public function updateTranslation(UpdateCategoryTranslationDTO $command): void
    {
        if ($this->queryReader->findTranslationById($command->translationId) === null) {
            throw CategoryTranslationNotFoundException::withId($command->translationId);
        }

        if (!$this->translationCommandRepository->update($command, $this->clock->now())) {
            throw CategoryTranslationNotFoundException::withId($command->translationId);
        }
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
