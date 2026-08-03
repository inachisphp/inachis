<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\API\Review;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\User\User;
use Inachis\Repository\Content\ReviewThreadRepository;
use Inachis\Repository\User\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Review assign controller
 */
class ReviewAssignController extends AbstractController
{
	/**
	 *  Assigns the thread to a User
	 *
	 * @param string $id
	 * @param Request $request
	 * @param ReviewThreadRepository $threads
	 * @param UserRepository $users
	 * @param EntityManagerInterface $entityManager
	 * @return JsonResponse
	 */
	#[Route('/incp/api/review/thread/{id}/assign', methods: ['POST'])]
	public function assign(
		string $id,
		Request $request,
		ReviewThreadRepository $threads,
		UserRepository $users,
		EntityManagerInterface $entityManager,
	): JsonResponse {

		$thread = $threads->find($id);
		if (!$thread) {
			throw $this->createNotFoundException();
		}

		/** @var array{userId: string, ...} */
		$payload = json_decode($request->getContent(), true);

		$user = $users->find($payload['userId']);
		if (!$user) {
			throw $this->createNotFoundException();
		}

		$thread->setAssignedTo($user);

		$entityManager->flush();

		return $this->json([ 'success' => true ]);
	}

	/**
	 * Returns a list of available reviewers
	 *
	 * @param UserRepository $users
	 * @return JsonResponse
	 */
	#[Route('/incp/api/review/reviewers', methods: ['GET'])]
	public function reviewers(
		UserRepository $users
	): JsonResponse {

		// TODO: change this to only show active users with the correct permissions
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
