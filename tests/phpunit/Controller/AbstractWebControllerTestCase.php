<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Controller;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractWebControllerTestCase extends TestCase
{
    protected ParameterBagInterface $params;
    protected EntityManagerInterface $entityManager;
    protected Security $security;
    protected TranslatorInterface $translator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->params->expects($this->atLeastOnce())
            ->method('has')
            ->willReturn(false);

        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->security = $this->createStub(Security::class);
        $this->translator = $this->createStub(TranslatorInterface::class);
    }
}
