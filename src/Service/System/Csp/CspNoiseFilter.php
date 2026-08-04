<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\System\Csp;

use Inachis\Model\System\CspReportDto;

/**
 * Used to filter out noise from CSP report submissions.
 */
final class CspNoiseFilter
{
    /**
     * Determines if the request is likely noise, and returns true|false.
     */
    public function isNoise(CspReportDto $dto): bool
    {
        $uri = $dto->blockedUri;
        if (!$uri) {
            return false;
        }

        return str_starts_with($uri, 'chrome-extension://')
            || str_starts_with($uri, 'moz-extension://')
            || str_starts_with($uri, 'safari-extension://')
            || str_starts_with($uri, 'data:')
            || str_starts_with($uri, 'blob:');
    }
}
