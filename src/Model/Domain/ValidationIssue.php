<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Model\Domain;

/**
 * Validation issue
 */
final readonly class ValidationIssue
{
    /**
     * @param string $type
     * @param string $message
     * @param Severity $severity
     */
    public function __construct(
        public string $type,
        public string $message,
        public Severity $severity,
    ) {}
}