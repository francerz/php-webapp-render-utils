<?php

namespace Francerz\WebappRenderUtils;

use Fig\Http\Message\StatusCodeInterface;
use LogicException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

class Renderer
{
    private $responseFactory;
    private $streamFactory;

    public function __construct(
        ResponseFactoryInterface $responseFactory,
        ?StreamFactoryInterface $streamFactory = null
    ) {
        $this->responseFactory = $responseFactory;
        $this->streamFactory = $streamFactory;
    }

    /**
     * @param ResponseInterface $response
     * @param string $headerString
     * @return ResponseInterface
     */
    private static function importHeaders(ResponseInterface $response, string $headerString): ResponseInterface
    {
        $headers = explode("\r\n", $headerString);
        for ($i = 2; $i < count($headers); $i++) {
            $h = $headers[$i];
            if (empty($h)) {
                continue;
            }
            if (stripos($h, 'HTTP') === 0) {
                continue;
            }
            list($header, $hContent) = explode(':', $h);
            $response = $response->withHeader($header, preg_split('/,\\s*/', trim($hContent)));
        }
        return $response;
    }

    private static function normalizeCsvString($string, CsvOptions $options)
    {
        if (strpos($string, $options->getFieldSeparator()) === false && strpos($string, '"') === false) {
            return $string;
        }
        return '"' . strtr($string, '"', '""') . '"';
    }

    private function getStreamFactory()
    {
        if (!isset($this->streamFactory)) {
            throw new LogicException('Missing $responseFactory.');
        }
        return $this->streamFactory;
    }

    public function setStreamFactory(StreamFactoryInterface $streamFactory)
    {
        $this->streamFactory = $streamFactory;
    }

    /**
     * @param UriInterface|string $location
     * @param int $code
     * @param ResponseInterface|null $response
     * @return ResponseInterface
     */
    public function renderRedirect(
        $location,
        int $code = StatusCodeInterface::STATUS_FOUND,
        ?ResponseInterface $response = null
    ): ResponseInterface {
        $response = $response ?? $this->responseFactory->createResponse($code);
        return $response
            ->withStatus($code)
            ->withHeader('Location', (string)$location);
    }

    /**
     * @param mixed $content
     * @param string $contentType
     * @param ResponseInterface|null $response
     * @return ResponseInterface
     */
    public function render(
        $content,
        $contentType = 'text/plain',
        ?ResponseInterface $response = null
    ): ResponseInterface {
        $response = $response ?? $this->responseFactory->createResponse();
        $response = $response->withHeader('Content-Type', $contentType);
        $body = $response->getBody();
        $body->write((string)$content);
        $body->rewind();
        return $response;
    }

    /**
     * @param mixed $data
     * @param ResponseInterface|null $response
     * @return ResponseInterface
     */
    public function renderJson($data, ?ResponseInterface $response = null): ResponseInterface
    {
        return $this->render(json_encode($data), 'application/json;charset=utf-8', $response);
    }

    /**
     * @param object[] $data
     * @param string $filename
     * @param CsvOptions|null $options
     * @param ResponseInterface|null $response
     * @return ResponseInterface
     */
    public function renderCsv(
        array $data,
        $filename = 'file.csv',
        ?CsvOptions $options = null,
        ?ResponseInterface $response = null
    ): ResponseInterface {
        $options = $options ?? new CsvOptions();
        $columns = [];
        foreach ($data as $row) {
            foreach ($row as $name => $_) {
                $colName = static::normalizeCsvString($name, $options);
                $columns[$name] = $colName;
            }
        }
        $columns = array_unique($columns);

        $response = $response ?? $this->responseFactory->createResponse();
        $body = $response->getBody();
        if ($options->getWithHeaders()) {
            $body->write(implode(',', $columns));
            $body->write($options->getRowSeparator());
        }
        foreach ($data as $row) {
            $line = [];
            $row = (array)$row;
            foreach ($columns as $name => $colName) {
                $line[] = static::normalizeCsvString($row[$name] ?? '', $options);
            }
            $body->write(implode($options->getFieldSeparator(), $line));
            $body->write($options->getRowSeparator());
        }
        $body->rewind();

        return $response
            ->withStatus(StatusCodeInterface::STATUS_OK)
            ->withHeader('Content-Type', 'text/csv')
            ->withHeader('Content-Disposition', "attachment; filename=\"{$filename}\"");
    }

    /**
     * @param string $filepath
     * @param string|null $filename
     * @param boolean $attachment
     * @return ResponseInterface
     */
    public function renderFile(
        string $filepath,
        ?string $filename = null,
        bool $attachment = false,
        ?ResponseInterface $response = null
    ): ResponseInterface {
        $streamFactory = $this->getStreamFactory();
        $response = $response ?? $this->responseFactory->createResponse();
        $response = $response
            ->withHeader('Content-Type', mime_content_type($filepath))
            ->withBody($streamFactory->createStreamFromFile($filepath));
        $disposition = $attachment ? 'attachment' : 'inline';
        if (isset($filename)) {
            $disposition .= ";filename=\"{$filename}\"";
        }
        $response = $response->withHeader('Content-Disposition', $disposition);
        return $response;
    }

    /**
     * @param \CurlHandle|resource $curl
     * @param string $responseBody
     * @return ResponseInterface
     */
    public function renderCurlResponse(
        $curl,
        string $responseBody = '',
        ?ResponseInterface $response = null
    ): ResponseInterface {
        $streamFactory = $this->getStreamFactory();
        $code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $response = $response ?? $this->responseFactory->createResponse();
        $response = $response->withStatus($code);

        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $headerString = trim(substr($responseBody, 0, $headerSize));
        $response = static::importHeaders($response, $headerString);

        $content = substr($responseBody, $headerSize);
        $response = $response->withBody($streamFactory->createStream($content));

        return $response;
    }

    public function renderView(
        string $viewpath,
        array $data = [],
        ?ResponseInterface $response = null
    ): ResponseInterface {
        $streamFactory = $this->getStreamFactory();

        $state = new ServerState();

        $view = new View($viewpath, $data);
        $stream = $view->render($streamFactory);

        // Creates PSR-7 ResponseInterface
        $response = $this->responseFactory
            ->createResponse($state->getNewCode())
            ->withBody($stream);

        $headers = $state->getNewHeaders();
        foreach ($headers as $hname => $hcontent) {
            $response = $response->withHeader(trim($hname), $hcontent);
        }

        $state->restore();
        return $response;
    }
}
