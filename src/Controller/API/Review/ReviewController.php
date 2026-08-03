<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\API\Review;

use Inachis\Controller\AbstractInachisController;
use Inachis\Entity\Content\{Page, ReviewThread};
use Inachis\Service\Content\Page\ReviewNormaliser;
use Inachis\Service\Content\Page\ReviewPageService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Review controller
 */
class ReviewController extends AbstractInachisController
{
	/**
	 * Returns a JSON list of review threads for a page, including all comments and author information.
	 * The threads are ordered by last updated date, with the most recently updated threads first.
	 * Only open threads are returned by this endpoint.
	 * The response format is as follows:
	 *
	 * @param Page $page The page for which to list review threads
     * @param ReviewPageService $reviewService
     * @param ReviewNormaliser $normaliser
	 * @return JsonResponse A JSON response containing an array of review threads, each with its comments and author information
	 */
    #[Route('/incp/api/review/page/{id}', methods: ['GET'])]
    public function list(
        Page $page,
        ReviewPageService $reviewService,
        ReviewNormaliser $normaliser,
    ): JsonResponse {
        $threads = $reviewService->getThreadsForPage($page);

        return $this->json(
            array_map(
                fn ($thread) => $normaliser->normaliseThread($thread),
                $threads
            )
        );
    }

	/**
	 * Creates a new review thread for a page with an initial comment, and returns the created thread as JSON.
	 *
	 * @param Request $request
	 * @param Page $page
     * @param ReviewPageService $reviewService
     * @param ReviewNormaliser $normaliser
	 * @return JsonResponse
	 */
    #[Route('/incp/api/review/page/{id}', methods: ['POST'])]
    public function create(
        Request $request,
        Page $page,
        ReviewPageService $reviewService,
        ReviewNormaliser $normaliser,
    ): JsonResponse {
        /** @var array{
         *     message: string,
         *     startOffset: int,
         *     endOffset: int,
         *     selectedText: string,
         *     contextBefore?: string,
         *     contextAfter?: string,...
         * }
         */
        $payload = json_decode(
            $request->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $thread = $reviewService->createThread(
            page: $page,
            author: $this->getCurrentUser(),
            message: $payload['message'],
            startOffset: $payload['startOffset'],
            endOffset: $payload['endOffset'],
            selectedText: $payload['selectedText'],
            contextBefore: $payload['contextBefore'] ?? '',
            contextAfter: $payload['contextAfter'] ?? ''
        );

        return $this->json(
            $normaliser->normaliseThread($thread)
        );
    }

	/**
	 * Adds a reply to a thread, and returns the UUID of the added comment
	 *
	 * @param Request $request
     * @param ReviewPageService $reviewService
	 * @param ReviewThread $thread
	 * @return JsonResponse
	 */
    #[Route('/incp/api/review/thread/{id}/reply', methods: ['POST'])]
    public function reply(
        Request $request,
        ReviewPageService $reviewService,
        ReviewThread $thread,
    ): JsonResponse {
		/** @var array{message: string} $payload */
        $payload = json_decode(
            $request->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $comment = $reviewService->addReply(
            $thread,
            $this->getCurrentUser(),
            $payload['message']
        );

        return $this->json([
            'id' => (string)$comment->getId()
        ]);
    }

	/**
	 * Resolves the specified review thread and returns success
	 *
     * @param ReviewPageService $reviewService
	 * @param ReviewThread $thread
	 * @return JsonResponse
	 */
    #[Route('/incp/api/review/thread/{id}/resolve', methods: ['POST'])]
    public function resolve(
        ReviewPageService $reviewService,
        ReviewThread $thread,
    ): JsonResponse {
        $reviewService->resolveThread(
            $thread,
            $this->getCurrentUser()
        );

        return $this->json([
            'success' => true
        ]);
    }

    /**
     * Reopens the specified thread
     *
     * @param ReviewPageService $reviewService
     * @param ReviewThread $thread
     * @return JsonResponse
     */
	#[Route('/incp/api/review/thread/{id}/reopen', methods: ['POST'])]
	public function reopen(
        ReviewPageService $reviewService,
        ReviewThread $thread,
    ): JsonResponse {
		$reviewService->reopenThread($thread);

		return $this->json([
			'success' => true
		]);
	}
}
