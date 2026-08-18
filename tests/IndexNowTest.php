<?php

declare(strict_types=1);

namespace Hakone\IndexNow;

use Hakone\IndexNow\Exception\IndexNowException;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface as HttpRequest;
use Psr\Http\Message\ResponseInterface as HttpResponse;

#[CoversClass(IndexNow::class)]
#[UsesClass(IndexNowException::class)]
final class IndexNowTest extends TestCase
{
    private Psr17Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new Psr17Factory();
    }

    public function test_submitUrl_sends_get_query_and_returns_success_response(): void
    {
        $http = $this->clientWithStatus(200);
        $subject = new IndexNow($http);

        $response = $subject->submitUrl('www.example.com', 'secret', 'http://www.example.com/product.html');

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(1, $http->receivedRequests);
        $request = $http->receivedRequests[0];
        self::assertSame('GET', $request->getMethod());
        self::assertSame(
            'https://www.bing.com/indexNow?host=www.example.com&key=secret&url=http%3A%2F%2Fwww.example.com%2Fproduct.html',
            (string)$request->getUri()
        );
        self::assertSame(
            ['HakonePhpIndexNow/0.1 (+https://github.com/hakonephp/indexnow)'],
            $request->getHeader('User-Agent')
        );
    }

    public function test_submitList_sends_json_post_and_returns_accepted_response(): void
    {
        $http = $this->clientWithStatus(202);
        $subject = new IndexNow($http);

        $response = $subject->submitList('www.example.com', 'secret', [
            'https://www.example.com/url1',
            'https://www.example.com/folder/url2',
        ]);

        self::assertSame(202, $response->getStatusCode());
        self::assertCount(1, $http->receivedRequests);
        $request = $http->receivedRequests[0];
        self::assertSame('POST', $request->getMethod());
        self::assertSame('https://www.bing.com/indexNow', (string)$request->getUri());
        self::assertSame(['application/json; charset=utf-8'], $request->getHeader('Content-Type'));
        self::assertSame(
            '{"host":"www.example.com","key":"secret","urlList":["https://www.example.com/url1","https://www.example.com/folder/url2"]}',
            (string)$request->getBody()
        );
    }

    public function test_submitList_includes_keyLocation_when_given(): void
    {
        $http = $this->clientWithStatus(200);
        $subject = new IndexNow($http);

        $subject->submitList(
            'www.example.com',
            'secret',
            ['https://www.example.com/url1'],
            'https://www.example.com/indexnow-key.txt'
        );

        self::assertCount(1, $http->receivedRequests);
        self::assertSame(
            '{"host":"www.example.com","key":"secret","urlList":["https://www.example.com/url1"],"keyLocation":"https://www.example.com/indexnow-key.txt"}',
            (string)$http->receivedRequests[0]->getBody()
        );
    }

    public function test_submitUrl_uses_custom_search_engine_host(): void
    {
        $http = $this->clientWithStatus(200);
        $subject = new IndexNow($http, 'api.indexnow.org');

        $subject->submitUrl('www.example.com', 'secret', 'https://www.example.com/');

        self::assertCount(1, $http->receivedRequests);
        self::assertSame('api.indexnow.org', $http->receivedRequests[0]->getUri()->getHost());
    }

    public function test_subclass_can_customize_protected_request_hooks(): void
    {
        $http = $this->clientWithStatus(200);
        $subject = new readonly class($http) extends IndexNow {
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
        };

        $response = $subject->submitList('www.example.com', 'secret', ['https://www.example.com/url1']);

        self::assertCount(1, $http->receivedRequests);
        $request = $http->receivedRequests[0];
        self::assertSame(['1'], $request->getHeader('X-Hook-Create'));
        self::assertSame(['1'], $request->getHeader('X-Hook-Headers'));
        self::assertSame(['1'], $request->getHeader('X-Hook-Json'));
        self::assertSame(['1'], $response->getHeader('X-Hook-Send'));
    }

    /**
     * @param non-empty-string $expectedMessage
     */
    #[DataProvider('errorStatusProvider')]
    public function test_submitUrl_throws_documented_error_for_known_status(
        int $status,
        string $expectedMessage
    ): void {
        $response = $this->factory->createResponse($status);
        $http = new FakeHttpClient($response);
        $subject = new IndexNow($http);

        try {
            $subject->submitUrl('www.example.com', 'secret', 'https://www.example.com/');
            self::fail('Expected IndexNowException');
        } catch (IndexNowException $exception) {
            self::assertSame($expectedMessage, $exception->getMessage());
            self::assertSame($status, $exception->statusCode);
            self::assertSame($response, $exception->response);
        }
    }

    /**
     * @return iterable<string, array{int, non-empty-string}>
     */
    public static function errorStatusProvider(): iterable
    {
        yield 'bad request' => [400, 'IndexNow Bad Request: Invalid format'];
        yield 'forbidden' => [403, 'IndexNow Forbidden: In case of key not valid (e.g. key not found, file found but key not in the file)'];
        yield 'unprocessable' => [422, 'IndexNow Unprocessable Entity: In case of URLs which don’t belong to the host or the key is not matching the schema in the protocol'];
        yield 'too many requests' => [429, 'IndexNow Too Many Requests (potential Spam)'];
        yield 'unexpected' => [500, 'Unexpected Server Response'];
    }

    private function clientWithStatus(int $status): FakeHttpClient
    {
        return new FakeHttpClient($this->factory->createResponse($status));
    }
}
