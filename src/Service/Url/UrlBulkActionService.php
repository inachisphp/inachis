<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Service\Url;

use DateTimeImmutable;
use Inachis\Entity\Url;
use Inachis\Repository\UrlRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;

/**
 * Service for applying bulk actions to URLs
 */
readonly class UrlBulkActionService
{
    /**
     * @param UrlRepository $urlRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private UrlRepository $urlRepository,
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Apply a bulk action to a list of URLs
     * 
     * @param string $action
     * @param array<string> $ids
     * @return int
     * @throws OptimisticLockException
     */
    public function apply(string $action, array $ids): int
    {
        $count = 0;
        foreach ($ids as $id) {
            /** @var Url|null $url */
            $url = $this->urlRepository->findOneBy([
                'id' => $id,
                'default' => false,
            ]);
            if (!$url) {
                continue;
            }
            match ($action) {
                'delete'  => $this->urlRepository->remove($url),
                'make_default'  => $this->makeDefault($url),
                default => null,
            };
            $count++;
        }

        return $count;
    }

    /**
     * Make a URL the default URL for its content
     * 
     * @param Url $url
     * @return void
     */
    protected function makeDefault(Url $url): void
    {
        /** @var Url|null $previous_default */
        $previous_default = $this->urlRepository->findOneBy(
            [
                'content' => $url->getContent(),
                'default' => true,
            ]
        );
        if ($previous_default !== null) {
            $previous_default->setDefault(false)->setModDate(new DateTimeImmutable());
            $this->entityManager->persist($previous_default);
        }
        $url->setDefault(true)->setModDate(new DateTimeImmutable());
        $this->entityManager->persist($url);
        $this->entityManager->flush();
    }
}
