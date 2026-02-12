<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller;

use DateTimeImmutable;
use DateInterval;
use Inachis\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract controller for Inachis.
 */
abstract class AbstractInachisController extends AbstractController
{
    /**
     * @var array<string>
     */
    protected array $errors = [];

    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * @param EntityManagerInterface $entityManager
     * @param Security $security
     * @param TranslatorInterface $translator
     */
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected Security $security,
        protected TranslatorInterface $translator
    ) {}

    /**
     * Sets the default data for the controller.
     * 
     * @return void
     */
    public function setDefaults(): void
    {
        $sessionTimeout = new DateTimeImmutable();
        $sessionTimeout = $sessionTimeout->add(new DateInterval('PT' . ini_get('session.gc_maxlifetime') . 'S'));

        $this->data = [
            'settings' => [
                'siteTitle' => $_ENV['APP_TITLE'] ?: 'Untitled Site',//$this->getParameter('app.config.title') ?: 'Untitled Site',
                'domain' => $this->getProtocolAndHostname(),
                'google' => [],
                'language' => //$this->getParameter('app.config.locale') ?
                    //$this->getParameter('app.config.locale') :
                    'en',
                'textDirection' => 'ltr',
                'abstract' => '',
                'geotagContent' => false,
//                'fb_app_id' => null !== $this->getParameter('app.social.fb_app_id') ?
//                    $this->getParameter('app.social.fb_app_id') :
//                    null,
//                'twitter' => null !== $this->getParameter('app.social.twitter') ?
//                    $this->getParameter('app.social.twitter') :
//                    null,
            ],
            'notifications' => [],
            'page'          => [
                'self'          => '',
                'tab'           => '',
                'title'         => '',
                'description'   => '',
                'keywords'      => '',
                'modDate'       => '',
            ],
            'session' => $this->security->getUser(),
            'session_timeout' => ini_get('session.gc_maxlifetime'),
            'session_timeout_time' => $sessionTimeout->format('Y-m-d\TH:i:s'),
        ];
        $this->data['timeout_template'] = base64_encode($this->renderView('inadmin/dialog/session_timeout.html.twig'));
    }

    /**
     * Gets the protocol and hostname.
     * 
     * @return string
     */
    private function getProtocolAndHostname(): string
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
    private function isSecure(): bool
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
    private function isAuthenticated(): bool
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
