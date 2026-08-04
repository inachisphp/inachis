<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Analytics;

use Inachis\Analytics\AnalyticsPeriod;
use Inachis\Analytics\AnalyticsPeriodResolver;
use Inachis\Model\ContentQueryParameters;
use Inachis\Model\Page\ViewStateDefaults;
use Inachis\Service\Content\ViewStateManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;

final class AnalyticsPeriodResolverTest extends TestCase
{
    private ViewStateManagerInterface $viewStateManager;

    protected function setUp(): void
    {
        $this->viewStateManager = $this->createMock(
            ViewStateManagerInterface::class
        );
    }

    public function testResolvesDefaultPeriodWhenNoRangeProvided(): void
    {
        $request = $this->createRequest();

        $this->viewStateManager
            ->expects($this->once())
            ->method('load')
            ->with(
                $request,
                'analytics',
                $this->isInstanceOf(ViewStateDefaults::class)
            )
            ->willReturn(
                new ContentQueryParameters(
                    filters: [
                        'period' => [
                            'range' => '30d',
                            'from' => null,
                            'to' => null,
                        ],
                    ],
                    sort: '',
                    limit: 0,
                    offset: 0,
                    view: '',
                )
            );

        $this->viewStateManager
            ->expects($this->once())
            ->method('save');

        $resolver = new AnalyticsPeriodResolver(
            $this->viewStateManager
        );

        $period = $resolver->resolve(
            $request,
            'analytics'
        );

        self::assertInstanceOf(
            AnalyticsPeriod::class,
            $period
        );

        self::assertSame(
            '30d',
            $period->range
        );

        self::assertSame(
            'Last 30 Days',
            $period->label
        );
    }

    public function testUsesSavedRangeWhenRequestHasNoRange(): void
    {
        $request = $this->createRequest();

        $this->viewStateManager
            ->method('load')
            ->willReturn(
                new ContentQueryParameters(
                    filters: [
                        'period' => [
                            'range' => '7d',
                            'from' => null,
                            'to' => null,
                        ],
                    ],
                    sort: '',
                    limit: 0,
                    offset: 0,
                    view: '',
                )
            );

        $this->viewStateManager
            ->expects($this->once())
            ->method('save');

        $resolver = new AnalyticsPeriodResolver(
            $this->viewStateManager
        );

        $period = $resolver->resolve(
            $request,
            'analytics'
        );

        self::assertSame(
            '7d',
            $period->range
        );

        self::assertSame(
            'Last 7 Days',
            $period->label
        );

        self::assertSame(
            '7d',
            $request->query->get('range')
        );
    }

    public function testRequestRangeOverridesSavedState(): void
    {
        $request = $this->createRequest([
            'range' => 'today',
        ]);

        $this->viewStateManager
            ->method('load')
            ->willReturn(
                new ContentQueryParameters(
                    filters: [
                        'period' => [
                            'range' => '30d',
                            'from' => null,
                            'to' => null,
                        ],
                    ],
                    sort: '',
                    limit: 0,
                    offset: 0,
                    view: '',
                )
            );

        $this->viewStateManager
            ->expects($this->once())
            ->method('save');

        $resolver = new AnalyticsPeriodResolver(
            $this->viewStateManager
        );

        $period = $resolver->resolve(
            $request,
            'analytics'
        );

        self::assertSame(
            'today',
            $period->range
        );

        self::assertSame(
            'Today',
            $period->label
        );
    }

    public function testRequestCustomOverridesSavedState(): void
    {
        $request = $this->createRequest([
            'range' => 'custom',
            'from' => '2026-01-01',
            'to' => '2026-01-31',
        ]);

        $this->viewStateManager
            ->method('load')
            ->willReturn(
                new ContentQueryParameters(
                    filters: [
                        'period' => [
                            'range' => 'custom',
                            'from' => '2026-01-01',
                            'to' => '2026-01-31',
                        ],
                    ],
                    sort: '',
                    limit: 0,
                    offset: 0,
                    view: '',
                )
            );

        $this->viewStateManager
            ->expects($this->once())
            ->method('save');

        $resolver = new AnalyticsPeriodResolver(
            $this->viewStateManager
        );

        $period = $resolver->resolve(
            $request,
            'analytics'
        );

        self::assertSame(
            'custom',
            $period->range
        );

        self::assertSame(
            '1 Jan 2026 – 31 Jan 2026',
            $period->label
        );
    }

    private function createRequest(
        array $query = [],
    ): Request {
        $request = Request::create(
            '/',
            'GET',
            $query
        );

        $request->setSession(
            new Session()
        );

        return $request;
    }
}
