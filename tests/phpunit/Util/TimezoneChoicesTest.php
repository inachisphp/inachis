<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Util;

use App\Util\TimezoneChoices;
use DateInvalidTimeZoneException;
use PHPUnit\Framework\TestCase;

class TimezoneChoicesTest extends TestCase
{

    /**
     * @throws DateInvalidTimeZoneException
     */
    public function testGetTimezones()
    {
        $timezones = (new TimezoneChoices)->getTimezones();
        $this->assertIsArray($timezones);
        $this->assertContains('UTC', $timezones);
        $this->assertArrayHasKey('(GMT+00:00) UTC', $timezones);
    }
}
