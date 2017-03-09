<?php

use Makasim\Swoole\HttpFactory;

/** @var \Composer\Autoload\ClassLoader $loader */
$loader = require __DIR__.'/app/autoload.php';

if ($sentryDsn = getenv('SENTRY_DSN')) {
    $client = new Raven_Client($sentryDsn, [
        'curl' => 'async',
        'extra' => [
            'app_id' => getenv('APP_ID'),
        ],
        'environment' => getenv('SYMFONY_ENV'),
    ]);

    $errorHandler = new Raven_ErrorHandler($client);
    $errorHandler->registerExceptionHandler();
    $errorHandler->registerErrorHandler();
    $errorHandler->registerShutdownFunction();
}

if (false == $env = getenv('SYMFONY_ENV')) {
    throw new \LogicException('Tne SYMFONY_ENV env var is not set.');
}
$debug = (bool) getenv('SYMFONY_DEBUG');

$kernel = new AppKernel($env, $debug);
$kernel->boot();
$server = HttpFactory::createServer(
    $kernel,
    getenv('SWOOLE_HOST') ?: '0.0.0.0',
    getenv('SWOOLE_PORT') ?: 80
);

$server->start();
