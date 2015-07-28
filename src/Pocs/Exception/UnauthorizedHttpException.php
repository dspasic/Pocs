<?php

namespace Pocs\Exception;

class UnauthorizedHttpException extends HttpException
{
    /**
     * @param string $challenge
     * @param null $message
     * @param \Exception|null $previous
     * @param int $code
     */
    public function __construct($challenge, $message = null, \Exception $previous = null, $code = 0)
    {
        $headers = ['WWW-Authenticate' => $challenge];
        parent::__construct(401, $message, $previous, $headers, $code);
    }
}
