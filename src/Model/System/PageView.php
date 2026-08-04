<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Model\System;

use Inachis\Entity\User\User;

final class PageView
{
    /**
     * Model used for page view.
     *
     * @param list<string> $notifications
     */
    public function __construct(
        public SiteSettings $settings,
        public PageMetadata $page,
        // TODO: change this to \Symfony\Component\Security\Core\User\UserInterface|User|null
        public array $notifications = [],
        public mixed $session = null,
        public int $sessionTimeout = 0,
        public string $sessionTimeoutTime = '',
        public int $deletedItems = 0,
        public string $timeoutTemplate = '',
        public bool $twoFactorPending = false,
    ) {
    }
}
