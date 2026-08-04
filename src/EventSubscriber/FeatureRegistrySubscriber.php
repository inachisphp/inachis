<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\EventSubscriber;

use Inachis\Service\Theme\FeatureRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * FeatureRegistrySubscriber.
 */
final readonly class FeatureRegistrySubscriber implements EventSubscriberInterface
{
    /**
     * Constructor for the FeatureRegistrySubscriber.
     */
    public function __construct(private FeatureRegistry $featureRegistry)
    {
    }

    /**
     * Gets the function name to use for subscribed events.
     *
     * @return array<string,string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    /**
     * Registers the available features.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        // $this->featureRegistry->register('trips');
    }
}
