<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Controller\AbstractController;
use Inachis\Entity\User\User;
use Inachis\Repository\Waste\WasteRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use DateTimeImmutable;
use DateInterval;

/**
 * Abstract controller for Inachis.
 */
abstract class AbstractInachisController extends AbstractController
{
    /**
     * @var array<string, string>
     */
    protected array $errors = [];

    /**
     * @param EntityManagerInterface $entityManager
     * @param Security $security
     * @param TranslatorInterface $translator
     */
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected ParameterBagInterface $params,
        protected Security $security,
        protected TranslatorInterface $translator,
        protected WasteRepository $wasteRepository,
    ) {}

    /**
     * Sets the default data for the controller.
     */
    public function setDefaults(): void
    {
        $sessionTimeout = new DateTimeImmutable();
        $sessionTimeout = $sessionTimeout->add(new DateInterval('PT' . ini_get('session.gc_maxlifetime') . 'S'));

        $this->data = [
            'settings' => [
                'siteTitle' => $this->params->has('app.config.title')
                    ? $this->params->get('app.config.title')
                    : 'Untitled Site',
                'domain' => $this->getProtocolAndHostname(),
                'google' => [],
                'language' => $this->params->has('app.config.locale') 
                    ? $this->params->get('app.config.locale') 
                    : 'en',
                'textDirection' => $this->params->has('app.config.textDirection') 
                    ? $this->params->get('app.config.textDirection') 
                    : 'ltr',
                'abstract' => $this->params->has('app.config.abstract') 
                    ? $this->params->get('app.config.abstract') 
                    : '',
                'geotagContent' => $this->params->has('app.config.geotagContent') 
                    ? $this->params->get('app.config.geotagContent') 
                    : false,
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
            'deleted_items' => $this->wasteRepository->getWasteCount(),
        ];
        $this->data['timeout_template'] = base64_encode($this->renderView('inadmin/dialog/session_timeout.html.twig'));
    }

    /**
     * Returns the current User, more specific than the parent Symfony getUser function.
     * If the user is not signed in, it returns an empty User object.
     *
     * @return User
     */
    protected function getCurrentUser(): User
    {
        $user = parent::getUser();

        return $user instanceof User ? $user : new User();
    }

    /**
     * Returns all current errors on the page.
     *
     * @return array<string, string> The array of errors
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
     * Checks if the user is authenticated.
     * 
     * @return bool
     */
    protected function isAuthenticated(): bool
    {
        return $this->security->getUser() instanceof User;
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
