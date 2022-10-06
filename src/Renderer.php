<?php

namespace Francerz\WebappRenderUtils;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class Renderer
{
    private $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    public function renderRedirect(
        $location,
        int $code = StatusCodeInterface::STATUS_FOUND,
        ?ResponseInterface $response = null
    ) {
        $response = $response ?? $this->responseFactory->createResponse($code);
        return $response
            ->withStatus($code)
            ->withHeader('Location', (string)$location);
    }

    public function render($content, $contentType = 'text/plain', ?ResponseInterface $response = null)
    {
        $response = $response ?? $this->responseFactory->createResponse();
        $response = $response->withHeader('Content-Type', $contentType);
        $body = $response->getBody();
        $body->write((string)$content);
        $body->rewind();
        return $response;
    }

    public function renderJson($data, ?ResponseInterface $response = null)
    {
        return $this->render(json_encode($data), 'application/json;charset=utf-8');
    }

    private static function normalizeCsvString($string, CsvOptions $options)
    {
        if (strpos($string, $options->getFieldSeparator()) === false && strpos($string, '"') === false) {
            return $string;
        }
        return '"' . strtr($string, '"', '""') . '"';
    }

    public function renderCsv(
        array $data,
        $filename = 'file.csv',
        ?CsvOptions $options = null,
        ?ResponseInterface $response = null
    ) {
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
}
