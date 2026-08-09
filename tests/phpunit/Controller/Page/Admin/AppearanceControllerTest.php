<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Controller\Page\Admin\AppearanceController;
use Inachis\Factory\PageViewFactory;
use Inachis\Repository\Waste\WasteRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AppearanceControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $siteSettings = new \Inachis\Model\System\SiteSettings('Wandering the World', 'http://localhost', [], 'en', 'ltr', '', false, 'UTC');
        $pageMetadata = new \Inachis\Model\System\PageMetadata();
        $pageView = new \Inachis\Model\System\PageView($siteSettings, $pageMetadata);
        $pvf = $this->createStub(PageViewFactory::class);
        $pvf->method('create')->willReturn($pageView);
        $pvf->method('createAdmin')->willReturn($pageView);

        $instance = new AppearanceController(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(ParameterBagInterface::class),
            $this->createStub(Security::class),
            $this->createStub(TranslatorInterface::class),
            $this->createMock(WasteRepository::class),
            $pvf,
            new RequestStack(),
        );

        self::assertInstanceOf(
            AppearanceController::class,
            $instance,
        );
    }
}
