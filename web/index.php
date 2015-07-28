<?php

require dirname(__DIR__) . '/vendor/autoload.php';

if (false === extension_loaded('Zend OPcache')) {
    die("Module Zend OPcache is not loaded");
}

function home()
{
    include dirname(__DIR__) . '/share/templates/index.php';
}

function handleHttpException(\Pocs\Exception\HttpException $exception)
{
    http_response_code($exception->getStatusCode());
    foreach ($exception->getHeaders() as $header => $value) {
        header(implode(':', [$header, $value]), true);
    }
    $templatePath = dirname(__DIR__)  . sprintf('/share/templates/%d.php', $exception->getStatusCode());
    if (is_file($templatePath)) {
        include $templatePath;
    } else {
        echo '<p>Ooops</p>';
    }
}

try  {
    (new \Pocs\FrontController())
        ->addPreDispatcher(function() {
            $pocsConfigFile = str_replace(
                'phar://',
                '',
                dirname(dirname(__DIR__)). '/pocs.config.php'
            );

            if (is_file($pocsConfigFile)) {
                include $pocsConfigFile;

                if (defined('POCS_AUTH_USER') && defined('POCS_AUTH_PW')) {
                    $user = isset($_SERVER['PHP_AUTH_USER']) ? $_SERVER['PHP_AUTH_USER'] : null;
                    $pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : null;

                    if (false === (isset($user, $pass) && [POCS_AUTH_USER, POCS_AUTH_PW] === [$user, $pass])) {
                        throw new \Pocs\Exception\UnauthorizedHttpException('Basic realm="Pocs"');
                    }
                }
            }
        })
        ->routeDefinitionsCallback(function(FastRoute\RouteCollector $r) {
            $r->addRoute('GET', '/', 'home');
        })
        ->dispatch();
} catch (Pocs\Exception\HttpException $e) {
   handleHttpException($e);
}
