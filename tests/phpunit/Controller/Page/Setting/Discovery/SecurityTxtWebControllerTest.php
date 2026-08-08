<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Setting\Discovery;

use Inachis\Controller\Page\Setting\Discovery\SecurityTxtWebController;
use Inachis\Factory\PageViewFactory;
use Inachis\Model\System\PageMetadata;
use Inachis\Model\System\PageView;
use Inachis\Model\System\SiteSettings;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class SecurityTxtWebControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $params = $this->createMock(ParameterBagInterface::class);
        $pageViewFactory = $this->createMock(PageViewFactory::class);
        $pageViewFactory->method('create')->willReturn(new PageView(
            new SiteSettings('Title', 'http://localhost', [], 'en', 'ltr', '', false, 'UTC'),
            new PageMetadata(),
        ));

        $instance = new SecurityTxtWebController($params, $pageViewFactory);

        self::assertInstanceOf(
            SecurityTxtWebController::class,
            $instance,
        );
    }
}
