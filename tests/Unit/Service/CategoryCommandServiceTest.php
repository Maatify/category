<?php

declare(strict_types=1);

namespace Maatify\Category\Tests\Unit\Service;

use DateTimeImmutable;
use DateTimeZone;
use Maatify\Category\Contract\CategoryCommandRepositoryInterface;
use Maatify\Category\Contract\CategoryQueryReaderInterface;
use Maatify\Category\Contract\CategoryContentCommandRepositoryInterface;
use Maatify\Category\Contract\CategoryImageAssignmentCommandRepositoryInterface;
use Maatify\Category\Contract\CategoryContentFieldCommandRepositoryInterface;
use Maatify\Category\DTO\CategoryDTO;
use Maatify\Category\DTO\CategoryContentDTO;
use Maatify\Category\DTO\CategoryImageAssignmentDTO;
use Maatify\Category\DTO\CategoryImageRoleDTO;
use Maatify\Category\DTO\CategoryContentFieldDTO;
use Maatify\Category\Command\CreateCategoryCommand;
use Maatify\Category\Command\CreateCategoryContentCommand;
use Maatify\Category\Command\CreateCategoryImageAssignmentCommand;
use Maatify\Category\Command\ClearCategoryImageAssignmentDefaultCommand;
use Maatify\Category\Command\MoveCategoryCommand;
use Maatify\Category\Command\RestoreCategoryCommand;
use Maatify\Category\Command\RestoreCategoryContentCommand;
use Maatify\Category\Command\RestoreCategoryImageAssignmentCommand;
use Maatify\Category\Command\SetCategoryImageAssignmentDefaultCommand;
use Maatify\Category\Command\SoftDeleteCategoryCommand;
use Maatify\Category\Command\SoftDeleteCategoryContentCommand;
use Maatify\Category\Command\SoftDeleteCategoryImageAssignmentCommand;
use Maatify\Category\Command\UpdateCategoryDisplayOrderCommand;
use Maatify\Category\Command\UpdateCategoryStatusCommand;
use Maatify\Category\Command\UpdateCategoryContentCommand;
use Maatify\Category\Command\UpdateCategoryImageAssignmentDisplayOrderCommand;
use Maatify\Category\Command\CreateCategoryContentFieldCommand;
use Maatify\Category\Command\UpdateCategoryContentFieldCommand;
use Maatify\Category\Command\UpdateCategoryContentFieldDisplayOrderCommand;
use Maatify\Category\Command\SoftDeleteCategoryContentFieldCommand;
use Maatify\Category\Command\RestoreCategoryContentFieldCommand;
use Maatify\Category\Enum\CategoryStatusEnum;
use Maatify\Category\Enum\CategoryContentFieldFormatEnum;
use Maatify\Category\Exception\CategoryCodeAlreadyExistsException;
use Maatify\Category\Exception\CategoryCycleException;
use Maatify\Category\Exception\CategoryHasNonDeletedChildrenException;
use Maatify\Category\Exception\CategoryInvalidArgumentException;
use Maatify\Category\Service\CategoryCommandService;
use Maatify\SharedCommon\Contracts\ClockInterface;
use Maatify\Persistence\Pdo\Transaction\TransactionRunnerInterface;
use PHPUnit\Framework\TestCase;
use Throwable;

final class CategoryCommandServiceTest extends TestCase
{
    public function testCreateValidatesParentAndKeepsCodeOutsideMutationContracts(): void
    {
        $queryReader = new InMemoryCategoryQueryReader([$this->category(7, null)]);
        $commandRepository = new InMemoryCategoryCommandRepository();
        $transaction = new InMemoryCategoryTransaction();
        $service = $this->service($commandRepository, $queryReader, $transaction);

        $createdId = $service->create(new CreateCategoryCommand('shirts', '7'));

        self::assertSame(99, $createdId);
        $created = $commandRepository->created;
        self::assertNotNull($created);
        self::assertSame('shirts', $created->code);
        self::assertSame(7, $created->parentId);
        self::assertSame(1, $transaction->runs);
        self::assertSame([7], $queryReader->lockedIds);
        self::assertSame('2026-01-03 00:00:00', $commandRepository->occurredAt?->format('Y-m-d H:i:s'));
        self::assertFalse(property_exists(UpdateCategoryStatusCommand::class, 'code'));
        self::assertFalse(property_exists(UpdateCategoryDisplayOrderCommand::class, 'code'));
    }

