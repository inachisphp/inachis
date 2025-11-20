<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Repository;

use App\Model\SearchResult;
use App\Repository\SearchRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class SearchRepositoryTest extends TestCase
{
    private SearchRepository $repository;
    private Connection $connection;

    public function setUp(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
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
        $method->setAccessible(true);

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
            ['id' => 2, 'title' => 'Second result']
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
            ->expects($this->exactly(2))
            ->method('prepare')
            ->willReturnOnConsecutiveCalls(
                $this->createConfiguredMock(Statement::class, [
                    'executeQuery' => $mainStmt
                ]),
                $this->createConfiguredMock(Statement::class, [
                    'executeQuery' => $countStmt
                ])
            );
        $result = $this->repository->search($keyword, $offset, $limit);

        $this->assertInstanceOf(SearchResult::class, $result);
        $this->assertSame($fetchedRows, $result->getResults());
        $this->assertSame($totalResults, $result->getTotal());
        $this->assertSame($offset, $result->getOffset());
        $this->assertSame($limit, $result->getLimit());
    }


    public function testGetSQLUnionGeneratesCorrectSQL(): void
    {
        $fields = ['p.id, p.title', 's.id, s.title', 'i.id, i.title'];

        $reflection = new \ReflectionClass($this->repository);
        $method = $reflection->getMethod('getSQLUnion');
        $method->setAccessible(true);

        $sql = $method->invoke($this->repository, $fields);

        $this->assertStringContainsString('SELECT p.id, p.title FROM page p WHERE', $sql);
        $this->assertStringContainsString('SELECT s.id, s.title FROM series s WHERE', $sql);
        $this->assertStringContainsString('SELECT i.id, i.title FROM image i WHERE', $sql);
        $this->assertStringContainsString('UNION ALL', $sql);
    }
}
