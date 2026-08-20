<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Content\Category;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Series;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

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

    public function getRecentTrips(int $maxDisplayCount = self::DEFAULT_MAX_DISPLAY_COUNT): Response
    {
        return $this->render('web/partials/recent_trips.html.twig', [
            'trips' => $this->getRecentSeries($maxDisplayCount),
        ]);
    }

    public function getRecentRunning(int $maxDisplayCount = self::DEFAULT_MAX_DISPLAY_COUNT): Response
    {
        return $this->render('web/partials/recent_running.html.twig', [
            'races' => $this->getPagesWithCategoryName('Running', $maxDisplayCount),
        ]);
    }

    public function getRecentArticles(int $maxDisplayCount = self::DEFAULT_MAX_DISPLAY_COUNT): Response
    {
        return $this->render('web/partials/recent_articles.html.twig', [
            'articles' => $this->getPagesWithCategoryName('Articles', $maxDisplayCount),
        ]);
    }

    /**
     * @return Page[]
     */
    private function getPagesWithCategoryName(string $categoryName, int $maxDisplayCount = 0): array
    {
        $category = $this->entityManager->getRepository(Category::class)->findOneBy([
            'title' => $categoryName,
        ]);
        if ($category instanceof Category) {
            return $this->entityManager->getRepository(Page::class)->getPagesWithCategory(
                $category,
                $maxDisplayCount,
            );
        }

        return [];
    }

    /**
     * @return Series[]
     */
    private function getRecentSeries(int $maxDisplayCount = 0): array
    {
        return $this->entityManager->getRepository(Series::class)->findBy(['visible' => 1], ['lastDate' => 'DESC'], $maxDisplayCount);
    }
}
