<?php

/**
 * This file is part of the inachis framework
 *
 * @package Inachis
 * @license https://github.com/inachisphp/inachis/blob/main/LICENSE.md
 */

namespace App\Tests\phpunit\Model;

use App\Model\ContentQueryParameters;
use App\Repository\PageRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class ContentQueryParametersTest extends TestCase
{
    protected ContentQueryParameters $contentQueryParameters;
    protected Request $request;

    public function setUp(): void
    {
        $this->contentQueryParameters = new ContentQueryParameters();
        $this->request = new Request([], [
            'filter' => [
                'keyword' => 'test from POST',
            ],
            'sort' => 'myField desc',
        ], [
            'offset' => 50,
            'limit' => 25,
        ], [], [], [
            'REQUEST_URI' => '/incc/post/list',
        ]);

        parent::setUp();
    }

    public function testProcessFromPost(): void
    {
        $this->request->setMethod(Request::METHOD_POST);
        $session = new MockArraySessionStorage();
        $this->request->setSession(new Session($session));
        $result = $this->processHelper();

        $this->assertEquals('test from POST', $result['filters']['keyword']);
    }

    public function testProcessFromSession(): void
    {
        $this->request->setMethod(Request::METHOD_GET);
        $sessionStorage = new MockArraySessionStorage();
        $session = new Session($sessionStorage);
        $session->set('test_filters', [ 'keyword' => 'test from SESSION', ]);
        $session->set('test_sort', 'myField desc');
        $this->request->setSession($session);
        $result = $this->processHelper();
        $this->assertEquals('test from SESSION', $result['filters']['keyword']);
    }

    private function processHelper(): array
    {
        $pageRepository = $this->createStub(PageRepository::class);
        $result = $this->contentQueryParameters->process(
            $this->request,
            $pageRepository,
            'test',
            'myField asc'
        );
        $this->assertIsArray($result['filters']);
        $this->assertEquals(50, $result['offset']);
        $this->assertEquals(25, $result['limit']);
        $this->assertEquals('myField desc', $result['sort']);

        return $result;
    }
}
