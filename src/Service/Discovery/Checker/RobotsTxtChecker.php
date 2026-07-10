<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Discovery\Checker;

use Inachis\Model\System\DiscoveryStatus;
use Inachis\Service\Discovery\Generator\RobotsTxtGenerator;

/**
 * Checks the status of robots.txt
 */
class RobotsTxtChecker implements DiscoveryCheckerInterface
{
    /**
     * Constructor
     *
     * @param RobotsTxtGenerator $generator
     */
    public function __construct(
        private readonly RobotsTxtGenerator $generator
    ) {}

    /**
     * Check the status of robots.txt
     *
     * @return DiscoveryStatus
     */
    public function check(): DiscoveryStatus
    {
        $content = $this->generator->generate();
        $messages = [];
        $status = 'success';

        if (preg_match(
            '/^Disallow:\s*\/\s*$/mi',
            $content
        )) {
            $status = 'warning';

            $messages[] = 'robots.txt blocks all crawlers.';
        }

        return new DiscoveryStatus(
            'robots.txt',
            'Controls crawler access rules.',
            $status,
            '/robots.txt',
            $messages
        );
    }
}