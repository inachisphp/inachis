<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller;

use Inachis\Factory\PageViewFactory;
use Inachis\Model\System\PageView;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as SymfonyController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 *
 */
abstract class AbstractController extends SymfonyController
{
    protected PageView $viewModel;

    public function __construct(
        protected ParameterBagInterface $params,
        PageViewFactory $pageViewFactory,
    ) {
        $this->viewModel = $pageViewFactory->create();
    }
}
