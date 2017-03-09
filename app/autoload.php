<?php

use Composer\Autoload\ClassLoader;

/** @var ClassLoader $loader */
$loader = require __DIR__.'/../vendor/autoload.php';

Doctrine\Common\Annotations\AnnotationRegistry::registerLoader([$loader, 'loadClass']);

return $loader;
