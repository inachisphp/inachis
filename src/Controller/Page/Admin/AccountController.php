<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Admin;

use DateTimeImmutable;
use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\User\User;
use Inachis\Form\ChangePasswordType;
use Inachis\Form\ForgotPasswordType;
use Inachis\Form\LoginType;
use Inachis\Repository\User\PasswordResetRequestRepository;
use Inachis\Repository\User\UserPasskeyRepository;
use Inachis\Repository\User\UserRepository;
use Inachis\Security\Authentication\PasskeyService;
use Inachis\Security\Authentication\TotpService;
use Inachis\Security\Authentication\TwoFactorAuthenticationListener;
use Inachis\Service\User\PasswordResetTokenService;
use Inachis\Service\User\UserAccountEmailService;
use Random\RandomException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Account controller
 */
class AccountController extends AbstractInachisController
{
    /**
     * Login
     *
     * @param Request $request
     * @param AuthenticationUtils $authenticationUtils
     * @return Response The response the controller results in
     */
    #[Route("/incc/login", name: "incc_account_login")]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        $redirectTo = $this->redirectIfAuthenticatedOrNoAdmins();
        if (!empty($redirectTo)) {
            return $this->redirectToRoute($redirectTo);
        }
        $form = $this->createForm(LoginType::class, [
            'loginUsername' => $authenticationUtils->getLastUsername(),
        ]);
        $form->handleRequest($request);
        $this->viewModel->page->title = 'Sign In';

