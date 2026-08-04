<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Analytics;

use Inachis\Exception\InvalidAnalyticsPeriodException;
use Inachis\Model\ContentQueryParameters;
use Inachis\Model\Page\ViewStateDefaults;
use Inachis\Service\Content\ViewStateManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Determine the analytics period to show.
 */
class AnalyticsPeriodResolver
{
    public function __construct(
        private ViewStateManager $viewStateManager,
    ) {
    }

    /**
     * Returns an {@link AnalyticsPeriod} for the current view based
     * on stored preferences and requested tab.
     *
     * @throws InvalidAnalyticsPeriodException
     */
    public function resolve(
        Request $request,
        string $stateKey,
    ): AnalyticsPeriod {
        $state = $this->viewStateManager->load(
            $request,
            $stateKey,
            new ViewStateDefaults(
                filters: [
                    'period' => [
                        'range' => '30d',
                        'from' => null,
                        'to' => null,
                    ],
                ],
            ),
        );

        if (!$request->query->has('range')) {
            $period = $state->getFilters()['period'] ?? [];

            if (!empty($period['range'])) {
                $request->query->set('range', $period['range']);
            }

            if (!empty($period['from'])) {
                $request->query->set('from', $period['from']);
            }

            if (!empty($period['to'])) {
                $request->query->set('to', $period['to']);
            }
        }

        $period = AnalyticsPeriodFactory::fromRequest($request);

        $this->viewStateManager->save(
            $request->getSession(),
            $stateKey,
            new ContentQueryParameters(
                filters: [
                    'period' => [
                        'range' => $period->range,
                        'from' => $period->from->format('Y-m-d'),
                        'to' => $period->to->format('Y-m-d'),
                    ],
                ],
                sort: '',
                limit: 0,
                offset: 0,
                view: '',
            ),
        );

        return $period;
    }
}
