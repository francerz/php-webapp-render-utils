<?php

namespace Francerz\WebappRenderUtils\Tests;

use Fig\Http\Message\StatusCodeInterface;
use Francerz\Http\HttpFactory;
use Francerz\Http\Response;
use Francerz\WebappRenderUtils\CsvOptions;
use Francerz\WebappRenderUtils\Renderer;
use PHPUnit\Framework\TestCase;

class RendererTest extends TestCase
{
    public function testRenderRedirect()
    {
        $responseFactory = new HttpFactory();
        $renderer = new Renderer($responseFactory);

        $response = $renderer->renderRedirect('http://www.example.com/test');

        $this->assertEquals(StatusCodeInterface::STATUS_FOUND, $response->getStatusCode());
        $this->assertEquals(['http://www.example.com/test'], $response->getHeader('Location'));
    }

    public function testRenderRedirectResponse()
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

    public function renderString()
    {
        $responseFactory = new HttpFactory();
        $renderer = new Renderer($responseFactory);

        $response = $renderer->render("Hello World!");

        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertEquals(['text/plain'], $response->getHeader('Content-Type'));
        $this->assertEquals("Hello World!", (string)$response->getBody());
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

    public function testRenderCsv()
    {
        $responseFactory = new HttpFactory();
        $renderer = new Renderer($responseFactory);

        $data = [
            ['first' => 'Joe',  'second' => 'Doe', 'third' => 16],
            ['first' => 'Jane', 'second' => 'Doe', 'fourth' => 'Open, go'],
            ['first' => 'Mary', 'second' => 'Smith', 'third' => 32],
            ['first' => 'Michael', 'second' => 'Jackson', 'fourth' => '"The Database"']
        ];

        $response = $renderer->renderCsv($data, 'file.csv');

        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertEquals(['text/csv'], $response->getHeader('Content-Type'));
        $this->assertEquals(["attachment; filename=\"file.csv\""], $response->getHeader('Content-Disposition'));

        $expected =
            "first,second,third,fourth\n" .
            "Joe,Doe,16,\n" .
            "Jane,Doe,,\"Open, go\"\n" .
            "Mary,Smith,32,\n" .
            "Michael,Jackson,,\"\"The Database\"\"\n";
        $this->assertEquals($expected, (string)$response->getBody());

        $options = new CsvOptions("\r\n", ';', false);
        $response = $renderer->renderCsv($data, 'file.csv', $options);

        $expected =
            "Joe;Doe;16;\r\n" .
            "Jane;Doe;;Open, go\r\n" .
            "Mary;Smith;32;\r\n" .
            "Michael;Jackson;;\"\"The Database\"\"\r\n";
        $this->assertEquals($expected, (string)$response->getBody());
    }

    public function testRenderFile()
    {
        $httpFactory = new HttpFactory();
        $renderer = new Renderer($httpFactory, $httpFactory);

        $path = dirname(__FILE__, 2) . '/tests-assets/test.txt';

        $response = $renderer->renderFile($path, 'test-file.txt', true);

        $this->assertEquals(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        $this->assertEquals(['text/plain'], $response->getHeader('Content-Type'));
        $this->assertEquals(["attachment;filename=\"test-file.txt\""], $response->getHeader('Content-Disposition'));
        $this->assertEquals(
            "Alpha, Bravo, Charlie, Delta,\nEcho, Foxtrot, Golf, Hotel.\n",
            (string)$response->getBody()
        );
    }

    /**
     * @runInSeparateProcess
     */
    public function testRenderView()
    {
        $httpFactory = new HttpFactory();
        $renderer = new Renderer($httpFactory, $httpFactory);

        $path = dirname(__FILE__, 2) . '/tests-assets/view.php';
        $response = $renderer->renderView($path, [
            'title' => 'Main title',
            'content' => 'Lorem ipsum dolor sit amet consectetur, adipisicing elit. Laborum, alias?'
        ]);
        $this->assertEquals(['X-Test-Header' => ['New Test Header', 'Other Header']], $response->getHeaders());
        $expected =
            "<html>\n" .
            "    <head>\n" .
            "        <link href=\"styles.css\" />\n" .
            "        <title>Main title</title>\n" .
            "    </head>\n" .
            "    <body>\n" .
            "        <h1>Main title</h1>\n" .
            "        <p>Lorem ipsum dolor sit amet consectetur, adipisicing elit. Laborum, alias?</p>\n" .
            "    </body>\n" .
            "</html>\n";
        $expected = preg_replace('/\\s+/', ' ', $expected);
        $actual = preg_replace('/\\s+/', ' ', (string)$response->getBody());
        $this->assertEquals($expected, $actual);
    }
}
