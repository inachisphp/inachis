<?php
/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Build;

enum ReleaseEntryType: string
{
    case File = 'file';
    case Directory = 'directory';
}
