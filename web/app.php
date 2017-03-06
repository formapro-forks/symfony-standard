<?php

use Symfony\Component\HttpFoundation\Request;

/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__.'/../app/autoload.php';

if ($sentryDsn = getenv('SENTRY_DSN')) {
    new Raven_Client($sentryDsn);
}

if (false == $env = getenv('SYMFONY_ENV')) {
    throw new \LogicException('Tne SYMFONY_ENV env var is not set.');
}

$debug = (bool) getenv('SYMFONY_DEBUG');

$kernel = new AppKernel($env, $debug);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
