<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Flex\Response;

abstract class AbstractInachisController extends AbstractController
{
    protected $security;
    protected $entityManager;
    protected $errors = [];
    protected $data = [];

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    /**
     * @return void
     */
    public function setDefaults(): void
    {
        $this->data = [
            'settings' => [
                'siteTitle' => $_ENV['APP_TITLE'],//$this->getParameter('app.config.title') ?: 'Untitled Site',
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
            'post' => [
                'featureImage' => '',
            ],
            'session' => $this->security->getUser(),
        ];
    }

    /**
     * @return string
     */
    private function getProtocolAndHostname() : string
    {
        $protocol = $this->isSecure() ? 'https://' : 'http://';

        return $protocol . (!empty($_ENV['APP_DOMAIN']) ? $_ENV['APP_DOMAIN'] : '');
    }

    /**
     * @return bool
     */
    private function isSecure() : bool
    {
        $isSecure = false;
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
            $isSecure = true;
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
            || !empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on'
        ) {
            $isSecure = true;
        }

        return $isSecure;
    }

    /**
     * Returns the result of testing if a user is currently signed in
     * @return bool Status of user authentication
     */
    private function isAuthenticated() : bool
    {
        return $this->security instanceof Security &&
            $this->security->getUser() instanceof User &&
            !empty($this->security->getUser()->getUsername());
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
     *
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
     * @return string
     */
    public function redirectIfNoAdmins(): string
    {
        if ($this->entityManager->getRepository(User::class)->count([]) == 0) {
            return 'app_setup_stage1';
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
            return 'app_dashboard_default';
        }
        return '';
    }

    /**
     * @return string|null
     */
    public function redirectIfAuthenticatedOrNoAdmins(): ?string
    {
        return $this->redirectIfAuthenticated() ?: $this->redirectIfNoAdmins();
    }

    /**
     * If the user has a referrer set they will be redirected to it otherwise they will be redirected to
     * the dashboard.
     *
     * @param Request  $request
     * @param Response $response The response object from the router
     *
     * @return Response
     */
    public function redirectToReferrerOrDashboard(Request $request, Response $response): Response
    {
        $referrer = $request->getSession()->get('referrer');
        if (!empty($referrer)) {
//            return $response->redirect($referrer)->send();
        }
//        return $response->redirect('/incc/')->send();

        $response->prepare($request);

        return $response->send();
    }
}
