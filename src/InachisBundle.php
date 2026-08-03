<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
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
