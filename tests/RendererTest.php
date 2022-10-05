<?php

namespace Francerz\WebappRenderUtils\Tests;

use Fig\Http\Message\StatusCodeInterface;
use Francerz\Http\HttpFactory;
use Francerz\Http\Response;
use Francerz\WebappRenderUtils\Renderer;
use PHPUnit\Framework\TestCase;

class RendererTest extends TestCase
{
    public function testRedirect()
    {
        $responseFactory = new HttpFactory();
        $renderer = new Renderer($responseFactory);

        $response = $renderer->renderRedirect('http://www.example.com/test');

        $this->assertEquals(StatusCodeInterface::STATUS_FOUND, $response->getStatusCode());
        $this->assertEquals(['http://www.example.com/test'], $response->getHeader('Location'));
    }

    public function testRedirectSampled()
    {
        $response = new Response();
        $response = $response->withHeader('Authorization', 'Bearer qwertyuiopasdfghjklzxcvbnm');

        $responseFactory = new HttpFactory();
        $renderer = new Renderer($responseFactory);

        $response = $renderer->renderRedirect(
            'http://www.example.com/test',
            StatusCodeInterface::STATUS_FOUND,
            $response
        );

        $this->assertEquals(StatusCodeInterface::STATUS_FOUND, $response->getStatusCode());
        $this->assertEquals(['http://www.example.com/test'], $response->getHeader('Location'));
        $this->assertEquals(['Bearer qwertyuiopasdfghjklzxcvbnm'], $response->getHeader('Authorization'));
    }

    public function testRenderJson()
    {
        $responseFactory = new HttpFactory();
        $renderer = new Renderer($responseFactory);

        $response = $renderer->renderJson(['a' => 1, 'b' => "second", 'c' => ["hello", "world"]]);

        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertEquals(["application/json;charset=utf-8"], $response->getHeader('Content-Type'));
        $this->assertEquals('{"a":1,"b":"second","c":["hello","world"]}', (string)$response->getBody());
    }
}
