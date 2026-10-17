<?php

/**
 * This file is part of the inachis framework.
 */

declare(strict_types=1);

namespace Inachis;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
