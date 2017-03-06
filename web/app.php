<?php

use Symfony\Component\HttpFoundation\Request;

if (false == $env = getenv('SYMFONY_ENV')) {
    throw new \LogicException('Tne SYMFONY_ENV env var is not set.');
}

$debug = (bool) getenv('SYMFONY_DEBUG');

/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__.'/../app/autoload.php';

$kernel = new AppKernel($env, $debug);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
