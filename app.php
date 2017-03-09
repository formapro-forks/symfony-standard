<?php

use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeExtensionGuesser;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesser;
use Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\TerminableInterface;

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
$server = create_app_server(
    $kernel,
    getenv('SWOOLE_HOST') ?: '0.0.0.0',
    getenv('SWOOLE_PORT') ?: 80
);

$server->start();

function create_app_server(KernelInterface $kernel, $host = '127.0.0.1', $port = 80)
{
    $server = new \swoole_http_server($host, $port);
    $server->on('request', function (\swoole_http_request $swRequest, \swoole_http_response $swResponse) use ($kernel) {
        if (isset($swRequest->server['request_uri']) && is_file(__DIR__.'/web'.$swRequest->server['request_uri'])) {
            $staticFile = __DIR__.'/web'.$swRequest->server['request_uri'];

            // do not allow to go out of web dir
            if (false === strpos($staticFile, '..')) {
                $guesser = MimeTypeGuesser::getInstance();
                $guesser->register(new class() implements MimeTypeGuesserInterface {
                    private $mimeTypeExtensionGuesser;

                    public function __construct()
                    {
                        $this->mimeTypeExtensionGuesser = new MimeTypeExtensionGuesser();
                    }

                    /**
                     * {@inheritdoc}
                     */
                    public function guess($path)
                    {
                        return (function ($path) {
                            $ext = pathinfo($path, PATHINFO_EXTENSION);

                            $mimeTypes = array_flip($this->defaultExtensions);

                            return $mimeTypes[$ext] ?? null;
                        })->call($this->mimeTypeExtensionGuesser, $path);
                    }
                });

                $mimeType = $guesser->guess($staticFile);
                $swResponse->header('Content-Type', $mimeType);
                $swResponse->sendfile($staticFile);

                return;
            }
        }

        try {
            $sfRequest = create_request($swRequest);
            $sfResponse = $kernel->handle($sfRequest);

            send_response($swResponse, $sfResponse);

            if ($kernel instanceof TerminableInterface) {
                $kernel->terminate($sfRequest, $sfResponse);
            }
        } catch (\Throwable $e) {
            $swResponse->status(500);
            $swResponse->end((string) $e);
        }
    });

    return $server;
}

/**
 * @param \swoole_http_request $swRequest
 *
 * @return SymfonyRequest
 */
function create_request(\swoole_http_request $swRequest)
{
    $_SERVER = isset($swRequest->server) ? array_change_key_case($swRequest->server, CASE_UPPER) : [];
    if (isset($swRequest->header)) {
        $headers = [];
        foreach ($swRequest->header as $k => $v) {
            $k = str_replace('-', '_', $k);
            $headers['http_'.$k] = $v;
        }

        $_SERVER += array_change_key_case($headers, CASE_UPPER);
    }

    $_GET = isset($swRequest->get) ? $swRequest->get : [];
    $_POST = isset($swRequest->post) ? $swRequest->post : [];
    $_COOKIE = isset($swRequest->cookie) ? $swRequest->cookie : [];

    // The code below is a copy of Symfony's Request::createFromGlobals() method with some slight adjustments, like explicit set of content.

    // With the php's bug #66606, the php's built-in web server
    // stores the Content-Type and Content-Length header values in
    // HTTP_CONTENT_TYPE and HTTP_CONTENT_LENGTH fields.
    $server = $_SERVER;
    if ('cli-server' === PHP_SAPI) {
        if (array_key_exists('HTTP_CONTENT_LENGTH', $_SERVER)) {
            $server['CONTENT_LENGTH'] = $_SERVER['HTTP_CONTENT_LENGTH'];
        }
        if (array_key_exists('HTTP_CONTENT_TYPE', $_SERVER)) {
            $server['CONTENT_TYPE'] = $_SERVER['HTTP_CONTENT_TYPE'];
        }
    }

    $request = new SymfonyRequest($_GET, $_POST, [], $_COOKIE, [], $_SERVER, $swRequest->rawContent());

    if (0 === strpos($request->headers->get('CONTENT_TYPE'), 'application/x-www-form-urlencoded')
        && in_array(strtoupper($request->server->get('REQUEST_METHOD', 'GET')), ['PUT', 'DELETE', 'PATCH'])
    ) {
        parse_str($request->getContent(), $data);
        $request->request = new ParameterBag($data);
    }

    return $request;
}

function send_response(\swoole_http_response $swResponse, SymfonyResponse $response)
{
    $swResponse->status($response->getStatusCode());

    foreach ($response->headers->getCookies() as $cookie) {
        $swResponse->header('Set-Cookie', $cookie);
    }
    foreach ($response->headers as $name => $values) {
        $name = implode('-', array_map('ucfirst', explode('-', $name)));
        foreach ($values as $value) {
            $swResponse->header($name, $value);
        }
    }

    $swResponse->end($response->getContent());
}
