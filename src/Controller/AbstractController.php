<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyController;

/**
 * 
 */
abstract class AbstractController extends SymfonyController
{

    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

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
     * Applies properties to the current page view
     *
     * @param array<string, string> $properties
     */
    protected function setPageProperties(array $properties)
    {
        $this->data['page'] = array_merge($this->data['page'], $properties);
    }
}
