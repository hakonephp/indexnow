<?php

declare(strict_types=1);

namespace Hakone\IndexNow;

use Hakone\IndexNow\Exception\IndexNowException;
use Http\Discovery\Psr17FactoryDiscovery;
use Psr\Http\Client\ClientInterface as HttpClient;
use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Http\Message\ResponseInterface as HttpResponse;
use function http_build_query;
use function in_array;
use function json_encode;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;

class IndexNow
{
    /** @var HttpClient */
    private $httpClient;

    /** @var non-empty-string */
    private $searchEngine;

    /** @var array<key-of<self::MESSAGES>, string> */
    private $messages;

    /** @var string */
    private $unexpectedMessage;

    protected const MESSAGES = [
        400 => 'IndexNow Bad Request: Invalid format',
        403 => 'IndexNow Forbidden: In case of key not valid (e.g. key not found, file found but key not in the file)',
        422 => 'IndexNow Unprocessable Entity: In case of URLs which don’t belong to the host or the key is not matching the schema in the protocol',
        429 => 'IndexNow Too Many Requests (potential Spam)',
    ];

    protected const UNEXPECTED_MESSAGE = 'Unexpected Server Response';

    /**
     * @param non-empty-string $searchEngine
     * @param array<key-of<self::MESSAGES>, string> $messages
     */
    public function __construct(HttpClient $httpClient, string $searchEngine = 'www.bing.com', array $messages = self::MESSAGES, string $unexpectedMessage = self::UNEXPECTED_MESSAGE)
    {
        $this->httpClient = $httpClient;
        $this->searchEngine = $searchEngine;
        $this->messages = $messages;
        $this->unexpectedMessage = $unexpectedMessage;
    }

    /**
     * Submit one URL to IndexNow
     *
     * @param non-empty-string $host
     * @param non-empty-string $key
     * @param non-empty-string $url
     * @throws IndexNowException
     */
    public function submitUrl(string $host, string $key, string $url): HttpResponse
    {
        $payload = [
            'host' => $host,
            'key' => $key,
            'url' => $url,
        ];

        $params = http_build_query($payload);
        $request = $this->createRequest('GET', "https://{$this->searchEngine}/indexNow?{$params}");
        $request = $this->appendDefaultRequestHeaders($request);

        return $this->sendRequest($request);
    }

    /**
     * Submit set of URLs to IndexNow
     *
     * @param non-empty-string $host
     * @param non-empty-string $key
     * @param non-empty-list<non-empty-string> $urlList
     * @param ?non-empty-string $keyLocation
     * @throws IndexNowException
     */
    public function submitList(string $host, string $key, array $urlList, ?string $keyLocation = null): HttpResponse
    {
        $payload = [
            'host' => $host,
            'key' => $key,
            'urlList' => $urlList,
        ];

        if ($keyLocation !== null) {
            $payload['keyLocation'] = $keyLocation;
        }

        $request = $this->createRequest('POST', "https://{$this->searchEngine}/indexNow");
        $request = $this->appendDefaultRequestHeaders($request);
        $request = $this->appendJsonPayload($request, $payload);

        return $this->sendRequest($request);
    }

    /** @pure */
    protected function createRequest(string $method, string $url): HttpRequest
    {
        return Psr17FactoryDiscovery::findRequestFactory()->createRequest($method, $url);
    }

    /** @pure */
    protected function appendDefaultRequestHeaders(HttpRequest $request): HttpRequest
    {
        return $request
            ->withHeader('User-Agent', 'HakonePhpIndexNow/0.1 (+https://github.com/hakonephp/indexnow)');
    }

    /**
     * @pure
     * @param array<string, mixed> $payload
     */
    protected function appendJsonPayload(HttpRequest $request, array $payload): HttpRequest
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return $request
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody(Psr17FactoryDiscovery::findStreamFactory()->createStream($json));
    }

    /**
     * @throws IndexNowException
     */
    protected function sendRequest(HttpRequest $request): HttpResponse
    {
        $response = $this->httpClient->sendRequest($request);
        $status = $response->getStatusCode();

        if (in_array($status, [200, 202], true)) {
            return $response;
        }

        throw new IndexNowException($this->messages[$status] ?? $this->unexpectedMessage, $response);
    }
}
