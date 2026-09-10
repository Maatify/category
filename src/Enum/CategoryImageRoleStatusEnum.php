<?php

declare(strict_types=1);

namespace Maatify\Category\Enum;

/** Typed lifecycle status for a package-owned Category Image Role. */
enum CategoryImageRoleStatusEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
