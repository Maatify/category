<?php

declare(strict_types=1);

namespace Maatify\Category\Api;

use Maatify\Category\Api\Contract\CategoryFacadeInterface;
use Maatify\Category\Api\Domain\CategoryApi;
use Maatify\Category\Content\Api\ContentApi;
use Maatify\Category\Content\Infrastructure\PdoCategoryContentCommandRepository;
use Maatify\Category\Content\Service\ContentService;
use Maatify\Category\ContentField\Api\ContentFieldApi;
use Maatify\Category\ContentField\Infrastructure\PdoCategoryContentFieldCommandRepository;
use Maatify\Category\ContentField\Service\ContentFieldService;
use Maatify\Category\ImageAssignment\Api\ImageAssignmentApi;
use Maatify\Category\ImageAssignment\Infrastructure\PdoCategoryImageAssignmentCommandRepository;
use Maatify\Category\ImageAssignment\Service\ImageAssignmentService;
use Maatify\Category\ImageRole\Api\ImageRoleApi;
use Maatify\Category\ImageRole\Infrastructure\PdoCategoryImageRoleCommandRepository;
use Maatify\Category\ImageRole\Service\ImageRoleService;
use Maatify\Category\Infrastructure\PdoCategoryCommandRepository;
use Maatify\Category\Query\Infrastructure\PdoCategoryManagementReadQuery;
use Maatify\Category\Query\Infrastructure\PdoCategoryQueryReader;
use Maatify\Category\Query\Infrastructure\PdoCategoryReadQuery;
use Maatify\Category\Service\CategoryService;
use Maatify\Persistence\Pdo\Ordering\ScopedOrderingManager;
use Maatify\Persistence\Pdo\Transaction\PdoTransactionRunner;
use Maatify\SharedCommon\Contracts\ClockInterface;
use PDO;

/** Builds all package-owned adapters and Domain APIs from Host primitives. */
final class CategoryFactory
{
    private function __construct() {}

    public static function create(PDO $pdo, ClockInterface $clock): CategoryFacadeInterface
    {
        $transaction = new PdoTransactionRunner($pdo);
        $ordering = new ScopedOrderingManager();
        $queryReader = new PdoCategoryQueryReader($pdo, $clock);
        $visibleReader = new PdoCategoryReadQuery($pdo, $clock);
        $managementReader = new PdoCategoryManagementReadQuery($pdo, $clock);

        return new CategoryFacade(
            new CategoryApi(new CategoryService(
                new PdoCategoryCommandRepository($pdo, $ordering),
                $queryReader,
                $visibleReader,
                $managementReader,
                $transaction,
                $clock,
            )),
            new ContentApi(new ContentService(
                new PdoCategoryContentCommandRepository($pdo),
                $queryReader,
                $visibleReader,
                $managementReader,
                $transaction,
                $clock,
            )),
            new ContentFieldApi(new ContentFieldService(
                new PdoCategoryContentFieldCommandRepository($pdo, $ordering),
                $queryReader,
                $visibleReader,
                $managementReader,
                $transaction,
                $clock,
            )),
            new ImageRoleApi(new ImageRoleService(
                new PdoCategoryImageRoleCommandRepository($pdo),
                $queryReader,
                $managementReader,
                $transaction,
                $clock,
            )),
            new ImageAssignmentApi(new ImageAssignmentService(
                new PdoCategoryImageAssignmentCommandRepository($pdo, $ordering),
                $queryReader,
                $visibleReader,
                $managementReader,
                $transaction,
                $clock,
            )),
        );
    }
}
