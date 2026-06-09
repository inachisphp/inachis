<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\API;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\User\User;
use Inachis\Repository\Content\ReviewThreadRepository;
use Inachis\Repository\User\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Review assign controller
 */
#[IsGranted('ROLE_ADMIN')]
class ReviewAssignController extends AbstractController
{
	#[Route('/incc/api/review/thread/{id}/assign', methods: ['POST'])]
	public function assign(
		string $id,
		Request $request,
		ReviewThreadRepository $threads,
		UserRepository $users,
		EntityManagerInterface $entityManager
	): JsonResponse {

		$thread = $threads->find($id);
		if (!$thread) {
			throw $this->createNotFoundException();
		}

		$payload = json_decode($request->getContent(), true);

		$user = $users->find($payload['userId']);
		if (!$user) {
			throw $this->createNotFoundException();
		}

		$thread->setAssignedTo($user);

		$entityManager->flush();

		return $this->json([ 'success' => true ]);
	}

	#[Route('/incc/api/review/reviewers', methods: ['GET'])]
	public function reviewers(
		UserRepository $users
	): JsonResponse {

		// @todo change this to only show active users with the correct permissions
		$reviewers =
			$users->findBy(['isRemoved' => false, 'isActive' => true]);

		return $this->json(
			array_map(
				fn(User $user) => [
					'id' =>
						(string)$user->getId(),

					'name' =>
						$user->getDisplayName()
				],
				$reviewers
			)
		);
	}
}
