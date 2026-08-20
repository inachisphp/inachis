<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\EventSubscriber;

use Inachis\EventSubscriber\FeatureRegistrySubscriber;
use Inachis\Service\Theme\FeatureRegistry;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class FeatureRegistrySubscriberTest extends TestCase
{
    private FeatureRegistry $featureRegistry;
    private FeatureRegistrySubscriber $subscriber;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var FeatureRegistry $featureRegistry */
        $this->featureRegistry = (new \ReflectionClass(FeatureRegistry::class))->newInstanceWithoutConstructor();
        $this->subscriber = new FeatureRegistrySubscriber($this->featureRegistry);
    }

    #[Test]
    public function itReturnsSubscribedEvents(): void
    {
        self::assertSame(
            [KernelEvents::REQUEST => 'onKernelRequest'],
            FeatureRegistrySubscriber::getSubscribedEvents(),
        );
    }

    #[Test]
    public function itHandlesKernelRequestEvent(): void
    {
        $event = $this->createMock(RequestEvent::class);

        $this->subscriber->onKernelRequest($event);

        self::assertTrue(true);
    }
}
