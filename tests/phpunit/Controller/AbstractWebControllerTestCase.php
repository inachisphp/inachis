<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Controller;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use Inachis\Factory\PageViewFactory;
use Inachis\Model\System\PageMetadata;
use Inachis\Model\System\PageView;
use Inachis\Model\System\SiteSettings;

abstract class AbstractWebControllerTestCase extends TestCase
{
    protected ParameterBagInterface $params;
    protected EntityManagerInterface $entityManager;
    protected Security $security;
    protected TranslatorInterface $translator;
    protected PageViewFactory $pageViewFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->params->expects($this->any())
            ->method('has')
            ->willReturn(false);

        $this->entityManager = $this->createStub(EntityManagerInterface::class);
        $this->security = $this->createStub(Security::class);
        $this->translator = $this->createStub(TranslatorInterface::class);

        $siteSettings = new SiteSettings('Wandering the World', 'http://localhost', [], 'en', 'ltr', '', false, 'UTC');
        $pageMetadata = new PageMetadata();
        $pageView = new PageView($siteSettings, $pageMetadata);

        $this->pageViewFactory = $this->createMock(PageViewFactory::class);
        $this->pageViewFactory->method('create')->willReturn($pageView);
        $this->pageViewFactory->method('createAdmin')->willReturn($pageView);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     * @return T
     */
    protected function createController(string $class): object
    {
        return new $class(
            $this->entityManager,
            $this->pageViewFactory,
            $this->params,
            $this->security,
            $this->translator,
        );
    }
}
