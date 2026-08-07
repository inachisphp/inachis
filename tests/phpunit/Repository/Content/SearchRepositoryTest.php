<?php

declare(strict_types=1);

/**
 * This file is part of the inachis framework.
 */

namespace Inachis\Tests\phpunit\Repository\Content;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Doctrine\Persistence\ManagerRegistry;
use Inachis\Model\SearchResult;
use Inachis\Repository\Content\SearchRepository;
use PHPUnit\Framework\TestCase;

class SearchRepositoryTest extends TestCase
{
    private SearchRepository $repository;
    private Connection $connection;

    public function setUp(): void
    {
        $registry = $this->createStub(ManagerRegistry::class);
        $this->connection = $this->createMock(Connection::class);
        $this->repository = new SearchRepository($registry, $this->connection);
    }

    public function testGetWhereConditions(): void
    {
        $types = [
            'page' => 'MATCH(p.title, p.sub_title, p.content) AGAINST(:kw IN NATURAL LANGUAGE MODE)',
            'series' => 'MATCH(s.title, s.sub_title, s.description) AGAINST(:kw IN NATURAL LANGUAGE MODE)',
        ];
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('getWhereConditions');

        $this->assertEquals($types['page'], $method->invoke($this->repository, 'page'));
        $this->assertEquals($types['series'], $method->invoke($this->repository, 'series'));
    }

    public function testSearchReturnsSearchResult(): void
    {
        $keyword = 'example';
        $offset = 5;
        $limit = 10;
        $totalResults = 42;

        $fetchedRows = [
            ['id' => 1, 'title' => 'First result'],
            ['id' => 2, 'title' => 'Second result'],
        ];

        $mainResult = $this->createMock(Result::class);
        $mainResult
            ->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn($fetchedRows);

        $countResult = $this->createMock(Result::class);
        $countResult
            ->expects($this->once())
            ->method('fetchOne')
            ->willReturn($totalResults);

        $mainStatement = $this->createMock(Statement::class);
        $mainStatement
            ->method('bindValue')
            ->willReturnSelf();
        $mainStatement
            ->method('executeQuery')
            ->willReturn($mainResult);

        $countStatement = $this->createMock(Statement::class);
        $countStatement
            ->method('bindValue')
            ->willReturnSelf();
        $countStatement
            ->method('executeQuery')
            ->willReturn($countResult);

        $this->connection
            ->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls(
                $mainStatement,
                $countStatement,
            );

        $result = $this->repository->search($keyword, $limit, $offset);

        $this->assertInstanceOf(SearchResult::class, $result);
        $this->assertSame($fetchedRows, $result->getResults());
        $this->assertSame($totalResults, $result->getTotal());
        $this->assertSame($offset, $result->getOffset());
        $this->assertSame($limit, $result->getLimit());
    }

    public function testSearchPublicReturnsSearchResultWithoutImages(): void
    {
        $keyword = 'example';
        $offset = 0;
        $limit = 10;
        $totalResults = 3;
        $fetchedRows = [
            ['id' => 1, 'title' => 'Page result', 'type' => 'Page'],
            ['id' => 2, 'title' => 'Series result', 'type' => 'Series'],
        ];

        $mainStmt = $this->createMock(Result::class);
        $mainStmt->expects($this->once())
            ->method('fetchAllAssociative')
            ->willReturn($fetchedRows);
        $countStmt = $this->createMock(Result::class);
        $countStmt->expects($this->once())
            ->method('fetchOne')
            ->willReturn($totalResults);

        $this->connection
            ->method('prepare')
            ->willReturnOnConsecutiveCalls(
                $this->createConfiguredStub(Statement::class, [
                    'executeQuery' => $mainStmt,
                ]),
                $this->createConfiguredStub(Statement::class, [
                    'executeQuery' => $countStmt,
                ]),
            );

        $result = $this->repository->searchPublic($keyword, $offset, $limit);

        $this->assertInstanceOf(SearchResult::class, $result);
        $this->assertSame($fetchedRows, $result->getResults());
        $this->assertSame($totalResults, $result->getTotal());
    }

    public function testGetSQLUnionGeneratesCorrectSQL(): void
    {
        $fields = ['p.id, p.title', 's.id, s.title', 'i.id, i.title'];

        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('getSQLUnion');

        $sql = $method->invoke($this->repository, $fields);

        $this->assertStringContainsString('SELECT p.id, p.title FROM page p WHERE', $sql);
        $this->assertStringContainsString('SELECT s.id, s.title FROM series s WHERE', $sql);
        $this->assertStringContainsString('SELECT i.id, i.title FROM image i WHERE', $sql);
        $this->assertStringContainsString('UNION ALL', $sql);
    }

    public function testGetSQLUnionWithoutImagesOmitsImageBranch(): void
    {
        $fields = ['p.id, p.title', 's.id, s.title'];

        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('getSQLUnion');

        $sql = $method->invoke($this->repository, $fields, false);

        $this->assertStringContainsString('SELECT p.id, p.title FROM page p WHERE', $sql);
        $this->assertStringContainsString('SELECT s.id, s.title FROM series s WHERE', $sql);
        $this->assertStringNotContainsString('FROM image i WHERE', $sql);
    }

    public function testDetermineOrderBy(): void
    {
        $orders = [
            'contentDate asc' => 'contentDate ASC',
            'contentDate desc' => 'contentDate DESC',
            'relevance asc' => 'relevance ASC, contentDate DESC',
            'title desc' => 'title DESC',
            'title asc' => 'title ASC',
            'type desc' => 'type DESC',
            'type asc' => 'type ASC',
            'default' => 'relevance DESC, contentDate DESC',
        ];
        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('determineOrderBy');
        foreach ($orders as $key => $order) {
            $this->assertEquals($order, $method->invokeArgs($this->repository, [$key]));
        }
    }
}
