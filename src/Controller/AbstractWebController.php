<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller;

use Inachis\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Inachis\Factory\PageViewFactory;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract controller for Inachis.
 */
abstract class AbstractWebController extends AbstractController
{
    /**
     * @param EntityManagerInterface $entityManager
     * @param ParameterBagInterface $params
     * @param Security $security
     * @param TranslatorInterface $translator
     */
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected PageViewFactory $pageViewFactory,
        protected ParameterBagInterface $params,
        protected Security $security,
        protected TranslatorInterface $translator,
    ) {
        parent::__construct($params, $pageViewFactory);
    }
}
