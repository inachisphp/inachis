<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Analytics;

final readonly class AnalyticsPeriod
{
    public function __construct(
        public \DateTimeImmutable $from,
        public \DateTimeImmutable $to,
        public string $range,
        public string $label,
    ) {
    }

    /**
     * Returns the previous period of the same length.
     */
    public function previous(): self
    {
        $days = $this->from->diff($this->to)->days + 1;
        $interval = new \DateInterval("P{$days}D");

        return new self(
            from: $this->from->sub($interval),
            to: $this->from->sub(new \DateInterval('P1D')),
            range: $this->range,
            label: 'Previous '.$this->label,
        );
    }
}
