<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller;

use Inachis\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
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
        protected ParameterBagInterface $params,
        protected Security $security,
        protected TranslatorInterface $translator,
    ) {}

        /**
     * Sets the default data for the controller.
     * 
     * @return void
     */
    public function setDefaults(): void
    {
        $this->data = [
            'settings' => [
                'siteTitle' => $this->params->has('app.config.title')
                    ? $this->params->get('app.config.title')
                    : 'Untitled Site',
                'domain' => $this->getProtocolAndHostname(),
                'google' => [],
                'language' => $this->params->has('app.config.locale') 
                    ? $this->params->get('app.config.locale') 
                    : 'en',
                'textDirection' => $this->params->has('app.config.textDirection') 
                    ? $this->params->get('app.config.textDirection') 
                    : 'ltr',
                'abstract' => $this->params->has('app.config.abstract') 
                    ? $this->params->get('app.config.abstract') 
                    : '',
                'geotagContent' => $this->params->has('app.config.geotagContent') 
                    ? $this->params->get('app.config.geotagContent') 
                    : false,
            ],
            'page' => [
                'self'          => '',
                'tab'           => '',
                'title'         => '',
                'description'   => '',
                'keywords'      => '',
                'modDate'       => '',
            ],
        ];
    }
}
