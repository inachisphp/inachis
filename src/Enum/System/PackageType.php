<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */
namespace Inachis\Enum\System;

enum PackageType: string
{
    case Core = 'core';
    case Plugin = 'plugin';
    case Theme = 'theme';
}
