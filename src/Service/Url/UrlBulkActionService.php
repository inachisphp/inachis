<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Service\Url;


use App\Repository\UrlRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\OptimisticLockException;

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
     * @param string $action
     * @param array $ids
     * @return int
     * @throws OptimisticLockException
     */
    public function apply(string $action, array $ids): int
    {
        $count = 0;
        foreach ($ids as $id) {
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
                default   => null,
            };
            $count++;
        }

        return $count;
    }

    protected function makeDefault($url): void
    {
        $previous_default = $this->urlRepository->findOneBy(
            [
                'content' => $url->getContent(),
                'default' => true,
            ]
        );
        if ($previous_default !== null) {
            $previous_default->setDefault(false)->setModDate(new DateTime('now'));
            $this->entityManager->persist($previous_default);
        }
        $url->setDefault(true)->setModDate(new DateTime('now'));
        $this->entityManager->persist($url);
        $this->entityManager->flush();
    }
}
