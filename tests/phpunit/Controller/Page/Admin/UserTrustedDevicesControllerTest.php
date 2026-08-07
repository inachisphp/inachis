<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Controller\Page\Admin\UserTrustedDevicesController;
use Inachis\Factory\PageViewFactory;
use Inachis\Repository\Waste\WasteRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class UserTrustedDevicesControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $params = $this->createMock(ParameterBagInterface::class);
        $security = $this->createStub(Security::class);
        $translator = $this->createStub(TranslatorInterface::class);
        $wasteRepository = $this->createMock(WasteRepository::class);
        $pageViewFactory = $this->createMock(PageViewFactory::class);
        $requestStack = new \Symfony\Component\HttpFoundation\RequestStack();

        $instance = new UserTrustedDevicesController(
            $entityManager,
            $params,
            $security,
            $translator,
            $wasteRepository,
            $pageViewFactory,
            $requestStack,
        );

        self::assertInstanceOf(
            UserTrustedDevicesController::class,
            $instance,
        );
    }
}
