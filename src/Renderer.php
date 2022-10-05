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

    public function renderJson($data, ?ResponseInterface $response = null)
    {
        $response = $response ?? $this->responseFactory->createResponse();
        $response = $response->withHeader('Content-Type', 'application/json;charset=utf-8');
        $body = $response->getBody();
        $body->write(json_encode($data));
        $body->rewind();
        return $response;
    }
}
