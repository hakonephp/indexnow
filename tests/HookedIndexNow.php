<?php

declare(strict_types=1);

namespace Hakone\IndexNow;

use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Http\Message\ResponseInterface as HttpResponse;

readonly class HookedIndexNow extends IndexNow
{
    #[\NoDiscard]
    protected function createRequest(string $method, string $url): HttpRequest
    {
        return parent::createRequest($method, $url)
            ->withHeader('X-Hook-Create', '1');
    }

    #[\NoDiscard]
    protected function appendDefaultRequestHeaders(HttpRequest $request): HttpRequest
    {
        return parent::appendDefaultRequestHeaders($request)
            ->withHeader('X-Hook-Headers', '1');
    }

    /**
     * @param array<string, mixed> $payload
     */
    #[\NoDiscard]
    protected function appendJsonPayload(HttpRequest $request, array $payload): HttpRequest
    {
        return parent::appendJsonPayload($request, $payload)
            ->withHeader('X-Hook-Json', '1');
    }

    protected function sendRequest(HttpRequest $request): HttpResponse
    {
        return parent::sendRequest($request)
            ->withHeader('X-Hook-Send', '1');
    }
}
