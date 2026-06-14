<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\EventSubscriber;

use Inachis\Service\Theme\FeatureRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * FeatureRegistrySubscriber
 */
final readonly class FeatureRegistrySubscriber implements EventSubscriberInterface
{
	/**
	 * Constructor for the FeatureRegistrySubscriber
	 *
	 * @param FeatureRegistry $featureRegistry
	 */
    public function __construct(private FeatureRegistry $featureRegistry) {}

	/**
	 * Gets the function name to use for subscribed events
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
	 * Registers the available features
	 *
	 * @param RequestEvent $event
	 * @return void
	 */
    public function onKernelRequest(RequestEvent $event): void
    {
        // $this->featureRegistry->register('trips');
    }
}
