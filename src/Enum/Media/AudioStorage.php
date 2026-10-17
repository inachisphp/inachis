<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis\Enum\Media;

/**
 * Defines where an audio resource is stored.
 */
enum AudioStorage: string
{
    /**
     * Audio is stored locally by Inachis.
     */
    case LOCAL = 'local';

    /**
     * Audio is hosted at an external location.
     */
    case EXTERNAL = 'external';
}
