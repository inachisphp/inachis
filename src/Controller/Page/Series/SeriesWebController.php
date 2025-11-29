<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Controller\Page\Series;

use App\Controller\AbstractInachisController;
use App\Entity\Series;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SeriesWebController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @param int $year
     * @param string $title
     * @return Response
     */
    #[Route("/{year}-{title}", name: "app_series_view", methods: [ "GET" ])]
    public function view(Request $request, int $year, string $title): Response
    {
        $this->data['series'] = $this->entityManager->getRepository(Series::class)->getSeriesByYearAndUrl(
            $year,
            $title
        );
        if (empty($this->data['series'])) {
            throw $this->createNotFoundException('This page does not exist');
        }
        return $this->render('web/pages/series.html.twig', $this->data);
    }

}
