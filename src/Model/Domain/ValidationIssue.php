<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Model\Domain;

/**
 * Validation issue.
 */
final readonly class ValidationIssue
{
    public function __construct(
        public string $type,
        public string $message,
        public Severity $severity,
    ) {
    }
}
