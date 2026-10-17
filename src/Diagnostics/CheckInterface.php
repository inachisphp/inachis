<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Diagnostics;

interface CheckInterface
{
    public function getId(): string;

    public function getLabel(): string;

    public function getSection(): string;

    public function run(): CheckResult;
}
