<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Content;

use Inachis\Model\ContentQueryParameters;
use Inachis\Model\Page\ViewStateDefaults;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

interface ViewStateManagerInterface
{
    public function load(
        Request $request,
        string $context,
        ViewStateDefaults $defaults,
    ): ContentQueryParameters;

    public function save(
        SessionInterface $session,
        string $context,
        ContentQueryParameters $parameters,
    ): void;
}
