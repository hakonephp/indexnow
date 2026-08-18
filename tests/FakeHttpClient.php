<?php

declare(strict_types=1);

namespace Hakone\IndexNow;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class FakeHttpClient implements ClientInterface
{
    /** @var list<RequestInterface> */
    public array $receivedRequests = [];

    public function __construct(
        private ResponseInterface $response
    ) {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->receivedRequests[] = $request;

        return $this->response;
    }
}