    public function testCreateRejectsAStableCodeThatAlreadyExistsIncludingSoftDeletedRows(): void
    {
        $queryReader = new InMemoryCategoryQueryReader([$this->category(7, null, $this->deletedAt())]);
        $commandRepository = new InMemoryCategoryCommandRepository();
        $service = $this->service($commandRepository, $queryReader);

        $this->expectException(CategoryCodeAlreadyExistsException::class);
        $service->create(new CreateCategoryCommand('category-7'));
    }

    public function testMoveToAValidParentIsDelegated(): void
    {
        $queryReader = new InMemoryCategoryQueryReader([
            $this->category(1, null),
            $this->category(2, null),
            $this->category(3, 1),
        ]);
        $commandRepository = new InMemoryCategoryCommandRepository();
        $transaction = new InMemoryCategoryTransaction();
        $service = $this->service($commandRepository, $queryReader, $transaction);

        $service->move(new MoveCategoryCommand(3, 2));

        $moved = $commandRepository->moved;
        self::assertNotNull($moved);
        self::assertSame(3, $moved->categoryId);
        self::assertSame(2, $moved->parentId);
        self::assertSame(1, $transaction->runs);
        self::assertSame([3, 2], $queryReader->lockedIds);
        self::assertSame('2026-01-03 00:00:00', $commandRepository->occurredAt?->format('Y-m-d H:i:s'));
    }

    public function testDirectSelfParentIsRejectedByTheInputCommand(): void
    {
        $this->expectException(CategoryInvalidArgumentException::class);

        new MoveCategoryCommand('7', '7');
    }

    public function testIndirectCycleIsRejectedAcrossTheWholeAncestorChain(): void
    {
        $queryReader = new InMemoryCategoryQueryReader([
            $this->category(1, null),
            $this->category(2, 1),
            $this->category(3, 2),
        ]);
        $commandRepository = new InMemoryCategoryCommandRepository();
        $transaction = new InMemoryCategoryTransaction();
        $service = $this->service($commandRepository, $queryReader, $transaction);

        try {
            $service->move(new MoveCategoryCommand(1, 3));
            self::fail('The complete ancestor chain must reject an indirect cycle.');
        } catch (CategoryCycleException) {
            self::assertSame(1, $transaction->runs);
            self::assertSame(1, $transaction->rollbacks);
            self::assertSame([1, 3, 2], $queryReader->lockedIds);
        }
    }

    public function testSoftDeleteRejectsANonDeletedChild(): void
    {
        $queryReader = new InMemoryCategoryQueryReader([
            $this->category(1, null),
            $this->category(2, 1),
        ]);
        $commandRepository = new InMemoryCategoryCommandRepository();
        $transaction = new InMemoryCategoryTransaction();
        $service = $this->service($commandRepository, $queryReader, $transaction);

        try {
            $service->softDelete(new SoftDeleteCategoryCommand(1));
            self::fail('A Category with a non-deleted child must not be soft-deleted.');
        } catch (CategoryHasNonDeletedChildrenException) {
            self::assertSame(1, $transaction->runs);
            self::assertSame(1, $transaction->rollbacks);
            self::assertNull($commandRepository->softDeleted);
        }
    }

    public function testSoftDeleteIsAllowedWhenAllChildrenAreDeleted(): void
    {
        $queryReader = new InMemoryCategoryQueryReader([
            $this->category(1, null),
            $this->category(2, 1, $this->deletedAt()),
        ]);
        $commandRepository = new InMemoryCategoryCommandRepository();
        $transaction = new InMemoryCategoryTransaction();
        $service = $this->service($commandRepository, $queryReader, $transaction);

        $service->softDelete(new SoftDeleteCategoryCommand('1'));

        $deleted = $commandRepository->softDeleted;
        self::assertNotNull($deleted);
        self::assertSame(1, $deleted->categoryId);
        self::assertSame(1, $transaction->runs);
    }

