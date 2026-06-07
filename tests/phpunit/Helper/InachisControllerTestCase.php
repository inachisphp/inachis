<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Repository\Waste\WasteRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class InachisControllerTestCase extends TestCase
{
    protected EntityManagerInterface $entityManager;
    protected ParameterBagInterface $params;
    protected Security $security;
    protected TranslatorInterface $translator;
    protected WasteRepository $wasteRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->security = $this->createStub(Security::class);
        $this->translator = $this->createStub(TranslatorInterface::class);
        $this->wasteRepository = $this->createMock(WasteRepository::class);
    }
}
