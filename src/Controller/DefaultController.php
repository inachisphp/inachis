<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Controller;

use Inachis\Service\Page\ContentAggregator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractInachisController
{
    #[Route("/", methods: [ "GET" ])]
    public function homepage(ContentAggregator $contentProvider): Response
    {
        $this->data['content'] = $contentProvider->getHomepageContent();
        return $this->render('web/pages/homepage.html.twig', $this->data);
    }
}
