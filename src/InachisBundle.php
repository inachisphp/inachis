<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class InachisBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
