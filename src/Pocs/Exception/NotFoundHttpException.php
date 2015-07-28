<?php

namespace Pocs\Exception;

class NotFoundHttpException extends HttpException
{
    /**
     * @param null|string $message
     * @param \Exception|null $previous
     * @param int $code
     */
    public function __construct($message = null, \Exception $previous = null, $code = 0)
    {
        parent::__construct(404, $message, $previous, array(), $code);
    }
}
