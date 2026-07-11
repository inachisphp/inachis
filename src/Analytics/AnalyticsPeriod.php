<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Analytics;

final readonly class AnalyticsPeriod
{
    public function __construct(
        public \DateTimeImmutable $from,
        public \DateTimeImmutable $to,
        public string $range,
        public string $label,
    ) {}

    /**
	 * Returns the previous period of the same length.
	 *
	 * @return self
	 */
    public function previous(): self
	{
		$days = $this->from->diff($this->to)->days + 1;
		$interval = new \DateInterval("P{$days}D");

		return new self(
			from: $this->from->sub($interval),
			to: $this->from->sub(new \DateInterval('P1D')),
			range: $this->range,
			label: 'Previous ' . $this->label,
		);
	}
}
