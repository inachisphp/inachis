<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Controller\Admin;

use App\Controller\AbstractInachisController;
use App\Entity\User;
use App\Form\ChangePasswordType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Constraints\PasswordStrength;
use Symfony\Component\Validator\Constraints\PasswordStrengthValidator;
use DateTime;

class ChangePasswordController extends AbstractInachisController
{
    /**
     * Controller for the change-password tab in the admin interface
     * @param Request $request
     * @param string $id
     * @return Response
     */
    #[Route("/incc/admin/{id}/change-password", methods: [ "GET", "POST" ])]
    public function changePasswordTab(UserPasswordHasherInterface $passwordHasher, Request $request, string $id): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $request->get('id')]);
        $form = $this->createForm(
            ChangePasswordType::class,
            null,
            [
                'last_modified' => $this->security->getUser()->getPasswordModDate()->format('d F Y'),
            ]
        );
        $form->handleRequest($request);
        if ($form->isSubmitted() && $user->getId() === $this->security->getUser()->getId() && $form->isValid()) {
            $plaintextPassword = $request->get('change_password')['new_password'];
            $hashedPassword = $passwordHasher->hashPassword($user, $plaintextPassword);
            $user->setPassword($hashedPassword);
            $user->setPasswordModDate(new DateTime('now'));
            if (!$passwordHasher->isPasswordValid($user, $plaintextPassword)) {
                throw new AccessDeniedHttpException();
            }
            $this->addFlash('success', 'Password updated.');
            $this->entityManager->flush();
        }
        $this->data['user'] = $user;
        $this->data['form'] = $form->createView();
        return $this->render('inadmin/admin/change-password.html.twig', $this->data);
    }

    /**
     * Returns a JSON object containing the result of calculating the password strength entropy
     * @param Request $request
     * @return JsonResponse
     */
    #[Route("/incc/ax/calculate-password-strength", methods: [ "POST" ])]
    public function calculatePasswordStrength(Request $request): JsonResponse
    {
        return new JsonResponse(PasswordStrengthValidator::estimateStrength($request->get('password')));
    }
}
