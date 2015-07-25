<?php

$mimes = array(
    'c' => 'text/plain',
    'cc' => 'text/plain',
    'cpp' => 'text/plain',
    'c++' => 'text/plain',
    'dtd' => 'text/plain',
    'h' => 'text/plain',
    'log' => 'text/plain',
    'rng' => 'text/plain',
    'txt' => 'text/plain',
    'xsd' => 'text/plain',
    'php' => Phar::PHP, // parse as PHP
    'inc' => Phar::PHP, // parse as PHP
    'avi' => 'video/avi',
    'bmp' => 'image/bmp',
    'css' => 'text/css',
    'gif' => 'image/gif',
    'htm' => 'text/html',
    'html' => 'text/html',
    'htmls' => 'text/html',
    'ico' => 'image/x-ico',
    'jpe' => 'image/jpeg',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'js' => 'application/x-javascript',
    'midi' => 'audio/midi',
    'mid' => 'audio/midi',
    'mod' => 'audio/mod',
    'mov' => 'movie/quicktime',
    'mp3' => 'audio/mp3',
    'mpg' => 'video/mpeg',
    'mpeg' => 'video/mpeg',
    'pdf' => 'application/pdf',
    'png' => 'image/png',
    'swf' => 'application/shockwave-flash',
    'tif' => 'image/tiff',
    'tiff' => 'image/tiff',
    'wav' => 'audio/wav',
    'xbm' => 'image/xbm',
    'xml' => 'text/xml',
);

Phar::webPhar(
    'pocs.phar',
    'web/index.php',
    'share/phar/404.php',
    $mimes
);

__HALT_COMPILER();
