<?php

namespace Pocs\Exception;

class MethodNotAllowedHttpException extends HttpException
{
    /**
     * @param array $allow
     * @param string $message
     * @param \Exception|null $previous
     * @param int $code
     */
    public function __construct(array $allow, $message = null, \Exception $previous = null, $code = 0)
    {
        $headers = array('Allow' => strtoupper(implode(', ', $allow)));
        parent::__construct(405, $message, $previous, $headers, $code);
    }
}