    public function testRestoreUsesTheExistingCategoryIdentity(): void
    {
        $queryReader = new InMemoryCategoryQueryReader([$this->category(11, null, $this->deletedAt())]);
        $commandRepository = new InMemoryCategoryCommandRepository();
        $transaction = new InMemoryCategoryTransaction();
        $service = $this->service($commandRepository, $queryReader, $transaction);

        $service->restore(new RestoreCategoryCommand('11'));

        $restored = $commandRepository->restored;
        self::assertNotNull($restored);
        self::assertSame(11, $restored->categoryId);
        self::assertSame(1, $transaction->runs);
    }

    public function testStatusAndDisplayOrderMutationsAreDedicatedOperations(): void
    {
        $queryReader = new InMemoryCategoryQueryReader([$this->category(5, null)]);
        $commandRepository = new InMemoryCategoryCommandRepository();
        $transaction = new InMemoryCategoryTransaction();
        $service = $this->service($commandRepository, $queryReader, $transaction);

        $service->updateStatus(new UpdateCategoryStatusCommand(5, CategoryStatusEnum::INACTIVE));
        $service->updateDisplayOrder(new UpdateCategoryDisplayOrderCommand(5, 3));

        self::assertSame(CategoryStatusEnum::INACTIVE, $commandRepository->statusUpdated?->status);
        self::assertSame(3, $commandRepository->displayOrderUpdated?->displayOrder);
        self::assertSame([5, 5], $queryReader->lockedIds);
        self::assertSame(2, $transaction->runs);
    }

    public function testAllDisplayOrderMutationsUseTheSharedTransactionRunnerAndLockRows(): void
    {
        $assignment = new CategoryImageAssignmentDTO(
            id: 21,
            categoryId: 5,
            mediaAssetId: 900,
            languageCode: null,
            platform: null,
            displayOrder: 1,
            createdAt: $this->createdAt(),
            updatedAt: $this->createdAt(),
            deletedAt: null,
        );
        $field = new CategoryContentFieldDTO(
            id: 31,
            categoryId: 5,
            fieldKey: 'badge',
            languageCode: null,
            platform: null,
            format: CategoryContentFieldFormatEnum::TEXT,
            value: 'new',
            displayOrder: 1,
            createdAt: $this->createdAt(),
            updatedAt: $this->createdAt(),
            deletedAt: null,
        );
        $queryReader = new InMemoryCategoryQueryReader(
            [$this->category(5, null)],
            [],
            [$assignment],
            [$field],
        );
        $commandRepository = new InMemoryCategoryCommandRepository();
        $imageRepository = new InMemoryCategoryImageAssignmentCommandRepository();
        $fieldRepository = new InMemoryCategoryContentFieldCommandRepository();
        $transaction = new InMemoryCategoryTransaction();
        $service = new CategoryCommandService(
            $commandRepository,
            $queryReader,
            new InMemoryCategoryContentCommandRepository(),
            $imageRepository,
            $fieldRepository,
            $transaction,
            new FixedClock(),
        );

        $service->updateDisplayOrder(new UpdateCategoryDisplayOrderCommand(5, 2));
        $service->updateImageAssignmentDisplayOrder(
            new UpdateCategoryImageAssignmentDisplayOrderCommand(21, 2),
        );
        $service->updateContentFieldDisplayOrder(
            new UpdateCategoryContentFieldDisplayOrderCommand(31, 2),
        );

        self::assertSame(3, $transaction->runs);
        self::assertSame(3, $transaction->commits);
        self::assertSame([5], $queryReader->lockedIds);
        self::assertSame([21], $queryReader->lockedAssignmentIds);
        self::assertSame([31], $queryReader->lockedContentFieldIds);
        self::assertSame(2, $commandRepository->displayOrderUpdated?->displayOrder);
        self::assertSame(2, $imageRepository->displayOrderUpdated?->displayOrder);
        self::assertSame(2, $fieldRepository->displayOrderUpdated?->displayOrder);
    }

