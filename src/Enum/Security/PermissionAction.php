<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Enum\Security;

enum PermissionAction: string
{
    case VIEW = 'VIEW';
    case CREATE = 'CREATE';
    case EDIT = 'EDIT';
    case DELETE = 'DELETE';
    case REVIEW = 'REVIEW';
    case PUBLISH = 'PUBLISH';
}
