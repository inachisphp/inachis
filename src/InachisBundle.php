<?php

namespace Inachis;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class InachisBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