    public function testContentMutationCannotChangeItsLogicalIdentity(): void
    {
        $content = new CategoryContentDTO(
            id: 21,
            categoryId: 5,
            languageCode: 'en-US',
            name: 'Shirts',
            description: null,
            createdAt: $this->createdAt(),
            updatedAt: $this->createdAt(),
            deletedAt: null,
        );
        $queryReader = new InMemoryCategoryQueryReader([$this->category(5, null)], [$content]);
        $commandRepository = new InMemoryCategoryCommandRepository();
        $contentRepository = new InMemoryCategoryContentCommandRepository();
        $service = new CategoryCommandService(
            $commandRepository,
            $queryReader,
            $contentRepository,
            new InMemoryCategoryImageAssignmentCommandRepository(),
            new InMemoryCategoryContentFieldCommandRepository(),
            new InMemoryCategoryTransaction(),
            new FixedClock(),
        );

        $service->updateContent(new UpdateCategoryContentCommand(21, 'قمصان', 'وصف'));

        $updated = $contentRepository->updated;
        self::assertNotNull($updated);
        self::assertSame(21, $updated->contentId);
        self::assertSame([21], $queryReader->lockedContentIds);
        self::assertFalse(property_exists(UpdateCategoryContentCommand::class, 'categoryId'));
        self::assertFalse(property_exists(UpdateCategoryContentCommand::class, 'languageCode'));
    }

    public function testContentLifecycleUsesTypedOperationsAndPreservesIdentity(): void
    {
        $queryReader = new InMemoryCategoryQueryReader(
            [$this->category(5, null)],
            [new CategoryContentDTO(
                id: 77,
                categoryId: 5,
                languageCode: 'en-US',
                name: 'Shirts',
                description: null,
                createdAt: $this->createdAt(),
                updatedAt: $this->createdAt(),
                deletedAt: null,
            )],
        );
        $contentRepository = new InMemoryCategoryContentCommandRepository();
        $transaction = new InMemoryCategoryTransaction();
        $service = new CategoryCommandService(
            new InMemoryCategoryCommandRepository(),
            $queryReader,
            $contentRepository,
            new InMemoryCategoryImageAssignmentCommandRepository(),
            new InMemoryCategoryContentFieldCommandRepository(),
            $transaction,
            new FixedClock(),
        );

        $createdId = $service->createContent(
            new CreateCategoryContentCommand(5, 'en-US', 'Shirts', null),
        );
        $service->updateContent(new UpdateCategoryContentCommand($createdId, 'قمصان', 'وصف'));
        $service->softDeleteContent(new SoftDeleteCategoryContentCommand($createdId));
        $service->restoreContent(new RestoreCategoryContentCommand($createdId));

        self::assertSame(77, $createdId);
        self::assertNotNull($contentRepository->created);
        self::assertSame(5, $contentRepository->created->categoryId);
        self::assertSame('en-US', $contentRepository->created->languageCode);
        self::assertSame($createdId, $contentRepository->updated?->contentId);
        self::assertSame($createdId, $contentRepository->softDeleted?->contentId);
        self::assertSame($createdId, $contentRepository->restored?->contentId);
        self::assertSame(4, $transaction->runs);
    }

    public function testImageAssignmentLifecycleUsesTypedOperationsAndKeepsItsIdentity(): void
    {
        $assignment = new CategoryImageAssignmentDTO(
            id: 77,
            categoryId: 5,
            mediaAssetId: 900,
            languageCode: 'en-US',
            platform: 'web',
            displayOrder: 1,
            createdAt: $this->createdAt(),
            updatedAt: $this->createdAt(),
            deletedAt: null,
        );
        $queryReader = new InMemoryCategoryQueryReader(
            [$this->category(5, null)],
            [],
            [$assignment],
        );
        $imageRepository = new InMemoryCategoryImageAssignmentCommandRepository();
        $transaction = new InMemoryCategoryTransaction();
        $service = new CategoryCommandService(
            new InMemoryCategoryCommandRepository(),
            $queryReader,
            new InMemoryCategoryContentCommandRepository(),
            $imageRepository,
            new InMemoryCategoryContentFieldCommandRepository(),
            $transaction,
            new FixedClock(),
        );

        $createdId = $service->createImageAssignment(
            new CreateCategoryImageAssignmentCommand(5, 900, 'en-US', 'web'),
        );
        $service->updateImageAssignmentDisplayOrder(
            new UpdateCategoryImageAssignmentDisplayOrderCommand($createdId, 2),
        );
        $service->setImageAssignmentDefault(new SetCategoryImageAssignmentDefaultCommand($createdId));
        $service->clearImageAssignmentDefault(new ClearCategoryImageAssignmentDefaultCommand($createdId));
        $service->softDeleteImageAssignment(new SoftDeleteCategoryImageAssignmentCommand($createdId));
        $service->restoreImageAssignment(new RestoreCategoryImageAssignmentCommand($createdId));

        self::assertSame(77, $createdId);
        self::assertNotNull($imageRepository->created);
        self::assertNotNull($imageRepository->displayOrderUpdated);
        self::assertNotNull($imageRepository->defaultSet);
        self::assertNotNull($imageRepository->defaultCleared);
        self::assertNotNull($imageRepository->softDeleted);
        self::assertNotNull($imageRepository->restored);
        self::assertSame(5, $imageRepository->created->categoryId);
        self::assertSame(900, $imageRepository->created->mediaAssetId);
        self::assertSame(2, $imageRepository->displayOrderUpdated->displayOrder);
        self::assertSame($createdId, $imageRepository->softDeleted->assignmentId);
        self::assertSame($createdId, $imageRepository->restored->assignmentId);
        self::assertSame($createdId, $imageRepository->defaultSet->assignmentId);
        self::assertSame($createdId, $imageRepository->defaultCleared->assignmentId);
        self::assertSame(6, $transaction->runs);
    }

