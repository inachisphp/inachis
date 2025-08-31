<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ForgotPasswordType;
use App\Form\LoginType;
use Karser\Recaptcha3Bundle\Validator\Constraints\Recaptcha3Validator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\Translation\TranslatorInterface;

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

        return $this->render('inadmin/signin.html.twig', $this->data);
    }

    /**
     * @throws \Exception
     */
    #[Route("/incc/logout", name: "app_logout", methods: [ "GET", "POST" ])]
    public function logout(): never
    {
        throw new \Exception('Don\'t forget to activate logout in security.yaml');
    }

    /**
     * @param Request $request
     * @param TranslatorInterface $translator
     * @param Recaptcha3Validator $recaptcha3Validator
     * @return Response
     * @throws TransportExceptionInterface
     */
    #[Route("/incc/forgot-password", methods: [ "GET", "POST" ])]
    public function forgotPassword(
        Request $request,
        TranslatorInterface $translator,
        Recaptcha3Validator $recaptcha3Validator,
        MailerInterface $mailer,
    ): Response
    {
        $redirectTo = $this->redirectIfAuthenticatedOrNoAdmins();
        if (!empty($redirectTo)) {
            return $this->redirectToRoute($redirectTo);
        }
        $this->data['page']['title'] = 'Request a password reset';
        $form = $this->createForm(ForgotPasswordType::class, [
            'forgot_email' => $request->get('forgot_email'),
        ]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            //$score = $recaptcha3Validator->getLastResponse()->getScore();
            $user = $this->entityManager->getRepository(User::class)->findOneBy([
                'email' => $request->get('forgot_password')['forgot_email']
            ]);
            if (null !== $user) {
                $email = (new Email())
                    ->from('jedi58@gmail.com')
                    ->to($user->getEmail())
                    ->subject('Forgotten password')
                    ->text('Click this link to reset your password')
                    ->html('<p>Click this link to reset your password</p>');
                $mailer->send($email);
            }
            // Always send below even if user not found - for security
            $this->redirectToRoute('app_account_forgotpasswordsent');
            exit;
        }
        $this->data['form'] = $form->createView();

        return $this->render('inadmin/forgot-password.html.twig', $this->data);
    }

    #[Route("/incc/forgot-password-sent", methods: [ "POST" ])]
    public function forgotPasswordSent(): Response
    {
        $this->data['page']['title'] = 'Password reset request sent';
        return $this->render('inadmin/forgot-password-sent.html.twig', $this->data);
    }

    public function register(UserPasswordHasherInterface $passwordHasher): Response
    {
        // ... e.g. get the user data from a registration form
//        $user = new User(...);
//        $plaintextPassword = ...;
//
//        // hash the password (based on the security.yaml config for the $user class)
//        $hashedPassword = $passwordHasher->hashPassword(
//            $user,
//            $plaintextPassword
//        );
//        $user->setPassword($hashedPassword);

        // ...
    }
}
