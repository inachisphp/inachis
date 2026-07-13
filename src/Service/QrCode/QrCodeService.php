<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\QrCode;

use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;

class QrCodeService
{
    /**
     * Generate a QR code image data URI.
     *
     * @param string $data
     * @return string
     */
    public function generate(
        string $data
    ): string {
        return (new Builder(
            writer: new PngWriter(),
            data: $data,
            size: 200,
            margin: 10
        ))
            ->build()
            ->getDataUri();
    }
}
