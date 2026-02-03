<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class InachisBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
