<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller;

use DateTimeImmutable;
use DateInterval;
use Inachis\Entity\User;
use Inachis\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract controller for Inachis.
 */
abstract class AbstractWebController extends AbstractController
{
    
}