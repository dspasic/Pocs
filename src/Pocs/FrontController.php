<?php

namespace Pocs;

use FastRoute;
use Pocs\Exception\MethodNotAllowedHttpException;
use Pocs\Exception\NotFoundHttpException;

class FrontController
{
    /**
     * @var array
     */
    private $preDispatchers = [];

    /**
     * @var FastRoute\Dispatcher
     */
    private $dispatcher;

    /**
     * @var string
     */
    private $frontControllerPath;

    /**
     * @param string $frontControllerPath
     */
    public function __construct($frontControllerPath)
    {
        $this->frontControllerPath = $frontControllerPath;
    }

    /**
     * @param callable $preDispatcher
     * @return $this
     */
    public function addPreDispatcher(callable $preDispatcher)
    {
        $this->preDispatchers[] = $preDispatcher;

        return $this;
    }

    /**
     * @param callable $callbacks
     * @return $this
     */
    public function routeDefinitionsCallback(callable $callbacks)
    {
        $this->dispatcher = FastRoute\simpleDispatcher($callbacks);

        return $this;
    }

    public function dispatch()
    {
        $skip = $_SERVER['SCRIPT_NAME'] . $this->frontControllerPath;
        $uri = str_replace($skip, '', $_SERVER['REQUEST_URI']);
        if (empty($uri)) {
            $uri = '/';
        }

        foreach ($this->preDispatchers as $preDispatcher) {
            $preDispatcher();
        }

        $routeInfo = $this->dispatcher->dispatch($_SERVER['REQUEST_METHOD'], $uri);

        switch ($routeInfo[0]) {
            case FastRoute\Dispatcher::NOT_FOUND:
                throw new NotFoundHttpException();
                break;
            case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                throw new MethodNotAllowedHttpException($allowedMethods);
                break;
            case FastRoute\Dispatcher::FOUND:
                $routeInfo[1](...$routeInfo[2]);
                break;
        }
    }
}
