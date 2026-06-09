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
    /**
     * Constructor for DiagnosticsCollector
     *
     * @param iterable<CheckInterface> $checks
     */
    public function __construct(private iterable $checks) {}

    /**
     * Returns the result of checks
     * @return list<CheckResult>
     */
    public function collect(): array
    {
        $results = [];

        foreach ($this->checks as $check) {
            $results[] = $check->run();
        }

        return $results;
    }

    /**
     * Groups checks by category
     *
     * @return array<string,array<string,string|array<CheckResult>>>
     */
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
