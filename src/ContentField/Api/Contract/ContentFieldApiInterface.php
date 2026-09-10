<?php

declare(strict_types=1);

namespace Maatify\Category\ContentField\Api\Contract;

use Maatify\Category\Common\Enum\CategoryDeletedStateEnum;
use Maatify\Category\ContentField\CategoryContentFieldScopeDTO;
use Maatify\Category\ContentField\Contract\ContentFieldServiceInterface;
use Maatify\Category\ContentField\Mutation\Command\CreateCategoryContentFieldCommand;
use Maatify\Category\ContentField\Mutation\Command\RestoreCategoryContentFieldCommand;
use Maatify\Category\ContentField\Mutation\Command\SoftDeleteCategoryContentFieldCommand;
use Maatify\Category\ContentField\Mutation\Command\UpdateCategoryContentFieldCommand;
use Maatify\Category\ContentField\Ordering\Command\UpdateCategoryContentFieldDisplayOrderCommand;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldCollectionDTO;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldDTO;
use Maatify\Category\ContentField\Query\DTO\CategoryContentFieldListCriteriaDTO;
use Maatify\Category\Query\DTO\CategoryVisibleListCriteriaDTO;

interface ContentFieldApiInterface
{
    public function create(CreateCategoryContentFieldCommand $command): int;

    public function update(UpdateCategoryContentFieldCommand $command): void;

    public function updateDisplayOrder(UpdateCategoryContentFieldDisplayOrderCommand $command): void;

    public function softDelete(SoftDeleteCategoryContentFieldCommand $command): void;

    public function restore(RestoreCategoryContentFieldCommand $command): void;

    public function listVisibleForCategory(
        int $categoryId,
        CategoryContentFieldScopeDTO $scope,
        CategoryVisibleListCriteriaDTO $criteria = new CategoryVisibleListCriteriaDTO(),
    ): CategoryContentFieldCollectionDTO;

    public function getByIdForManagement(
        int $fieldId,
        CategoryDeletedStateEnum $deletedState = CategoryDeletedStateEnum::NON_DELETED,
    ): CategoryContentFieldDTO;

    public function listForManagement(CategoryContentFieldListCriteriaDTO $criteria): CategoryContentFieldCollectionDTO;
}
