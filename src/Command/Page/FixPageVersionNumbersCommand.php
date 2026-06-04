<?php

namespace Inachis\Command\Page;

use Doctrine\ORM\EntityManagerInterface;
use Inachis\Entity\Content\{Page, Revision};
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'inachis:page:fix-versions',
    description: 'Corrects page version numbers based on revision history'
)]
class FixPageVersionNumbersCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $pageRepository = $this->entityManager->getRepository(Page::class);

        /** @var Page[] $pages */
        $pages = $pageRepository->findAll();

        $updated = 0;

        foreach ($pages as $page) {
            $revisionCount = (int) $this->entityManager
                ->createQueryBuilder()
                ->select('COUNT(r.id)')
                ->from(Revision::class, 'r')
                ->where('r.page_id = :pageId')
                ->setParameter('pageId', (string) $page->getId())
                ->getQuery()
                ->getSingleScalarResult();

            $correctVersion = $revisionCount + 1;

            if ($page->getVersionNumber() !== $correctVersion) {
                $output->writeln(sprintf(
                    'Updating "%s" from %d to %d',
                    $page->getTitle(),
                    $page->getVersionNumber(),
                    $correctVersion
                ));

                $page->setVersionNumber($correctVersion);
                ++$updated;
            }
        }

        $this->entityManager->flush();

        $output->writeln(sprintf(
            'Updated %d page version numbers.',
            $updated
        ));

        return Command::SUCCESS;
    }
}
