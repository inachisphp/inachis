<?php

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
        $this->assertContains([ '(GMT+00:00) UTC', 'UTC' ], $timezones);
    }
}
