<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller\Page\Search;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Controller\Page\Search\SearchWebController;
use Inachis\Factory\PageViewFactory;
use Inachis\Model\System\PageMetadata;
use Inachis\Model\System\PageView;
use Inachis\Model\System\SiteSettings;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class SearchWebControllerTest extends TestCase
{
    public function testCanBeInstantiated(): void
    {
        $siteSettings = new SiteSettings('Wandering the World', 'http://localhost', [], 'en', 'ltr', '', false, 'UTC');
        $pageView = new PageView($siteSettings, new PageMetadata());
        $pageViewFactory = $this->createStub(PageViewFactory::class);
        $pageViewFactory->method('create')->willReturn($pageView);

        $instance = new SearchWebController(
            $this->createMock(EntityManagerInterface::class),
            $pageViewFactory,
            $this->createMock(ParameterBagInterface::class),
            $this->createStub(Security::class),
            $this->createStub(TranslatorInterface::class),
        );

        self::assertInstanceOf(
            SearchWebController::class,
            $instance,
        );
    }
}
