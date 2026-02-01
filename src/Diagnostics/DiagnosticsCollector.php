<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Diagnostics;

final class DiagnosticsCollector
{
    public function __construct(
        private iterable $checks
    ) {}

    /** @return CheckResult[] */
    public function collect(): array
    {
        $results = [];

        foreach ($this->checks as $check) {
            $results[] = $check->run();
        }

        return $results;
    }

    public function grouped(): array
    {
        $groups = [];

        foreach ($this->collect() as $result) {
            $groups[$result->section]['label'] = $result->section;
            $groups[$result->section]['checks'][] = $result;
        }

        return $groups;
    }
}
