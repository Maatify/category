<?php

declare(strict_types=1);

namespace Maatify\Category\Enum;

enum CategoryStatusEnum: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
