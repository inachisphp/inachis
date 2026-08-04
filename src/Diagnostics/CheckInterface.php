<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Diagnostics;

interface CheckInterface
{
    public function getId(): string;

    public function getLabel(): string;

    public function getSection(): string;

    public function run(): CheckResult;
}
