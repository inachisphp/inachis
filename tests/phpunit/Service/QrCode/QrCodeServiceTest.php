<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Service\QrCode;

use Inachis\Service\QrCode\QrCodeService;
use PHPUnit\Framework\TestCase;

final class QrCodeServiceTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $instance = new QrCodeService();

        self::assertInstanceOf(
            QrCodeService::class,
            $instance,
        );
    }
}
