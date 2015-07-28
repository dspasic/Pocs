<?php

require dirname(__DIR__) . '/vendor/autoload.php';


if (false === extension_loaded('Zend OPcache')) {
    die("Module Zend OPcache is not loaded");
}

$pocsConfigFile = str_replace(
    'phar://',
    '',
    dirname(dirname(__DIR__)). '/pocs.config.php'
);

if (file_exists($pocsConfigFile)) {
    include $pocsConfigFile;

    if (defined('POCS_AUTH_USER') && defined('POCS_AUTH_PW')) {
        $user = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
        $pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null;

        if (false === (isset($user, $pass) && [POCS_AUTH_USER, POCS_AUTH_PW] === [$user, $pass])) {
            include dirname(__DIR__) . '/share/templates/401.php';
            exit;
        }
    }
}

$dispatcher = FastRoute\simpleDispatcher(function(FastRoute\RouteCollector $r) {
    $r->addRoute('GET', '/', 'home');
//    $r->addRoute('GET', '/user/{id:\d+}', 'handler1');
//    $r->addRoute('GET', '/user/{id:\d+}/{name}', 'handler2');
});

$strip = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
$uri = str_replace($strip, '', $_SERVER['REQUEST_URI']);
if (empty($uri)) {
    $uri = '/';
}
$routeInfo = $dispatcher->dispatch($_SERVER['REQUEST_METHOD'], $uri);

switch ($routeInfo[0]) {
    case FastRoute\Dispatcher::NOT_FOUND:
        // ... 404 Not Found
        break;
    case FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
        $allowedMethods = $routeInfo[1];
        methodNotAllowed($allowedMethods);
        break;
    case FastRoute\Dispatcher::FOUND:
        $routeInfo[1](...$routeInfo[2]);
        break;
}

function home()
{
    include dirname(__DIR__) . '/share/templates/index.php';
}

function methodNotAllowed(array $allowedMethods)
{
    http_response_code(405);
    echo '<p>Allowed methods are [' . implode(',', $allowedMethods) . ']</p>';
}
