<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\Page\Admin;

use DateTimeImmutable;
use Exception;
use Inachis\Controller\AbstractInachisController;
use Inachis\Form\ChangePasswordType;
use Inachis\Repository\User\UserRepository;
use Inachis\Security\Authentication\TrustedDeviceManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller used for changing password for an administrator {@link User}
 */
class ChangePasswordController extends AbstractInachisController
{
    /**
     * Controller for the change-password tab in the admin interface
     *
     * @param Request $request
     * @param TrustedDeviceManager $trustedDeviceManager
     * @param UserPasswordHasherInterface $passwordHasher
     * @param UserRepository $userRepository
     * @return Response
     */
    #[Route("/incc/admin/{id}/change-password", name: "incc_admin_change_password", methods: [ "GET", "POST" ])]
    public function changePasswordTab(
        Request $request,
        TrustedDeviceManager $trustedDeviceManager,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
    ): Response {
        /** @var \Inachis\Entity\User\User */
        $currentUser = $this->security->getUser();
        /** @var \Inachis\Entity\User\User|null */
        $user = $userRepository->findOneBy(['username' => $request->attributes->getString('id')]);
        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $form = $this->createForm(
            ChangePasswordType::class,
            null,
            [
                'last_modified' => $currentUser->getPasswordChangedAt()?->format('d F Y'),
            ]
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $user->getId() === $currentUser->getId()) {
            /** @var string */
            $plaintextPassword = $request->request->all('change_password')['new_password'];
            if (str_contains(strtolower($plaintextPassword), strtolower($user->getUsername() ?: ''))) {
                throw new Exception('Your password cannot contain username.');
            }
            $hashedPassword = $passwordHasher->hashPassword($user, $plaintextPassword);
            $user->setPassword($hashedPassword);
            $user->setPasswordChangedAt(new DateTimeImmutable());
            if (!$passwordHasher->isPasswordValid($user, $plaintextPassword)) {
                throw new AccessDeniedHttpException();
            }
            $trustedDeviceManager->removeAll($user);
            $this->entityManager->flush();
            $this->addFlash('success', 'Password updated.');
        }

        $this->viewModel->page->title = 'Change Password';
        $this->viewModel->page->tab = 'change-password';
        return $this->render('inadmin/page/admin/change-password.html.twig', [
            'viewModel' => $this->viewModel,
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}
