<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Discovery\Checker;

use Inachis\Model\System\DiscoveryStatus;
use Inachis\Repository\System\SettingRepository;

/**
 * Check status of llms.txt
 */
class LlmsTxtChecker implements DiscoveryCheckerInterface
{
    /**
     * Constructor
     *
     * @param SettingRepository $settingRepository
     */
    public function __construct(
        private readonly SettingRepository $settingRepository,
    ) {}

    /**
     * Check the status of llms.txt
     *
     * @return DiscoveryStatus
     */
    public function check(): DiscoveryStatus
    {
        $content = trim(
            $this->settingRepository->getValue('llms_txt') ?? ''
        );
        $messages = [];
        $status = 'success';

        if ($content === '') {
            $status = 'warning';
            $messages[] = 'No llms.txt content has been configured.';
        }

        if ($content !== ''
            && !preg_match('/^#\s+.+$/m', $content)
        ) {
            $messages[] =
                'llms.txt does not contain a Markdown heading.';
        }

        return new DiscoveryStatus(
            'llms.txt',
            'Provides guidance for AI systems.',
            $status,
            '/llms.txt',
            $messages,
            'documents'
        );
    }
}