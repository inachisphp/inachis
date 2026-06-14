<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Content\Page;

use Inachis\Entity\Content\ReviewThread;

class ReviewNormaliser
{
    /**
     * Normalises the thread
     *
     * @param ReviewThread $thread
     * @return array{
     *     id: string,
     *     resolved: bool,
     *     startOffset: int,
     *     endOffset: int,
     *     currentStartOffset: int|null,
     *     currentEndOffset: int|null,
     *     needsRebase: bool,
     *     selectedText: string,
     *     assignedTo: array{id: string, name: string|null}|null,
     *     comments: list<array{
     *         id: string,
     *         message: string,
     *         created: string|false,
     *         author: array{id: string, name: string|null}|null
     *     }>|array{}
     * }
     */
    public function normaliseThread(ReviewThread $thread): array
    {
        $comments = [];

        foreach ($thread->getComments() as $comment) {
            $comments[] = [
                'id' => (string)$comment->getId(),
                'message' => $comment->getMessage(),
                'created' => $comment->getCreated()->format(DATE_ATOM),
                'author' => [
                    'id' => (string)$comment->getAuthor()->getId(),
                    'name' => $comment->getAuthor()->getDisplayName(),
                ],
            ];
        }

		return [
			'id' => (string) $thread->getId(),
			'resolved' => $thread->isResolved(),
			'startOffset' => $thread->getStartOffset(),
			'endOffset' => $thread->getEndOffset(),
			'currentStartOffset' => $thread->getCurrentStartOffset(),
			'currentEndOffset' => $thread->getCurrentEndOffset(),
			'needsRebase' => $thread->isNeedsRebase(),
			'selectedText' => $thread->getSelectedText(),
			'assignedTo' => $thread->getAssignedTo()
				? [
					'id' =>
						(string) $thread
							->getAssignedTo()
							->getId(),

					'name' =>
						$thread
							->getAssignedTo()
							->getDisplayName()
				]
				: null,
            'comments' => $comments,
		];
    }
}
