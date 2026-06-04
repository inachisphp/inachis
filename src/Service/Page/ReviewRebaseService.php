<?php

namespace Inachis\Service\Page;

use Inachis\Entity\Content\ReviewThread;

class ReviewRebaseService
{
    public function rebase(ReviewThread $thread, string $newContent): bool
	{
		$position = strpos($newContent, $thread->getSelectedText());

		if ($position === false) {
			$thread->setNeedsRebase(true);
			return false;
		}

		$thread->setCurrentStartOffset($position);

		$thread->setCurrentEndOffset(
			$position +
			strlen($thread->getSelectedText())
		);

		$thread->setNeedsRebase(false);

		return true;
	}
}
