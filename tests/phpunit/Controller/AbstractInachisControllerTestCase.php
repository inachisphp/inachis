<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Repository\Waste\WasteRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractInachisControllerTestCase extends TestCase
{
    protected EntityManagerInterface $entityManager;
    protected ParameterBagInterface $params;
    protected Security $security;
    protected TranslatorInterface $translator;
    protected WasteRepository $wasteRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->params = $this->createStub(ParameterBagInterface::class);
        $this->params->method('has')->willReturn(false);

        $this->entityManager = $this->createStub(EntityManagerInterface::class);

        $this->security = $this->createStub(Security::class);
        $this->security->method('getUser')->willReturn(null);

        $this->translator = $this->createStub(TranslatorInterface::class);

        $this->wasteRepository = $this->createStub(WasteRepository::class);
        $this->wasteRepository->method('getWasteCount')->willReturn(0);
    }
}