<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Model\Domain;

/**
 * Severity of validation issue.
 */
enum Severity: string
{
    case Error = 'error';
    case Warning = 'warning';
    case Info = 'info';
}
