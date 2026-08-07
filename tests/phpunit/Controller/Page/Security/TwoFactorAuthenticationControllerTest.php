<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Security;

use Inachis\Controller\Page\Security\TwoFactorAuthenticationController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Inachis\Repository\Waste\WasteRepository;
use Inachis\Factory\PageViewFactory;
use Symfony\Component\HttpFoundation\RequestStack;
use PHPUnit\Framework\TestCase;

final class TwoFactorAuthenticationControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $siteSettings = new \Inachis\Model\System\SiteSettings('Wandering the World', 'http://localhost', [], 'en', 'ltr', '', false, 'UTC');
        $pageMetadata = new \Inachis\Model\System\PageMetadata();
        $pageView = new \Inachis\Model\System\PageView($siteSettings, $pageMetadata);

        $pvf = $this->createStub(PageViewFactory::class);
        $pvf->method('create')->willReturn($pageView);
        $pvf->method('createAdmin')->willReturn($pageView);

        $instance = new TwoFactorAuthenticationController(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(ParameterBagInterface::class),
            $this->createStub(Security::class),
            $this->createStub(TranslatorInterface::class),
            $this->createMock(WasteRepository::class),
            $pvf,
            new RequestStack(),
        );

        self::assertInstanceOf(TwoFactorAuthenticationController::class, $instance);
    }
}
