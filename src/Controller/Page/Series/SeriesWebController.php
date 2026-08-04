<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller\Page\Series;

use Inachis\Controller\AbstractWebController;
use Inachis\Repository\Content\SeriesRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SeriesWebController extends AbstractWebController
{
    #[Route('/{year}-{title}', name: 'web_series_view', methods: ['GET'], requirements: ['year' => "\d{4}"])]
    public function view(
        SeriesRepository $seriesRepository,
        int $year,
        string $title,
    ): Response {
        $series = $seriesRepository->getPublicSeriesByYearAndUrl(
            (string) $year,
            $title,
        );
        if (empty($series)) {
            throw $this->createNotFoundException('This page does not exist');
        }

        return $this->render('web/pages/series.html.twig', [
            'viewModel' => $this->viewModel,
            'series' => $series,
        ]);
    }
}
