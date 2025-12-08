<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Controller\Page\Series;

use App\Controller\AbstractInachisController;
use App\Repository\SeriesRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SeriesWebController extends AbstractInachisController
{
    /**
     * @param Request $request
     * @param SeriesRepository $seriesRepository
     * @param int $year
     * @param string $title
     * @return Response
     */
    #[Route("/{year}-{title}", name: "app_series_view", methods: [ "GET" ])]
    public function view(
        Request $request,
        SeriesRepository $seriesRepository,
        int $year,
        string $title
    ): Response {
        $this->data['series'] = $seriesRepository->getPublicSeriesByYearAndUrl(
            $year,
            $title
        );
        if (empty($this->data['series'])) {
            throw $this->createNotFoundException('This page does not exist');
        }
        return $this->render('web/pages/series.html.twig', $this->data);
    }

}
