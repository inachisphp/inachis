<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Build\Enum;

enum SourceClassType
{
    case ConcreteClass;
    case Interface;
    case Trait;
    case Enum;
}
