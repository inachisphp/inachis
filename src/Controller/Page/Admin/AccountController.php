<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Controller\Page\Admin;

use App\Controller\AbstractInachisController;
use App\Entity\PasswordResetRequest;
use App\Entity\User;
use App\Form\ChangePasswordType;
use App\Form\ForgotPasswordType;
use App\Form\LoginType;
use App\Service\PasswordResetTokenService;
use App\Util\Base64EncodeFile;
use DateTime;
use Doctrine\ORM\NonUniqueResultException;
use Exception;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3Validator;
use Random\RandomException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

/**
 * Class AccountController.
 */
class AccountController extends AbstractInachisController
{
    /**
     * @param Request             $request
     * @param AuthenticationUtils $authenticationUtils
     * @return Response The response the controller results in
     */
    #[Route("/incc/login", name: "app_account_login", methods: [ "GET", "POST" ])]
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
        $this->data['page']['title'] = 'Sign In';
        $this->data['form'] = $form->createView();
        $this->data['expired'] = $request->query->has('expired');
        $this->data['error'] = $authenticationUtils->getLastAuthenticationError();

        return $this->render('inadmin/page/admin/signin.html.twig', $this->data);
    }

    /**
     * @throws \Exception
     */
    #[Route("/incc/logout", name: "app_logout", methods: [ "GET", "POST" ])]
    public function logout(): never
    {
        throw new Exception('Don\'t forget to activate logout in security.yaml');
    }

    /**
     * @param Request $request
     * @param PasswordResetTokenService $tokenService
     * @param RateLimiterFactoryInterface $forgotPasswordIpLimiter
     * @param RateLimiterFactoryInterface $forgotPasswordAccountLimiter
     * @param MailerInterface $mailer
     * @return Response
     * @throws RandomException
     */
    #[Route("/incc/forgot-password", name: "incc_account_forgot-password", methods: [ "GET", "POST" ])]
    public function forgotPassword(
        Request $request,
        PasswordResetTokenService $tokenService,
        RateLimiterFactoryInterface $forgotPasswordIpLimiter,
        RateLimiterFactoryInterface $forgotPasswordAccountLimiter,
        MailerInterface $mailer,
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
            return new Response('Too many attempts from this IP. Try again later.', 429, $headers);
        }
        $this->entityManager->getRepository(PasswordResetRequest::class)->purgeExpiredHashes();

        $this->data['page']['title'] = 'Request a password reset';
        $form = $this->createForm(ForgotPasswordType::class, [
            'forgot_email' => $request->request->get('forgot_email'),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $emailAddress = (string) $request->request->all('forgot_password')['forgot_email'];
            if ($emailAddress) {
                $accountLimiter = $forgotPasswordAccountLimiter->create(strtolower($emailAddress));
                $limit = $accountLimiter->consume(1);
                if (!$limit->isAccepted()) {
                    $headers = [
                        'X-RateLimit-Remaining' => $limit->getRemainingTokens(),
                        'X-RateLimit-Retry-After' => $limit->getRetryAfter()->getTimestamp() - time(),
                        'X-RateLimit-Limit' => $limit->getLimit(),
                    ];
                    return new Response('Too many reset attempts for this account. Try again later.', 429, $headers);
                }
            }
            $user = $this->entityManager->getRepository(User::class)->findOneBy([
                'email' => $emailAddress,
            ]);
            if (null !== $user) {
                $data = $tokenService->createResetRequestForEmail($emailAddress);
                try {
                    $email = (new TemplatedEmail())
                        ->to(new Address($user->getEmail()))
                        ->subject('Reset your password for ' . $this->data['settings']['siteTitle'])
                        ->htmlTemplate('inadmin/emails/forgot-password.html.twig')
                        ->textTemplate('inadmin/emails/forgot-password.txt.twig')
                        ->context([
                            'ipAddress' => $request->getClientIp(),
                            'url' => $this->generateUrl('incc_account_new-password', [ 'token' => $data['token']]),
                            'expiresAt' => $data['expiresAt']->format('l jS F Y \a\\t H:i'),
                            'settings' => $this->data['settings'],
                            'logo' => Base64EncodeFile::encode('public/assets/imgs/incc/inachis.png'),
                        ]);
                    $mailer->send($email);
                    $this->entityManager->persist($user);
                    $this->entityManager->flush();
                } catch (TransportExceptionInterface $e) {
                    $this->addFlash('warning', 'Error while sending mail: ' . $e->getMessage());
                }
            }
            $this->data['page']['title'] = 'Password reset request sent';
            $this->data['form'] = $this->createFormBuilder()->getForm()->createView();
            return $this->render('inadmin/page/admin/forgot-password-sent.html.twig', $this->data);
        }
        $this->data['form'] = $form->createView();

        return $this->render('inadmin/page/admin/forgot-password.html.twig', $this->data);
    }

    /**
     * @param Request $request
     * @param PasswordResetTokenService $tokenService
     * @param UserPasswordHasherInterface $hasher
     * @param RateLimiterFactoryInterface $forgotPasswordIpLimiter
     * @param string $token
     * @return Response
     * @throws NonUniqueResultException
     */
    #[Route("/incc/new-password/{token}", name: "incc_account_new-password", methods: [ "GET", "POST" ])]
    public function newPassword(
        Request $request,
        PasswordResetTokenService $tokenService,
        UserPasswordHasherInterface $hasher,
        RateLimiterFactoryInterface $forgotPasswordIpLimiter,
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

        $form = $this->createForm(ChangePasswordType::class, [
            'change_password' => $request->request->all('change_password', [
                'username' => '',
            ]),
        ], [
            'password_reset' => true,
        ]);
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
                return new Response('Too many password reset attempts from this IP. Try again later.', 429, $headers);
            };
            $user = $this->entityManager->getRepository(User::class)->findOneBy(
                [ 'username' => $form->getData()['change_password']['username'] ]
            );
            if (!$user) {
                $this->addFlash('error', 'Invalid token.');
                return $this->redirectToRoute('incc_account_forgot-password');
            }
            $resetRequest = $tokenService->validateTokenForUser($token, $user);
            if (!$resetRequest) {
                $this->addFlash('error', 'Invalid or expired reset token.');
                return $this->redirectToRoute('incc_account_forgot-password');
            }
            $plainPassword = $form->getData()['change_password']['new_password'];
            $hashed = $hasher->hashPassword($user, $plainPassword);
            $user->setPassword($hashed);
            $user->setPasswordModDate(new DateTime('now'));
            $tokenService->markAsUsed($resetRequest);
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->addFlash('success', 'Your password has been reset. You can now log in.');
            return $this->redirectToRoute('app_account_login');
        }
        $this->data['form'] = $form->createView();
        $this->data['token'] = $token;

        return $this->render('inadmin/page/admin/new-password.html.twig', $this->data);
    }
}
