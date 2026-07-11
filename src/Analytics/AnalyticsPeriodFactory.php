<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Analytics;

use Inachis\Exception\InvalidAnalyticsPeriodException;
use Symfony\Component\HttpFoundation\Request;

final class AnalyticsPeriodFactory
{
    /**
     * Creates an analytics period from the current request.
     *
     * @param Request $request
     * @return AnalyticsPeriod
     */
    public static function fromRequest(Request $request): AnalyticsPeriod
    {
        $range = $request->query->get('range', '30d');
        $today = new \DateTimeImmutable('today');

        return match ($range) {
            'today' => new AnalyticsPeriod(
                from: $today,
                to: $today,
                range: 'today',
                label: 'Today',
            ),

            'yesterday' => new AnalyticsPeriod(
                from: $today->modify('-1 days'),
                to: $today->modify('-1 days'),
                range: 'yesterday',
                label: 'Yesterday',
            ),

            '7d' => new AnalyticsPeriod(
                from: $today->modify('-6 days'),
                to: $today,
                range: '7d',
                label: 'Last 7 Days',
            ),

            '30d' => new AnalyticsPeriod(
                from: $today->modify('-29 days'),
                to: $today,
                range: '30d',
                label: 'Last 30 Days',
            ),

            '90d' => new AnalyticsPeriod(
                from: $today->modify('-89 days'),
                to: $today,
                range: '90d',
                label: 'Last 90 Days',
            ),

            'this-month' => new AnalyticsPeriod(
                from: $today->modify('first day of this month'),
                to: $today,
                range: 'this-month',
                label: 'This Month',
            ),

            'last-month' => new AnalyticsPeriod(
                from: $today->modify('first day of last month'),
                to: $today->modify('last day of last month'),
                range: 'last-month',
                label: 'Last Month',
            ),

            'this-year' => new AnalyticsPeriod(
                from: $today->setDate((int) $today->format('Y'), 1, 1),
                to: $today,
                range: 'this-year',
                label: 'This Year',
            ),

            'custom' => self::createCustom($request),

            default => self::fallback(),
        };
    }

    /**
     * Creates a custom analytics period.
     *
     * @param Request $request
     * @return AnalyticsPeriod
     */
    private static function createCustom(Request $request): AnalyticsPeriod
    {
        $from = $request->query->get('from');
        $to = $request->query->get('to');

        if (!$from || !$to) {
            throw new InvalidAnalyticsPeriodException(
                'Both a start and end date are required.'
            );
        }

        try {
            $fromDate = new \DateTimeImmutable($from);
            $toDate = new \DateTimeImmutable($to);
        } catch (\Throwable) {
            throw new InvalidAnalyticsPeriodException(
                'The selected dates are invalid.'
            );
        }

        if ($fromDate > $toDate) {
            [$fromDate, $toDate] = [$toDate, $fromDate];
        }

        $today = new \DateTimeImmutable('today');
        if ($toDate > $today) {
            $toDate = $today;
        }

        $maxDays = 365;
        if ($fromDate->diff($toDate)->days > $maxDays) {
            throw new InvalidAnalyticsPeriodException(
                'Custom date ranges cannot exceed one year.'
            );
        }

        return new AnalyticsPeriod(
            from: $fromDate,
            to: $toDate,
            range: 'custom',
            label: sprintf(
                '%s – %s',
                $fromDate->format('j M Y'),
                $toDate->format('j M Y'),
            ),
        );
    }

    /**
     * Returns the default reporting period.
     *
     * @return AnalyticsPeriod
     */
    private static function fallback(): AnalyticsPeriod
    {
        $today = new \DateTimeImmutable('today');

        return new AnalyticsPeriod(
            from: $today->modify('-29 days'),
            to: $today,
            range: '30d',
            label: 'Last 30 Days',
        );
    }
}
