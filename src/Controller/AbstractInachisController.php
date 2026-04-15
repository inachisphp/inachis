<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller;

use Inachis\Entity\User;
use Inachis\Controller\AbstractController;
use Inachis\Repository\WasteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use DateTimeImmutable;
use DateInterval;

/**
 * Abstract controller for Inachis.
 */
abstract class AbstractInachisController extends AbstractController
{
    /**
     * @var array<string>
     */
    protected array $errors = [];

    /**
     * @var array<string, mixed>
     */
    protected array $data = [];

    /**
     * @param EntityManagerInterface $entityManager
     * @param Security $security
     * @param TranslatorInterface $translator
     */
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected ParameterBagInterface $params,
        protected Security $security,
        protected TranslatorInterface $translator,
        protected WasteRepository $wasteRepository,
    ) {}

    /**
     * Sets the default data for the controller.
     * 
     * @return void
     */
    public function setDefaults(): void
    {
        $sessionTimeout = new DateTimeImmutable();
        $sessionTimeout = $sessionTimeout->add(new DateInterval('PT' . ini_get('session.gc_maxlifetime') . 'S'));

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
            'notifications' => [],
            'page'          => [
                'self'          => '',
                'tab'           => '',
                'title'         => '',
                'description'   => '',
                'keywords'      => '',
                'modDate'       => '',
            ],
            'session' => $this->security->getUser(),
            'session_timeout' => ini_get('session.gc_maxlifetime'),
            'session_timeout_time' => $sessionTimeout->format('Y-m-d\TH:i:s'),
            'deleted_items' => $this->wasteRepository->getWasteCount(),
        ];
        $this->data['timeout_template'] = base64_encode($this->renderView('inadmin/dialog/session_timeout.html.twig'));
    }
}
