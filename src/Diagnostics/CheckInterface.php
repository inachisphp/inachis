<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Diagnostics;

interface CheckInterface
{
    public function getId(): string;
    public function getLabel(): string;
    public function getSection(): string;

    public function run(): CheckResult;
}
