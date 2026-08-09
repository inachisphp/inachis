<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Security;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Controller\Page\Security\SecurityIndexController;
use Inachis\Factory\PageViewFactory;
use Inachis\Model\System\PageMetadata;
use Inachis\Model\System\PageView;
use Inachis\Model\System\SiteSettings;
use Inachis\Repository\Waste\WasteRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SecurityIndexControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $siteSettings = new SiteSettings('Wandering the World', 'http://localhost', [], 'en', 'ltr', '', false, 'UTC');
        $pageView = new PageView($siteSettings, new PageMetadata());
        $pageViewFactory = $this->createStub(PageViewFactory::class);
        $pageViewFactory->method('create')->willReturn($pageView);
        $pageViewFactory->method('createAdmin')->willReturn($pageView);

        $instance = new SecurityIndexController(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(ParameterBagInterface::class),
            $this->createStub(Security::class),
            $this->createStub(TranslatorInterface::class),
            $this->createMock(WasteRepository::class),
            $pageViewFactory,
            new RequestStack(),
        );

        self::assertInstanceOf(
            SecurityIndexController::class,
            $instance,
        );
    }
}
