<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Page;
use App\Entity\Series;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class WidgetController extends AbstractController
{
    /*
     * @var int Default number of items to be shown by "widgets"
     */
    public const DEFAULT_MAX_DISPLAY_COUNT = 10;

    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param int $maxDisplayCount
     * @return Response
     */
    public function getRecentTrips(int $maxDisplayCount = self::DEFAULT_MAX_DISPLAY_COUNT): Response
    {
        return $this->render('web/partials/recent_trips.html.twig', [
            'trips' => $this->getRecentSeries($maxDisplayCount),
        ]);
    }

    /**
     * @param int $maxDisplayCount
     * @return Response
     */
    public function getRecentRunning(int $maxDisplayCount = self::DEFAULT_MAX_DISPLAY_COUNT): Response
    {
        return $this->render('web/partials/recent_running.html.twig', [
            'races' => $this->getPagesWithCategoryName('Running', $maxDisplayCount),
        ]);
    }

    /**
     * @param int $maxDisplayCount
     * @return Response
     */
    public function getRecentArticles(int $maxDisplayCount = self::DEFAULT_MAX_DISPLAY_COUNT): Response
    {
        return $this->render('web/partials/recent_articles.html.twig', [
            'articles' => $this->getPagesWithCategoryName('Articles', $maxDisplayCount),
        ]);
    }

    /**
     * @param $categoryName
     * @param int $maxDisplayCount
     * @return Page[]
     */
    private function getPagesWithCategoryName($categoryName, int $maxDisplayCount = 0): array
    {
        $category = $this->entityManager->getRepository(Category::class)->findOneByTitle($categoryName);
        if ($category instanceof Category) {
            return $this->entityManager->getRepository(Page::class)->getPagesWithCategory(
                $category,
                $maxDisplayCount
            );
        }
        return [];
    }

    /**
     * @param int $maxDisplayCount
     * @return Series[]
     */
    private function getRecentSeries(int $maxDisplayCount = 0): array
    {
        return $this->entityManager->getRepository(Series::class)->findBy([], ['lastDate' => 'DESC'], $maxDisplayCount);
    }
}
