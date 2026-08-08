<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Setting\Discovery;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Controller\Page\Setting\Discovery\SecurityTxtController;
use Inachis\Factory\PageViewFactory;
use Inachis\Model\System\PageMetadata;
use Inachis\Model\System\PageView;
use Inachis\Model\System\SiteSettings;
use Inachis\Repository\Waste\WasteRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SecurityTxtControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $params = $this->createMock(ParameterBagInterface::class);
        $pageViewFactory = $this->createMock(PageViewFactory::class);
        $pageViewFactory->method('create')->willReturn(new PageView(
            new SiteSettings('Title', 'http://localhost', [], 'en', 'ltr', '', false, 'UTC'),
            new PageMetadata(),
        ));
        $pageViewFactory->method('createAdmin')->willReturn(new PageView(
            new SiteSettings('Title', 'http://localhost', [], 'en', 'ltr', '', false, 'UTC'),
            new PageMetadata(),
        ));

        $instance = new SecurityTxtController(
            $this->createMock(EntityManagerInterface::class),
            $params,
            $this->createMock(Security::class),
            $this->createMock(TranslatorInterface::class),
            $this->createMock(WasteRepository::class),
            $pageViewFactory,
            new \Symfony\Component\HttpFoundation\RequestStack(),
        );

        self::assertInstanceOf(
            SecurityTxtController::class,
            $instance,
        );
    }
}