    private function service(
        InMemoryCategoryCommandRepository $commandRepository,
        InMemoryCategoryQueryReader $queryReader,
        ?InMemoryCategoryTransaction $transaction = null,
    ): CategoryCommandService {
        return new CategoryCommandService(
            $commandRepository,
            $queryReader,
            new InMemoryCategoryContentCommandRepository(),
            new InMemoryCategoryImageAssignmentCommandRepository(),
            new InMemoryCategoryContentFieldCommandRepository(),
            $transaction ?? new InMemoryCategoryTransaction(),
            new FixedClock(),
        );
    }

    private function category(int $id, ?int $parentId, ?DateTimeImmutable $deletedAt = null): CategoryDTO
    {
        return new CategoryDTO(
            id: $id,
            parentId: $parentId,
            code: 'category-' . $id,
            status: CategoryStatusEnum::ACTIVE,
            displayOrder: 1,
            createdAt: $this->createdAt(),
            updatedAt: $this->createdAt(),
            deletedAt: $deletedAt,
        );
    }

    private function createdAt(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-01-01 00:00:00 Africa/Cairo');
    }

    private function deletedAt(): DateTimeImmutable
    {
        return new DateTimeImmutable('2026-01-02 00:00:00 Africa/Cairo');
    }
}

/** @internal Test-only in-memory query port. */
final class InMemoryCategoryQueryReader implements CategoryQueryReaderInterface
{
    /** @var list<int> */
    public array $lockedIds = [];

    /** @var list<int> */
    public array $lockedContentIds = [];

    /** @var list<int> */
    public array $lockedAssignmentIds = [];

    /** @var list<int> */
    public array $lockedContentFieldIds = [];

    /** @var list<CategoryDTO> */
    private array $categories;

        /** @var list<CategoryContentDTO> */
        private array $contents;

    /** @var list<CategoryImageAssignmentDTO> */
    private array $assignments;

    /** @var list<CategoryContentFieldDTO> */
    private array $fields;

    /**
     * @param list<CategoryDTO>            $categories
     * @param list<CategoryContentDTO> $contents
     * @param list<CategoryImageAssignmentDTO> $assignments
     * @param list<CategoryContentFieldDTO> $fields
     */
    public function __construct(array $categories, array $contents = [], array $assignments = [], array $fields = [])
    {
        $this->categories = $categories;
        $this->contents = $contents;
        $this->assignments = $assignments;
        $this->fields = $fields;
    }

    public function findById(int $categoryId): ?CategoryDTO
    {
        foreach ($this->categories as $category) {
            if ($category->id === $categoryId) {
                return $category;
            }
        }

        return null;
    }

    public function findByCode(string $code): ?CategoryDTO
    {
        foreach ($this->categories as $category) {
            if ($category->code === $code) {
                return $category;
            }
        }

        return null;
    }

    public function findActiveById(int $categoryId): ?CategoryDTO
    {
        foreach ($this->categories as $category) {
            if ($category->id === $categoryId && $category->deletedAt === null) {
                return $category;
            }
        }

        return null;
    }

    public function findActiveByIdForUpdate(int $categoryId): ?CategoryDTO
    {
        $this->lockedIds[] = $categoryId;

        return $this->findActiveById($categoryId);
    }

