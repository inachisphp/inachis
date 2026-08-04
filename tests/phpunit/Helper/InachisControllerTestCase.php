<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Factory\PageViewFactory;
use Inachis\Model\System\PageMetadata;
use Inachis\Model\System\PageView;
use Inachis\Model\System\SiteSettings;
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
    protected PageViewFactory $pageViewFactory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->security = $this->createStub(Security::class);
        $this->translator = $this->createStub(TranslatorInterface::class);
        $this->wasteRepository = $this->createMock(WasteRepository::class);

        $siteSettings = new SiteSettings('Wandering the World', 'http://localhost', [], 'en', 'ltr', '', false);
        $pageMetadata = new PageMetadata();
        $pageView = new PageView($siteSettings, $pageMetadata);

        $this->pageViewFactory = $this->createMock(PageViewFactory::class);
        $this->pageViewFactory->method('create')->willReturn($pageView);
        $this->pageViewFactory->method('createAdmin')->willReturn($pageView);
    }
}
