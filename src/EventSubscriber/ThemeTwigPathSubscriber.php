<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\EventSubscriber;

use Inachis\Service\Theme\ThemeManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Loader\FilesystemLoader;

/**
 * Subscriber for retrieving current theme path.
 */
final readonly class ThemeTwigPathSubscriber implements EventSubscriberInterface
{
    /**
     * Constructor for ThemeTwigPathSubscriber.
     */
    public function __construct(
        private ThemeManager $themeManager,
        private FilesystemLoader $twigLoader,
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    /**
     * Returns the events this subscriber listens for.
     *
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    /**
     * Prepends the current theme path to Twig's YAML config.
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $defaultThemePath = $this->projectDir.'/templates/themes/default';
        if (is_dir($defaultThemePath) && !in_array($defaultThemePath, $this->twigLoader->getPaths(), true)) {
            $this->twigLoader->prependPath($defaultThemePath);
        }

        $themePath = $this->themeManager->getActiveThemePath();
        if (!is_dir($themePath)) {
            return;
        }

        if (!in_array($themePath, $this->twigLoader->getPaths(), true)) {
            $this->twigLoader->prependPath($themePath);
        }
    }
}
