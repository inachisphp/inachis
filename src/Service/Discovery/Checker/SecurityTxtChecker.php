<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Discovery\Checker;

use Inachis\Model\System\DiscoveryStatus;
use Inachis\Repository\System\SettingRepository;

/**
 * Checks the status of security.txt
 */
class SecurityTxtChecker implements DiscoveryCheckerInterface
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
     * Checks the status of security.txt
     *
     * @return DiscoveryStatus
     */
    public function check(): DiscoveryStatus
    {
        $content = trim(
            $this->settingRepository ->getValue('security_txt') ?? ''
        );
        $messages = [];
        $status = 'success';

        if ($content === '') {
            return new DiscoveryStatus(
                'security.txt',
                'Security contact information.',
                'warning',
                '/.well-known/security.txt',
                [
                    'security.txt has not been configured.'
                ],
                'documents'
            );
        }


        if (!preg_match('/^Contact:/mi', $content)) {
            $status = 'warning';
            $messages[] = 'No Contact field found.';
        }


        if (!preg_match('/^Expires:\s*(.+)$/mi', $content, $match)) {
            $status = 'warning';
            $messages[] = 'No Expires field found.';
        } else {
            $expires = \DateTimeImmutable::createFromFormat(
                'Y-m-d\TH:i:s\Z',
                trim($match[1])
            );

            if (!$expires) {
                $status = 'warning';
                $messages[] = 'Expires format is invalid.';
            } elseif ($expires < new \DateTimeImmutable()) {
                $status = 'warning';
                $messages[] = 'security.txt has expired.';
            }
        }

        return new DiscoveryStatus(
            'security.txt',
            'Security contact information.',
            $status,
            '/.well-known/security.txt',
            $messages,
            'documents'
        );
    }
}