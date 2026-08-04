<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Enum\System;

enum PackageType: string
{
    case Core = 'core';
    case Plugin = 'plugin';
    case Theme = 'theme';
}