        return $this->render('inadmin/page/admin/signin.html.twig', [
            'viewModel' => $this->viewModel,
            'form'      => $form->createView(),
            'expired'   => $request->query->has('expired'),
            'error'     => $authenticationUtils->getLastAuthenticationError(),
        ]);
    }

    /**
     * TOTP verification page shown after successful password authentication.
     *
     * Redirects to the dashboard if TOTP is already verified for this session
     * or if the user does not have TOTP enabled.
     *
     * @param Request     $request
     * @param TotpService $totpService
     * @return Response
     */
    #[Route("/incc/login/totp", name: "incc_account_login_totp", methods: ["GET", "POST"])]
    public function totpVerify(Request $request, TotpService $totpService): Response
    {
        /** @var User|null $user */
        $user = $this->security->getUser();

        if (!$user instanceof User || !$user->isTotpEnabled()) {
            return $this->redirectToRoute('incc_dashboard');
        }

        $session = $request->getSession();

        if ($session->get(TwoFactorAuthenticationListener::SESSION_TOTP_VERIFIED_KEY) === true) {
            return $this->redirectToRoute('incc_dashboard');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $code = trim($request->request->getString('totp_code'));
            if ($totpService->verifyCode((string) $user->getTotpSecret(), $code)) {
                $session->set(TwoFactorAuthenticationListener::SESSION_TOTP_VERIFIED_KEY, true);
                return $this->redirectToRoute('incc_dashboard');
            }
            $error = 'Invalid code. Please try again.';
        }

        $this->viewModel->page->title = 'Two-Factor Authentication';
        return $this->render('inadmin/page/admin/totp_verify.html.twig', [
            'viewModel' => $this->viewModel,
            'error'     => $error,
        ]);
    }

    /**
     * Returns a WebAuthn challenge for passkey-based login.
     *
     * @param Request        $request
     * @param PasskeyService $passkeyService
     * @return JsonResponse
     */
    #[Route("/incc/login/passkey/challenge", name: "incc_account_login_passkey_challenge", methods: ["GET"])]
    public function passkeyChallenge(Request $request, PasskeyService $passkeyService): JsonResponse
    {
        $challenge = $passkeyService->generateChallenge();
        $request->getSession()->set('inachis.passkey.login_challenge', $challenge);

        $rpId    = $request->getHost();
        $options = $passkeyService->buildRequestOptions(null, $challenge, $rpId);

        return new JsonResponse($options);
    }

    /**
     * Verifies a passkey assertion and logs the user in.
     *
     * @param Request              $request
     * @param PasskeyService       $passkeyService
     * @param UserPasskeyRepository $passkeyRepository
     * @return JsonResponse
     */
    #[Route("/incc/login/passkey/verify", name: "incc_account_login_passkey_verify", methods: ["POST"])]
    public function passkeyVerify(
        Request $request,
        PasskeyService $passkeyService,
        UserPasskeyRepository $passkeyRepository,
    ): JsonResponse {
        $session   = $request->getSession();
        $challenge = $session->get('inachis.passkey.login_challenge');

        if (empty($challenge)) {
            return new JsonResponse(['error' => 'No active challenge.'], 400);
        }

        $body = json_decode($request->getContent(), true);
        if (!is_array($body)) {
            return new JsonResponse(['error' => 'Invalid JSON body.'], 400);
        }

        $credentialId = $body['id'] ?? '';
        $passkey      = $passkeyRepository->findByCredentialId($credentialId);

        if ($passkey === null) {
            return new JsonResponse(['error' => 'Passkey not recognised.'], 401);
        }

        try {
            $passkeyService->verifyAssertion(
                $passkey,
                $challenge,
                $request->getHost(),
                $body,
            );
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }

        $session->remove('inachis.passkey.login_challenge');
        // Mark TOTP as satisfied when using a passkey (passkeys provide user verification)
        $session->set(TwoFactorAuthenticationListener::SESSION_TOTP_VERIFIED_KEY, true);

        return new JsonResponse(['redirect' => $this->generateUrl('incc_dashboard')]);
    }

    /**
     * Logout
     *
     * @throws \Exception
     */
    #[Route("/incc/logout", name: "incc_logout")]
    public function logout(): void
    {
        throw new \LogicException('This method is blank and will be intercepted by the logout key on your firewall.');
    }

    /**
     * Forgot password
     *
     * @param Request $request
     * @param PasswordResetRequestRepository $passwordResetRequestRepository
     * @param RateLimiterFactoryInterface $forgotPasswordIpLimiter
     * @param RateLimiterFactoryInterface $forgotPasswordAccountLimiter
     * @param UserRepository $userRepository
     * @return Response
     * @throws RandomException
     */
    #[Route("/incc/forgot-password", name: "incc_account_forgot-password", methods: [ "GET", "POST" ])]
    public function forgotPassword(
        Request $request,
        PasswordResetRequestRepository $passwordResetRequestRepository,
        RateLimiterFactoryInterface $forgotPasswordIpLimiter,
        RateLimiterFactoryInterface $forgotPasswordAccountLimiter,
        UserAccountEmailService $userRegistrationService,
        UserRepository $userRepository,
    ): Response {
        $redirectTo = $this->redirectIfAuthenticatedOrNoAdmins();
        if (!empty($redirectTo)) {
            return $this->redirectToRoute($redirectTo);
        }
        $ipLimiter = $forgotPasswordIpLimiter->create($request->getClientIp() ?? 'unknown');
        $limit = $ipLimiter->consume(1);
        if (!$limit->isAccepted()) {
            $headers = [
                'X-RateLimit-Remaining' => $limit->getRemainingTokens(),
                'X-RateLimit-Retry-After' => $limit->getRetryAfter()->getTimestamp() - time(),
                'X-RateLimit-Limit' => $limit->getLimit(),
            ];
            // TODO: replace with something better - throw new TooManyRequestsHttpException();
            return new Response('Too many attempts from this IP. Try again later.', 429, $headers);
        }
        $passwordResetRequestRepository->purgeExpiredHashes();

        $form = $this->createForm(ForgotPasswordType::class, [
            'forgot_email' => $request->request->getString('forgot_email'),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var array{forgot_email:string} $forgotPassword */
            $forgotPassword = $request->request->all('forgot_password');

            $emailAddress = $forgotPassword['forgot_email'];
            if ($emailAddress) {
                $accountLimiter = $forgotPasswordAccountLimiter->create(strtolower($emailAddress));
                $limit = $accountLimiter->consume(1);
                if (!$limit->isAccepted()) {
                    $headers = [
                        'X-RateLimit-Remaining' => $limit->getRemainingTokens(),
                        'X-RateLimit-Retry-After' => $limit->getRetryAfter()->getTimestamp() - time(),
                        'X-RateLimit-Limit' => $limit->getLimit(),
                    ];
                    // TODO: replace with something better - throw new TooManyRequestsHttpException();
                    return new Response('Too many reset attempts for this account. Try again later.', 429, $headers);
                }
            }
            $user = $userRepository->findOneBy([
                'email' => $emailAddress,
            ]);
            if (null !== $user) {
                try {
                    $userRegistrationService->sendForgotPasswordEmail(
                        $user,
                        [
                            'viewModel' => $this->viewModel,
                            'clientIP' => $request->getClientIp(),
                        ],
                        fn (string $token) => $this->generateUrl(
                            'incc_account_new-password',
                            [ 'token' => $token ]
                        )
                    );
                } catch (TransportExceptionInterface $e) {
                    $this->addFlash('warning', 'Error while sending mail: ' . $e->getMessage());
                }
            }
            $this->viewModel->page->title = 'Password reset request sent';
            return $this->render('inadmin/page/admin/forgot-password-sent.html.twig', [
                'viewModel' => $this->viewModel,
                'form' => $this->createFormBuilder()->getForm()->createView(),
            ]);
        }

        $this->viewModel->page->title = 'Request a password reset';
        return $this->render('inadmin/page/admin/forgot-password.html.twig', [
            'viewModel' => $this->viewModel,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @param Request $request
     * @param PasswordResetTokenService $tokenService
     * @param RateLimiterFactoryInterface $forgotPasswordIpLimiter
     * @param UserPasswordHasherInterface $passwordHasher
     * @param UserRepository $userRepository
     * @param string $token
     * @return Response
     */
    #[Route("/incc/new-password/{token}", name: "incc_account_new-password", methods: [ "GET", "POST" ])]
    public function newPassword(
        Request $request,
        PasswordResetTokenService $tokenService,
        RateLimiterFactoryInterface $forgotPasswordIpLimiter,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
        string $token,
    ): Response {
        $redirectTo = $this->redirectIfAuthenticatedOrNoAdmins();
        if (!empty($redirectTo)) {
            return $this->redirectToRoute($redirectTo);
        }

        if (!$token || strlen($token) !== 64) {
            $this->addFlash('warning', 'Invalid token.');
            return $this->redirectToRoute('incc_account_forgot-password');
        }
        $changePassword = $request->request->all('change_password');
        if ($changePassword === []) {
            $changePassword = [
                'username' => '',
            ];
        }

        $form = $this->createForm(
            ChangePasswordType::class,
            [
                'change_password' => $changePassword,
            ],
            [
                'password_reset' => true,
            ]
        );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $limiter = $forgotPasswordIpLimiter->create($request->getClientIp() ?? 'unknown');
            $limit = $limiter->consume(1);
            if (!$limit->isAccepted()) {
                $headers = [
                    'X-RateLimit-Remaining' => $limit->getRemainingTokens(),
                    'X-RateLimit-Retry-After' => $limit->getRetryAfter()->getTimestamp() - time(),
                    'X-RateLimit-Limit' => $limit->getLimit(),
                ];
                // TODO: replace with something better - throw new TooManyRequestsHttpException();
                return new Response('Too many password reset attempts from this IP. Try again later.', 429, $headers);
            };
            /** @var array{
             *     change_password: array{
             *         username: string,
             *         new_password: string
             *     }
             * } $data
             */
            $data = $form->getData();
            $user = $userRepository->findOneBy([
                'username' => $data['change_password']['username'],
            ]);
            if (!$user) {
                $this->addFlash('error', 'Invalid token.');
                return $this->redirectToRoute('incc_account_forgot-password');
            }
            $resetRequest = $tokenService->validateTokenForUser($token, $user);
            if (!$resetRequest) {
                $this->addFlash('error', 'Invalid or expired reset token.');
                return $this->redirectToRoute('incc_account_forgot-password');
            }
            $plainPassword = $data['change_password']['new_password'];

            $hashed = $passwordHasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashed);
            $user->setPasswordChangedAt(new DateTimeImmutable());
            $tokenService->markAsUsed($resetRequest);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'Your password has been reset. You can now log in.');
            return $this->redirectToRoute('incc_account_login');
        }

        return $this->render('inadmin/page/admin/new-password.html.twig', [
            'viewModel' => $this->viewModel,
            'form' => $form->createView(),
            'token' => $token,
        ]);
    }
}
