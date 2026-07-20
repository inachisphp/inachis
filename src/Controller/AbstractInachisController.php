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
use Inachis\Factory\PageViewFactory;
use Inachis\Repository\Waste\WasteRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimit;

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
        PageViewFactory $pageViewFactory,
        protected RequestStack $requestStack,
    ) {
        parent::__construct($params, $pageViewFactory);

        $this->viewModel = $pageViewFactory->createAdmin();
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

    protected function requiresTwoFactor(): bool
    {
        $request = $this->requestStack->getCurrentRequest();

        return $request?->getSession()->get(
            'security.totp_pending',
            false
        ) ?? false;
    }

    protected function isFullyAuthenticated(): bool
    {
        return $this->isAuthenticated()
            && !$this->requiresTwoFactor();
    }

    /**
     * If the user is trying to access a page such as sign-in but is already authenticated
     * they will be redirected to the dashboard.
     *
     * @return string
     */
    public function redirectIfAuthenticated(): string
    {
        if ($this->isFullyAuthenticated()) {
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

    /**
     * Sends a 'Too many requests' response
     *
     * @param string $message
     * @param RateLimit $limit
     * @return Response
     */
    protected function tooManyRequests(string $message, RateLimit $limit): Response
    {
        return new Response(
            $message,
            Response::HTTP_TOO_MANY_REQUESTS,
            [
                'X-RateLimit-Remaining' => (string) $limit->getRemainingTokens(),
                'X-RateLimit-Retry-After' => (string) max(
                    0,
                    $limit->getRetryAfter()->getTimestamp() - time()
                ),
                'X-RateLimit-Limit' => (string) $limit->getLimit(),
            ]
        );
    }
}
