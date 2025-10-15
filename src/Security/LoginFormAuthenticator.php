<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Security;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractFormLoginAuthenticator
{
    use TargetPathTrait;

    /**
     * @var FormFactoryInterface
     */
    private FormFactoryInterface $formFactory;
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;
    /**
     * @var RouterInterface
     */
    private RouterInterface $router;
    /**
     * @var UserPasswordEncoderInterface
     */
    private UserPasswordEncoderInterface $userPasswordEncoder;

    /**
     * LoginFormAuthenticator constructor.
     *
     * @param FormFactoryInterface         $formFactory
     * @param EntityManagerInterface       $entityManager
     * @param RouterInterface              $router
     * @param UserPasswordEncoderInterface $userPasswordEncoder
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        UserPasswordEncoderInterface $userPasswordEncoder
    ) {
        $this->formFactory = $formFactory;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->userPasswordEncoder = $userPasswordEncoder;
    }

    /**
     * @param Request $request
     *
     * @return boolean
     */
    public function supports(Request $request): bool
    {
        return $request->attributes->get('_route') === 'app_account_login_action'
            && $request->isMethod('POST');
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    public function getCredentials(Request $request): array
    {
        $credentials = $request->request->get('login');
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $credentials['loginUsername']);

        return [
            'loginUsername' => $credentials['loginUsername'],
            'loginPassword' => $credentials['loginPassword'],
        ];
    }

    /**
     * @param array                 $credentials
     * @param UserProviderInterface $userProvider
     *
     * @return User|null|object|UserInterface
     */
    public function getUser(array $credentials, UserProviderInterface $userProvider): User|object|UserInterface|null
    {
        return $this->entityManager->getRepository(User::class)
            ->findOneBy(['username' => $credentials['loginUsername']]);
    }

    /**
     * @param array         $credentials
     * @param UserInterface $user
     *
     * @return boolean
     */
    public function checkCredentials(array $credentials, UserInterface $user): bool
    {
        if ($this->userPasswordEncoder->isPasswordValid($user, $credentials['loginPassword'])) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    protected function getLoginUrl(): string
    {
        return $this->router->generate('app_account_login');
    }

    /**
     * @param Request        $request
     * @param TokenInterface $token
     * @param string         $providerKey
     *
     * @return RedirectResponse
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $providerKey): RedirectResponse
    {
        $targetPath = $this->getTargetPath($request->getSession(), $providerKey);
        if (!$targetPath) {
            $targetPath = $this->router->generate('app_dashboard_default');
        }
        if ($token->getUser()->hasCredentialsExpired()) {
            $targetPath = 'app_account_changepassword';
        }

        return new RedirectResponse($targetPath);
    }
}
