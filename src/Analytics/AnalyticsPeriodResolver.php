<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Analytics;

use Inachis\Exception\InvalidAnalyticsPeriodException;
use Inachis\Model\ContentQueryParameters;
use Inachis\Model\Page\ViewStateDefaults;
use Inachis\Service\Content\ViewStateManager;
use Symfony\Component\HttpFoundation\Request;

class AnalyticsPeriodResolver
{
    public function __construct(
        private ViewStateManager $viewStateManager
    ) {}

    /**
     * @throws InvalidAnalyticsPeriodException
     */
    public function resolve(
        Request $request,
        string $stateKey
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
