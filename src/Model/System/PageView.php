<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Model\System;

use Inachis\Entity\User\User;
use Inachis\Model\System\PageMetadata;
use Inachis\Model\System\SiteSettings;

final class PageView
{
    /**
     * Model used for page view
     *
     * @param SiteSettings $settings
     * @param PageMetadata $page
     * @param list<string> $notifications
     * @param mixed $session
     * @param int $sessionTimeout
     * @param string $sessionTimeoutTime
     * @param int $deletedItems
     * @param string $timeoutTemplate
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
    ) {}
}