    public function findByIdForUpdate(int $categoryId): ?CategoryDTO
    {
        $this->lockedIds[] = $categoryId;

        return $this->findById($categoryId);
    }

    public function hasNonDeletedChildrenForUpdate(int $categoryId): bool
    {
        foreach ($this->categories as $category) {
            if ($category->parentId === $categoryId && $category->deletedAt === null) {
                return true;
            }
        }

        return false;
    }

    public function findContentById(int $contentId): ?CategoryContentDTO
    {
        foreach ($this->contents as $content) {
            if ($content->id === $contentId) {
                return $content;
            }
        }

        return null;
    }

    public function findContentByIdForUpdate(int $contentId): ?CategoryContentDTO
    {
        $this->lockedContentIds[] = $contentId;

        return $this->findContentById($contentId);
    }

    public function findImageAssignmentById(int $assignmentId): ?CategoryImageAssignmentDTO
    {
        foreach ($this->assignments as $assignment) {
            if ($assignment->id === $assignmentId) {
                return $assignment;
            }
        }

        return null;
    }

    public function findImageAssignmentByIdForUpdate(int $assignmentId): ?CategoryImageAssignmentDTO
    {
        $this->lockedAssignmentIds[] = $assignmentId;

        return $this->findImageAssignmentById($assignmentId);
    }

    public function findImageRoleByIdForUpdate(int $roleId): ?CategoryImageRoleDTO
    {
        return null;
    }

    public function findContentFieldById(int $fieldId): ?CategoryContentFieldDTO
    {
        return null;
    }

    public function findContentFieldByIdForUpdate(int $fieldId): ?CategoryContentFieldDTO
    {
        $this->lockedContentFieldIds[] = $fieldId;

        foreach ($this->fields as $field) {
            if ($field->id === $fieldId) {
                return $field;
            }
        }

        return null;
    }
}

/** @internal Test-only in-memory command port. */
final class InMemoryCategoryCommandRepository implements CategoryCommandRepositoryInterface
{
    public ?DateTimeImmutable $occurredAt = null;

    public ?CreateCategoryCommand $created = null;
    public ?MoveCategoryCommand $moved = null;
    public ?SoftDeleteCategoryCommand $softDeleted = null;
    public ?RestoreCategoryCommand $restored = null;
    public ?UpdateCategoryStatusCommand $statusUpdated = null;
    public ?UpdateCategoryDisplayOrderCommand $displayOrderUpdated = null;

    public function create(CreateCategoryCommand $command, DateTimeImmutable $occurredAt): int
    {
        $this->created = $command;
        $this->occurredAt = $occurredAt;

        return 99;
    }

    public function move(MoveCategoryCommand $command, DateTimeImmutable $occurredAt): bool
    {
        $this->moved = $command;
        $this->occurredAt = $occurredAt;

        return true;
    }

    public function softDelete(SoftDeleteCategoryCommand $command, DateTimeImmutable $occurredAt): bool
    {
        $this->softDeleted = $command;
        $this->occurredAt = $occurredAt;

        return true;
    }

    public function restore(RestoreCategoryCommand $command, DateTimeImmutable $occurredAt): bool
    {
        $this->restored = $command;
        $this->occurredAt = $occurredAt;

        return true;
    }

    public function updateStatus(UpdateCategoryStatusCommand $command, DateTimeImmutable $occurredAt): bool
    {
        $this->statusUpdated = $command;
        $this->occurredAt = $occurredAt;

        return true;
    }

    public function updateDisplayOrder(UpdateCategoryDisplayOrderCommand $command, DateTimeImmutable $occurredAt): bool
    {
        $this->displayOrderUpdated = $command;
        $this->occurredAt = $occurredAt;

        return true;
    }
}

/** @internal Test-only in-memory content command port. */
final class InMemoryCategoryContentCommandRepository implements CategoryContentCommandRepositoryInterface
{
    public ?CreateCategoryContentCommand $created = null;
    public ?UpdateCategoryContentCommand $updated = null;
    public ?SoftDeleteCategoryContentCommand $softDeleted = null;
    public ?RestoreCategoryContentCommand $restored = null;

    public function create(CreateCategoryContentCommand $command, DateTimeImmutable $occurredAt): int
    {
        $this->created = $command;

        return 77;
    }

