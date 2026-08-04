<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Form\Provider;

use Inachis\Form\Provider\TimezoneChoices;
use PHPUnit\Framework\TestCase;

class TimezoneChoicesTest extends TestCase
{
    /**
     * @throws \DateInvalidTimeZoneException
     */
    public function testGetTimezones()
    {
        $timezones = (new TimezoneChoices())->getTimezones();
        $this->assertIsArray($timezones);
        $this->assertContains('UTC', $timezones);
        $this->assertArrayHasKey('(GMT+00:00) UTC', $timezones);
    }
}
