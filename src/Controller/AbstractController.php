<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
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
