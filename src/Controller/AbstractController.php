<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller;

use Inachis\Model\System\PageMetadata;
use Inachis\Model\System\PageView;
use Inachis\Model\System\SiteSettings;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * 
 */
abstract class AbstractController extends SymfonyController
{
    protected PageView $viewModel;

    public function __construct(protected ParameterBagInterface $params)
    {
        $this->viewModel = $this->createPageViewModel();
    }

    /**
     * Gets the protocol and hostname.
     * 
     * @return string
     */
    protected function getProtocolAndHostname(): string
    {
        $protocol = $this->isSecure() ? 'https://' : 'http://';
        $domain = $_ENV['APP_DOMAIN'] ?? '';
        if (!is_string($domain)) {
            $domain = '';
        }
        return $protocol . $domain;
    }

    /**
     * Checks if the request is secure.
     * 
     * @return bool
     */
    protected function isSecure(): bool
    {
        $isSecure = false;
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $isSecure = true;
        } elseif (
            !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
            || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on'
        ) {
            $isSecure = true;
        }

        return $isSecure;
    }

    /**
     * Sets up the {@link PageView} model
     *
     * @return PageView
     */
    protected function createPageViewModel(): PageView
    {
        $settings = new SiteSettings(
            siteTitle: $this->params->has('app.config.title')
                && is_string($this->params->get('app.config.title'))
                ? $this->params->get('app.config.title')
                : 'Untitled Site',

            domain: $this->getProtocolAndHostname(),

            google: [],

            language: $this->params->has('app.config.locale')
                && is_string($this->params->get('app.config.locale'))
                ? $this->params->get('app.config.locale')
                : 'en',

            textDirection: $this->params->has('app.config.textDirection')
                && is_string($this->params->get('app.config.textDirection'))
                ? $this->params->get('app.config.textDirection')
                : 'ltr',

            abstract: $this->params->has('app.config.abstract')
                && is_string($this->params->get('app.config.abstract'))
                ? $this->params->get('app.config.abstract')
                : '',

            geotagContent: $this->params->has('app.config.geotagContent')
                && is_bool($this->params->get('app.config.geotagContent'))
                ? $this->params->get('app.config.geotagContent')
                : false,
        );

        $page = new PageMetadata();

        return new PageView($settings, $page);
    }
}
