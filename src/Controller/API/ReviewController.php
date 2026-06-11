<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller\API;

use Inachis\Entity\Content\{Page, ReviewThread};
use Inachis\Service\Page\ReviewNormaliser;
use Inachis\Service\Page\ReviewPageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Review controller
 */
#[IsGranted('ROLE_ADMIN')]
class ReviewController extends AbstractController
{
    public function __construct(
        private readonly ReviewPageService $reviewService,
        private readonly ReviewNormaliser $normaliser
    ) {}

	/**
	 * Returns a JSON list of review threads for a page, including all comments and author information.
	 * The threads are ordered by last updated date, with the most recently updated threads first.
	 * Only open threads are returned by this endpoint.
	 * The response format is as follows:
	 *
	 * @param Page $page The page for which to list review threads
	 * @return JsonResponse A JSON response containing an array of review threads, each with its comments and author information
	 */
    #[Route('/incc/api/review/page/{id}', methods: ['GET'])]
    public function list(Page $page): JsonResponse
    {
        $threads = $this->reviewService->getThreadsForPage($page);

        return $this->json(
            array_map(
                fn ($thread) => $this->normaliser->normaliseThread($thread),
                $threads
            )
        );
    }

	/**
	 * Creates a new review thread for a page with an initial comment, and returns the created thread as JSON.
	 *
	 * @param Request $request
	 * @param Page $page
	 * @return JsonResponse
	 */
    #[Route('/incc/api/review/page/{id}', methods: ['POST'])]
    public function create(
        Request $request,
        Page $page
    ): JsonResponse {
        $payload = json_decode(
            $request->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $thread = $this->reviewService->createThread(
            page: $page,
            author: $this->getUser(),
            message: $payload['message'],
            startOffset: $payload['startOffset'],
            endOffset: $payload['endOffset'],
            selectedText: $payload['selectedText'],
            contextBefore: $payload['contextBefore'] ?? '',
            contextAfter: $payload['contextAfter'] ?? ''
        );

        return $this->json(
            $this->normaliser->normaliseThread($thread)
        );
    }

	/**
	 * Adds a reply to a thread, and returns the UUID of the added comment
	 *
	 * @param Request $request
	 * @param ReviewThread $thread
	 * @return JsonResponse
	 */
    #[Route('/incc/api/review/thread/{id}/reply', methods: ['POST'])]
    public function reply(
        Request $request,
        ReviewThread $thread
    ): JsonResponse {
		/** @var array{message: string} $payload */
        $payload = json_decode(
            $request->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $comment = $this->reviewService->addReply(
            $thread,
            $this->getUser(),
            $payload['message']
        );

        return $this->json([
            'id' => (string)$comment->getId()
        ]);
    }

	/**
	 * Resolves the specified review thread and returns success
	 *
	 * @param ReviewThread $thread
	 * @return JsonResponse
	 */
    #[Route('/incc/api/review/thread/{id}/resolve', methods: ['POST'])]
    public function resolve(
        ReviewThread $thread
    ): JsonResponse {
        $this->reviewService->resolveThread(
            $thread,
            $this->getUser()
        );

        return $this->json([
            'success' => true
        ]);
    }

	#[Route('/incc/api/review/thread/{id}/reopen', methods: ['POST'])]
	public function reopen(ReviewThread $thread): JsonResponse
	{
		$this->reviewService->reopenThread($thread);

		return $this->json([
			'success' => true
		]);
	}
}
