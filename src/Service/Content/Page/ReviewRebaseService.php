<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Content\Page;

use Inachis\Entity\Content\ReviewThread;

class ReviewRebaseService
{
    /**
     * Recalculate offsets for selected text after edits.
     */
    public function rebase(ReviewThread $thread, string $newContent): bool
    {
        $selectedText = $thread->getSelectedText();
        $contextBefore = $thread->getContextBefore();
        $contextAfter = $thread->getContextAfter();

        $matches = [
            // Best match
            [
                'needle' => $contextBefore.
                    $selectedText.
                    $contextAfter,

                'offset' => strlen($contextBefore),
            ],

            // Selected text + before
            [
                'needle' => $contextBefore.
                    $selectedText,

                'offset' => strlen($contextBefore),
            ],

            // Selected text + after
            [
                'needle' => $selectedText.
                    $contextAfter,

                'offset' => 0,
            ],

            // Last resort
            [
                'needle' => $selectedText,

                'offset' => 0,
            ],
        ];

        foreach ($matches as $match) {
            $position = strpos($newContent, $match['needle']);
            if (false === $position) {
                continue;
            }

            $currentStart = $position + $match['offset'];
            $currentEnd = $currentStart + strlen($selectedText);

            $thread->setCurrentStartOffset($currentStart);
            $thread->setCurrentEndOffset($currentEnd);
            $thread->setNeedsRebase(false);

            return true;
        }

        $thread->setNeedsRebase(true);

        return false;
    }
}
