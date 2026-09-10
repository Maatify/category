<?php

declare(strict_types=1);

namespace Maatify\Category\Tests\Unit\Service;

use Maatify\Category\Query\Contract\CategoryReadQueryInterface;
use Maatify\Category\Query\Contract\CategoryManagementReadQueryInterface;
use Maatify\Category\Query\Contract\CategoryQueryReaderInterface;
use Maatify\Category\Tests\Unit\Service\Support\InMemoryCategoryTransaction;
use Maatify\Category\Tests\Unit\Service\Support\FixedClock;
use PHPUnit\Framework\TestCase;

/** Test-only construction of isolated domain services with explicit read ports. */
abstract class DomainReadServiceTestCase extends TestCase
{
    protected function categoryService(
        ?CategoryReadQueryInterface $visible = null,
        ?CategoryManagementReadQueryInterface $management = null,
    ): \Maatify\Category\Service\CategoryService {
        return new \Maatify\Category\Service\CategoryService(
            $this->createStub(\Maatify\Category\Contract\CategoryCommandRepositoryInterface::class),
            $this->createStub(CategoryQueryReaderInterface::class),
            $visible ?? $this->createStub(CategoryReadQueryInterface::class),
            $management ?? $this->createStub(CategoryManagementReadQueryInterface::class),
            new InMemoryCategoryTransaction(),
            new FixedClock(),
        );
    }

    protected function contentService(
        ?CategoryReadQueryInterface $visible = null,
        ?CategoryManagementReadQueryInterface $management = null,
    ): \Maatify\Category\Content\Service\ContentService {
        return new \Maatify\Category\Content\Service\ContentService(
            $this->createStub(\Maatify\Category\Content\Contract\CategoryContentCommandRepositoryInterface::class),
            $this->createStub(CategoryQueryReaderInterface::class),
            $visible ?? $this->createStub(CategoryReadQueryInterface::class),
            $management ?? $this->createStub(CategoryManagementReadQueryInterface::class),
            new InMemoryCategoryTransaction(),
            new FixedClock(),
        );
    }

    protected function contentFieldService(
        ?CategoryReadQueryInterface $visible = null,
        ?CategoryManagementReadQueryInterface $management = null,
    ): \Maatify\Category\ContentField\Service\ContentFieldService {
        return new \Maatify\Category\ContentField\Service\ContentFieldService(
            $this->createStub(\Maatify\Category\ContentField\Contract\CategoryContentFieldCommandRepositoryInterface::class),
            $this->createStub(CategoryQueryReaderInterface::class),
            $visible ?? $this->createStub(CategoryReadQueryInterface::class),
            $management ?? $this->createStub(CategoryManagementReadQueryInterface::class),
            new InMemoryCategoryTransaction(),
            new FixedClock(),
        );
    }

    protected function imageRoleService(
        ?CategoryReadQueryInterface $visible = null,
        ?CategoryManagementReadQueryInterface $management = null,
    ): \Maatify\Category\ImageRole\Service\ImageRoleService {
        return new \Maatify\Category\ImageRole\Service\ImageRoleService(
            $this->createStub(\Maatify\Category\ImageRole\Contract\CategoryImageRoleCommandRepositoryInterface::class),
            $this->createStub(CategoryQueryReaderInterface::class),
            $management ?? $this->createStub(CategoryManagementReadQueryInterface::class),
            new InMemoryCategoryTransaction(),
            new FixedClock(),
        );
    }

    protected function imageAssignmentService(
        ?CategoryReadQueryInterface $visible = null,
        ?CategoryManagementReadQueryInterface $management = null,
    ): \Maatify\Category\ImageAssignment\Service\ImageAssignmentService {
        return new \Maatify\Category\ImageAssignment\Service\ImageAssignmentService(
            $this->createStub(\Maatify\Category\ImageAssignment\Contract\CategoryImageAssignmentCommandRepositoryInterface::class),
            $this->createStub(CategoryQueryReaderInterface::class),
            $visible ?? $this->createStub(CategoryReadQueryInterface::class),
            $management ?? $this->createStub(CategoryManagementReadQueryInterface::class),
            new InMemoryCategoryTransaction(),
            new FixedClock(),
        );
    }
}
