<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Service\Content\Series;

use DateTimeImmutable;
use Inachis\Entity\Content\Series;
use Inachis\Repository\Content\SeriesRepository;
use Doctrine\ORM\EntityManagerInterface;
use Inachis\Service\Waste\WasteManagerService;

/**
 * Service for applying bulk actions to series
 */
readonly class SeriesBulkActionService
{
    /**
     * @param SeriesRepository $seriesRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private SeriesRepository $seriesRepository,
        private EntityManagerInterface $entityManager,
        private WasteManagerService $wasteManagerService,
    ) {}

    /**
     * Applies a bulk action to series.
     *
     * @param string $action
     * @param array<string> $ids
     * @return int
     */
    public function apply(string $action, array $ids): int
    {
        $count = 0;
        foreach ($ids as $id) {
            /** @var Series|null $series */
            $series = $this->seriesRepository->findOneBy(['id' => $id]);
            if (empty($series) || empty($series->getId())) {
                continue;
            }
            match ($action) {
                'delete'  => $this->sendToWaste($series),
                'private'  => $series->setVisible(false),
                'public' => $series->setVisible(true),
                default => null,
            };
            if ($action !== 'delete') {
                $series->setUpdatedAt(new DateTimeImmutable());
                $this->entityManager->persist($series);
            }
            $count++;
        }
        $this->entityManager->flush();
        return $count;
    }

    /**
     * Sends a series to waste.
     *
     * @param Series $series
     */
    public function sendToWaste(Series $series): void
    {
        $this->wasteManagerService->sendToWaste($series);
        $this->seriesRepository->remove($series);
    }
}
