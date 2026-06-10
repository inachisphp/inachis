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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller used for changing password for an administrator {@link User}
 */
class ChangePasswordController extends AbstractInachisController
{
    /**
     * Controller for the change-password tab in the admin interface
     * @param Request $request
     * @param UserPasswordHasherInterface $passwordHasher
     * @param UserRepository $userRepository
     * @return Response
     */
    #[Route("/incc/admin/{id}/change-password", name: "incc_admin_change_password", methods: [ "GET", "POST" ])]
    #[IsGranted('ROLE_ADMIN')]
    public function changePasswordTab(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
    ): Response {
        /** @var \Inachis\Entity\User\User */
        $currentUser = $this->security->getUser();
        /** @var \Inachis\Entity\User\User|null */
        $user = $userRepository->findOneBy(['username' => $request->attributes->get('id')]);
        if (!$user) {
            throw new AccessDeniedHttpException();
        }

        $form = $this->createForm(
            ChangePasswordType::class,
            null,
            [
                'last_modified' => $currentUser->getPasswordModDate()?->format('d F Y'),
            ]
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && $user->getId() === $currentUser->getId()) {
            /** @var string */
            $plaintextPassword = $request->request->all('change_password')['new_password'];
            if (strtolower($user->getUsername() ?: '') === strtolower($plaintextPassword)) {
                throw new Exception('Your password cannot be the same as your username.');
            }
            $hashedPassword = $passwordHasher->hashPassword($user, $plaintextPassword);
            $user->setPassword($hashedPassword);
            $user->setPasswordModDate(new DateTimeImmutable());
            if (!$passwordHasher->isPasswordValid($user, $plaintextPassword)) {
                throw new AccessDeniedHttpException();
            }
            $this->addFlash('success', 'Password updated.');
            $this->entityManager->flush();
        }
        $this->data['user'] = $user;
        $this->data['form'] = $form->createView();
        $this->data['page']['title'] = 'Change Password';
        $this->data['page']['tab'] = 'users';
        return $this->render('inadmin/page/admin/change-password.html.twig', $this->data);
    }
}
