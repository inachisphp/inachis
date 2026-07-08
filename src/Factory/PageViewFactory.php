<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Factory;

use Inachis\Model\System\PageMetadata;
use Inachis\Model\System\PageView;
use Inachis\Model\System\SiteSettings;
use Inachis\Repository\Waste\WasteRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class PageViewFactory
{
    public function __construct(
        private readonly ParameterBagInterface $params,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly WasteRepository $wasteRepository,
    ) {
    }

    /**
     * Creates the base page view model.
     */
    public function create(): PageView
    {
        $settings = new SiteSettings(
            siteTitle: $this->getStringParameter('app.config.title', 'Untitled Site'),
            domain: $this->getDomain(),
            google: [],
            language: $this->getStringParameter('app.config.locale', 'en'),
            textDirection: $this->getStringParameter('app.config.textDirection', 'ltr'),
            abstract: $this->getStringParameter('app.config.abstract', ''),
            geotagContent: $this->getBoolParameter('app.config.geotagContent', false),
        );

        return new PageView(
            $settings,
            new PageMetadata(),
        );
    }

    /**
     * Creates an admin page view model.
     */
    public function createAdmin(): PageView
    {
        $view = $this->create();

        $sessionTimeout = new \DateTimeImmutable();
        $sessionTimeout = $sessionTimeout->add(
            new \DateInterval('PT' . ini_get('session.gc_maxlifetime') . 'S')
        );

        $view->session = $this->security->getUser();
        $view->sessionTimeout = (int) ini_get('session.gc_maxlifetime');
        $view->sessionTimeoutTime = $sessionTimeout->format('Y-m-d\TH:i:s');
        $view->deletedItems = $this->wasteRepository->getWasteCount();

        return $view;
    }

    /**
     * Returns the application's base URL.
     */
    private function getDomain(): string
    {
        $request = $this->requestStack->getCurrentRequest();

        if ($request !== null) {
            return $request->getSchemeAndHttpHost();
        }

        // Fallback for CLI, workers, etc.
        $domain = $_ENV['APP_DOMAIN'] ?? '';

        if (!is_string($domain) || $domain === '') {
            return '';
        }

        $https = ($_ENV['APP_HTTPS'] ?? true) !== false;

        return sprintf(
            '%s://%s',
            $https ? 'https' : 'http',
            $domain
        );
    }

    private function getStringParameter(string $name, string $default): string
    {
        if (!$this->params->has($name)) {
            return $default;
        }

        $value = $this->params->get($name);

        return is_string($value) ? $value : $default;
    }

    private function getBoolParameter(string $name, bool $default): bool
    {
        if (!$this->params->has($name)) {
            return $default;
        }

        $value = $this->params->get($name);

        return is_bool($value) ? $value : $default;
    }
}
