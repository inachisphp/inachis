<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Command\Page;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Inachis\Command\Page\FixPageVersionNumbersCommand;
use Inachis\Entity\Content\Page;
use Inachis\Entity\Content\Revision;
use Inachis\Repository\Content\PageRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class FixPageVersionNumbersCommandTest extends TestCase
{
    #[Test]
    public function itUpdatesPagesWithIncorrectVersionNumbers(): void
    {
        $page = new Page();
        $pageId = Uuid::uuid1();

        $page->setId($pageId);
        $page->setTitle('Test Page');
        $page->setVersionNumber(1);

        $repository = $this->createMock(PageRepository::class);
        $repository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn([$page]);

        $query = $this->createMock(Query::class);
        $query
            ->expects(self::once())
            ->method('getSingleScalarResult')
            ->willReturn('3');

        $queryBuilder = $this->createQueryBuilderMock(
            $query,
            (string) $pageId,
        );

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $command = new FixPageVersionNumbersCommand(
            $entityManager,
            $repository,
        );

        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $result = $command->run($input, $output);

        self::assertSame(Command::SUCCESS, $result);
        self::assertSame(4, $page->getVersionNumber());

        $display = $output->fetch();

        self::assertStringContainsString(
            'Updating "Test Page" from 1 to 4',
            $display,
        );

        self::assertStringContainsString(
            'Updated 1 page version numbers.',
            $display,
        );
    }

    #[Test]
    public function itDoesNotUpdatePagesWithCorrectVersionNumbers(): void
    {
        $page = new Page();
        $pageId = Uuid::uuid1();

        $page->setId($pageId);
        $page->setTitle('Correct Page');
        $page->setVersionNumber(4);

        $repository = $this->createMock(PageRepository::class);
        $repository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn([$page]);

        $query = $this->createMock(Query::class);
        $query
            ->expects(self::once())
            ->method('getSingleScalarResult')
            ->willReturn('3');

        $queryBuilder = $this->createQueryBuilderMock(
            $query,
            (string) $pageId,
        );

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $command = new FixPageVersionNumbersCommand(
            $entityManager,
            $repository,
        );

        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $result = $command->run($input, $output);

        self::assertSame(Command::SUCCESS, $result);
        self::assertSame(4, $page->getVersionNumber());

        $display = $output->fetch();

        self::assertStringNotContainsString(
            'Updating "Correct Page"',
            $display,
        );

        self::assertStringContainsString(
            'Updated 0 page version numbers.',
            $display,
        );
    }

    #[Test]
    public function itHandlesMultiplePages(): void
    {
        $firstPage = new Page();
        $firstPageId = Uuid::uuid1();

        $firstPage->setId($firstPageId);
        $firstPage->setTitle('First Page');
        $firstPage->setVersionNumber(1);

        $secondPage = new Page();
        $secondPageId = Uuid::uuid1();

        $secondPage->setId($secondPageId);
        $secondPage->setTitle('Second Page');
        $secondPage->setVersionNumber(3);

        $repository = $this->createMock(PageRepository::class);
        $repository
            ->expects(self::once())
            ->method('findAll')
            ->willReturn([
                $firstPage,
                $secondPage,
            ]);

        $firstQuery = $this->createMock(Query::class);
        $firstQuery
            ->expects(self::once())
            ->method('getSingleScalarResult')
            ->willReturn('0');

        $secondQuery = $this->createMock(Query::class);
        $secondQuery
            ->expects(self::once())
            ->method('getSingleScalarResult')
            ->willReturn('5');

        $firstQueryBuilder = $this->createQueryBuilderMock(
            $firstQuery,
            (string) $firstPageId,
        );

        $secondQueryBuilder = $this->createQueryBuilderMock(
            $secondQuery,
            (string) $secondPageId,
        );

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::exactly(2))
            ->method('createQueryBuilder')
            ->willReturnOnConsecutiveCalls(
                $firstQueryBuilder,
                $secondQueryBuilder,
            );

        $entityManager
            ->expects(self::once())
            ->method('flush');

        $command = new FixPageVersionNumbersCommand(
            $entityManager,
            $repository,
        );

        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $result = $command->run($input, $output);

        self::assertSame(Command::SUCCESS, $result);

        self::assertSame(1, $firstPage->getVersionNumber());
        self::assertSame(6, $secondPage->getVersionNumber());

        $display = $output->fetch();

        self::assertStringContainsString(
            'Updating "Second Page" from 3 to 6',
            $display,
        );

        self::assertStringContainsString(
            'Updated 1 page version numbers.',
            $display,
        );
    }

    /**
     * @param Query $query
     */
    private function createQueryBuilderMock(
        Query $query,
        string $pageId,
    ): QueryBuilder {
        $queryBuilder = $this->createMock(QueryBuilder::class);

        $queryBuilder
            ->method('select')
            ->with('COUNT(r.id)')
            ->willReturnSelf();

        $queryBuilder
            ->method('from')
            ->with(Revision::class, 'r')
            ->willReturnSelf();

        $queryBuilder
            ->method('where')
            ->with('r.page_id = :pageId')
            ->willReturnSelf();

        $queryBuilder
            ->method('setParameter')
            ->with('pageId', $pageId)
            ->willReturnSelf();

        $queryBuilder
            ->method('getQuery')
            ->willReturn($query);

        return $queryBuilder;
    }
}
