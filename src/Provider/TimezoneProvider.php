<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Provider;

use Inachis\Entity\User\User;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class TimezoneProvider
{
    public function __construct(
        #[Autowire('%env(APP_DEFAULT_TIMEZONE)%')]
        private string $defaultTimezone,
    ) {}

    public function getDefault(): string
    {
        return $this->defaultTimezone;
    }

    public function getForUser(?User $user): string
    {
        return $user?->getPreferences()?->getTimezone()
            ?? $this->defaultTimezone;
    }
}