    public function update(UpdateCategoryContentCommand $command, DateTimeImmutable $occurredAt): bool
    {
        $this->updated = $command;

        return true;
    }

    public function softDelete(
        SoftDeleteCategoryContentCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool {
        $this->softDeleted = $command;

        return true;
    }

    public function restore(
        RestoreCategoryContentCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool {
        $this->restored = $command;

        return true;
    }
}

/** @internal Test-only in-memory image assignment command port. */
final class InMemoryCategoryImageAssignmentCommandRepository implements CategoryImageAssignmentCommandRepositoryInterface
{
    public ?CreateCategoryImageAssignmentCommand $created = null;
    public ?UpdateCategoryImageAssignmentDisplayOrderCommand $displayOrderUpdated = null;
    public ?SetCategoryImageAssignmentDefaultCommand $defaultSet = null;
    public ?ClearCategoryImageAssignmentDefaultCommand $defaultCleared = null;
    public ?SoftDeleteCategoryImageAssignmentCommand $softDeleted = null;
    public ?RestoreCategoryImageAssignmentCommand $restored = null;

    public function create(CreateCategoryImageAssignmentCommand $command, DateTimeImmutable $occurredAt): int
    {
        $this->created = $command;

        return 77;
    }

    public function updateDisplayOrder(
        UpdateCategoryImageAssignmentDisplayOrderCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool {
        $this->displayOrderUpdated = $command;

        return true;
    }

    public function setDefault(
        SetCategoryImageAssignmentDefaultCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool {
        $this->defaultSet = $command;

        return true;
    }

    public function clearDefault(
        ClearCategoryImageAssignmentDefaultCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool {
        $this->defaultCleared = $command;

        return true;
    }

    public function softDelete(
        SoftDeleteCategoryImageAssignmentCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool {
        $this->softDeleted = $command;

        return true;
    }

    public function restore(
        RestoreCategoryImageAssignmentCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool {
        $this->restored = $command;

        return true;
    }
}

/** @internal Test-only in-memory content field command port. */
final class InMemoryCategoryContentFieldCommandRepository implements CategoryContentFieldCommandRepositoryInterface
{
    public ?CreateCategoryContentFieldCommand $created = null;
    public ?UpdateCategoryContentFieldCommand $updated = null;
    public ?UpdateCategoryContentFieldDisplayOrderCommand $displayOrderUpdated = null;
    public ?SoftDeleteCategoryContentFieldCommand $softDeleted = null;
    public ?RestoreCategoryContentFieldCommand $restored = null;

    public function create(CreateCategoryContentFieldCommand $command, DateTimeImmutable $occurredAt): int
    {
        $this->created = $command;

        return 88;
    }

    public function update(UpdateCategoryContentFieldCommand $command, DateTimeImmutable $occurredAt): bool
    {
        $this->updated = $command;

        return true;
    }

    public function updateDisplayOrder(
        UpdateCategoryContentFieldDisplayOrderCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool {
        $this->displayOrderUpdated = $command;

        return true;
    }

    public function softDelete(
        SoftDeleteCategoryContentFieldCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool {
        $this->softDeleted = $command;

        return true;
    }

    public function restore(
        RestoreCategoryContentFieldCommand $command,
        DateTimeImmutable $occurredAt,
    ): bool {
        $this->restored = $command;

        return true;
    }
}

/** @internal Test-only transaction port. */
final class InMemoryCategoryTransaction implements TransactionRunnerInterface
{
    public int $runs = 0;
    public int $commits = 0;
    public int $rollbacks = 0;

    public function run(callable $operation): mixed
    {
        $this->runs++;

        try {
            $result = $operation();
            $this->commits++;

            return $result;
        } catch (Throwable $exception) {
            $this->rollbacks++;

            throw $exception;
        }
    }
}

/** @internal Test-only deterministic application clock. */
final class FixedClock implements ClockInterface
{
    private DateTimeImmutable $now;

    public function __construct()
    {
        $this->now = new DateTimeImmutable('2026-01-03 00:00:00 Africa/Cairo');
    }

    public function now(): DateTimeImmutable
    {
        return $this->now;
    }

    public function getTimezone(): DateTimeZone
    {
        return $this->now->getTimezone();
    }
}
