<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller;

use Inachis\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyController;

/**
 * 
 */
abstract class AbstractController extends SymfonyController
{
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
     * Checks if the user is authenticated.
     * 
     * @return bool
     */
    protected function isAuthenticated(): bool
    {
        return $this->security->getUser() instanceof User;
    }

    /**
     * Returns all current errors on the page.
     *
     * @return string[] The array of errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Returns a specific error message given by it's unique name.
     *
     * @param string $error The name of the error message to retrieve
     * @return string|null The requested error message if set
     */
    public function getError(string $error): ?string
    {
        return $this->errors[$error] ?? null;
    }

    /**
     * Adds an error to the current controller to be displayed/handled on
     * by the view.
     *
     * @param string $error   Unique identifier for the error
     * @param string $message The friendly message for the error
     */
    public function addError(string $error, string $message): void
    {
        $this->errors[$error] = (string) $message;
    }

    /**
     * Redirects to the setup page if there are no admins.
     * 
     * @return string
     */
    public function redirectIfNoAdmins(): string
    {
        if ($this->entityManager->getRepository(User::class)->count([]) == 0) {
            return 'incc_setup_stage1';
        }
        return '';
    }

    /**
     * If the user is trying to access a page such as sign-in but is already authenticated
     * they will be redirected to the dashboard.
     *
     * @return string
     */
    public function redirectIfAuthenticated(): string
    {
        if ($this->isAuthenticated()) {
            return 'incc_dashboard';
        }
        return '';
    }

    /**
     * Redirects to the dashboard if the user is authenticated or to the setup page if there are no admins.
     * 
     * @return string|null
     */
    public function redirectIfAuthenticatedOrNoAdmins(): ?string
    {
        return $this->redirectIfAuthenticated() ?: $this->redirectIfNoAdmins();
    }
}