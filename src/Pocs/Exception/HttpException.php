<?php

namespace Pocs\Exception;

class HttpException extends \Exception implements PocsExceptionInterface
{
    /**
     * @var string
     */
    private $statusCode;

    /**
     * @var array
     */
    private $headers;

    /**
     * @param string $statusCode
     * @param null|string $message
     * @param \Exception|null $previous
     * @param array $headers
     * @param int $code
     */
    public function __construct(
        $statusCode,
        $message = null,
        \Exception $previous = null,
        array $headers = array(),
        $code = 0
    ) {
        $this->statusCode = $statusCode;
        $this->headers = $headers;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
