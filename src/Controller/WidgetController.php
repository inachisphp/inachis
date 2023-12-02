<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Page;
use App\Entity\Series;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class WidgetController extends AbstractController
{
    /*
     * @var int Default number of items to be shown by "widgets"
     */
    const DEFAULT_MAX_DISPLAY_COUNT = 10;

    protected $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param int $maxDisplayCount
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getRecentTrips($maxDisplayCount = self::DEFAULT_MAX_DISPLAY_COUNT)
    {
        return $this->render('web/partials/recent_trips.html.twig', [
            'trips' => $this->getRecentSeries($maxDisplayCount),
        ]);
    }

    /**
     * @param int $maxDisplayCount
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getRecentRunning($maxDisplayCount = self::DEFAULT_MAX_DISPLAY_COUNT)
    {
        return $this->render('web/partials/recent_running.html.twig', [
            'races' => $this->getPagesWithCategoryName('Running', $maxDisplayCount),
        ]);
    }

    /**
     * @param int $maxDisplayCount
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getRecentArticles($maxDisplayCount = self::DEFAULT_MAX_DISPLAY_COUNT)
    {
        return $this->render('web/partials/recent_articles.html.twig', [
            'articles' => $this->getPagesWithCategoryName('Articles', $maxDisplayCount),
        ]);
    }

    /**
     * @param $categoryName
     * @return Page[]
     */
    private function getPagesWithCategoryName($categoryName, int $maxDisplayCount = null)
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

    private function getRecentSeries(int $maxDisplayCount = null)
    {
        return $this->entityManager->getRepository(Series::class)->findBy([], ['lastDate' => 'DESC'], $maxDisplayCount);
    }
}
