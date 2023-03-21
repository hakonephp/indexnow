<?php

declare(strict_types=1);

namespace Hakone\IndexNow\Exception;

use Exception;
use Psr\Http\Message\ResponseInterface as HttpResponse;

class IndexNowException extends Exception
{
    /**
     * @readonly
     * @var HttpResponse
     */
    public $response;

    /**
     * @readonly
     * @var int
     */
    public $statusCode;

    public function __construct(string $message, HttpResponse $response)
    {
        parent::__construct($message);

        $this->statusCode = $response->getStatusCode();
        $this->response = $response;
    }
}
