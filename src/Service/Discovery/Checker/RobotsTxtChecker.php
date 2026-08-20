<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Discovery\Checker;

use Inachis\Model\System\DiscoveryStatus;
use Inachis\Service\Discovery\Generator\RobotsTxtGenerator;

/**
 * Checks the status of robots.txt.
 */
class RobotsTxtChecker implements DiscoveryCheckerInterface
{
    /**
     * Constructor.
     */
    public function __construct(
        private readonly RobotsTxtGenerator $generator,
    ) {
    }

    /**
     * Check the status of robots.txt.
     */
    public function check(): DiscoveryStatus
    {
        $content = $this->generator->generate();
        $messages = [];
        $status = 'success';

        if (preg_match(
            '/^Disallow:\s*\/\s*$/mi',
            $content,
        )) {
            $status = 'warning';

            $messages[] = 'robots.txt blocks all crawlers.';
        }

        return new DiscoveryStatus(
            'robots.txt',
            'Controls crawler access rules.',
            $status,
            '/robots.txt',
            $messages,
        );
    }
}
