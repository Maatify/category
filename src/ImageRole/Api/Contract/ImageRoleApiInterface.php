<?php

declare(strict_types=1);

namespace Maatify\Category\ImageRole\Api\Contract;

use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;
use Maatify\Category\ImageRole\CategoryImageRoleDTO;
use Maatify\Category\ImageRole\Contract\ImageRoleServiceInterface;
use Maatify\Category\ImageRole\Lifecycle\Command\CreateCategoryImageRoleCommand;
use Maatify\Category\ImageRole\Lifecycle\Command\RestoreCategoryImageRoleCommand;
use Maatify\Category\ImageRole\Lifecycle\Command\SoftDeleteCategoryImageRoleCommand;
use Maatify\Category\ImageRole\Lifecycle\Command\UpdateCategoryImageRoleStatusCommand;
use Maatify\Category\ImageRole\Query\DTO\CategoryImageRoleCollectionDTO;
use Maatify\Category\ImageRole\Query\DTO\CategoryImageRoleListCriteriaDTO;

interface ImageRoleApiInterface
{
    public function create(CreateCategoryImageRoleCommand $command): int;

    public function updateStatus(UpdateCategoryImageRoleStatusCommand $command): void;

    public function softDelete(SoftDeleteCategoryImageRoleCommand $command): void;

    public function restore(RestoreCategoryImageRoleCommand $command): void;

    public function getByIdForManagement(
        int $roleId,
        CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
    ): CategoryImageRoleDTO;

    public function getByKeyForManagement(
        string $roleKey,
        CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
    ): CategoryImageRoleDTO;

    public function listForManagement(CategoryImageRoleListCriteriaDTO $criteria): CategoryImageRoleCollectionDTO;
}
