<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace Inachis\Tests\phpunit\Model;

use DateTimeImmutable;
use Inachis\Model\BulkCreateData;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Request;

class BulkCreateDataTest extends TestCase
{
    protected BulkCreateData $bulkCreateData;

    public function setUp(): void
    {
        $this->bulkCreateData = new BulkCreateData(
            'some title',
            DateTimeImmutable::createFromFormat('d/m/Y', '01/11/2025'),
            DateTimeImmutable::createFromFormat('d/m/Y', '07/11/2025'),
            false,
            Uuid::uuid1()->toString(),
            [ 'test-tag' ],
            [ 'test-category' ],
        );
    }

    public function testFromRequestNoFormData(): void
    {
        $request = new Request([], [], [], [], [], [
            'REQUEST_URI' => '/incc/series/some-post'
        ]);
        $this->expectExceptionMessage('Form data is missing.');
        BulkCreateData::fromRequest($request);
    }

    public function testFromRequestNoTitle(): void
    {
        $request = new Request([], [
            'form' => [
                'test' => 'some title',
            ],
        ], [], [], [], []);
        $this->expectExceptionMessage('Title is required.');
        BulkCreateData::fromRequest($request);
    }

    public function testFromRequestNoDates(): void
    {
        $request = new Request([], [
            'form' => [
                'title' => 'some title',
            ],
        ], [], [], [], []);
        $this->expectExceptionMessage('Start and end dates are required.');
        BulkCreateData::fromRequest($request);
    }

    public function testFromRequestInvalidDateFormat(): void
    {
        $request = new Request([], [
            'form' => [
                'title' => 'some title',
                'startDate' => '2025-11-01',
                'endDate' => '2025-11-07',
            ],
        ], [], [], [], []);
        $this->expectExceptionMessage('Invalid date format, expected d/m/Y.');
        BulkCreateData::fromRequest($request);
    }

    public function testFromRequest(): void
    {
        $request = new Request([], [
            'form' => [
                'title' => 'some title',
                'startDate' => '01/11/2025',
                'endDate' => '07/11/2025',
                'tags' => ['test-tag'],
                'categories' => ['test-category'],
            ],
            'seriesId' => $this->bulkCreateData->seriesId,
        ], [], [], [], []);
        $result = BulkCreateData::fromRequest($request);
        $this->assertEquals($this->bulkCreateData, $result);
    }
}
