<?php

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\MonologBundle\MonologBundle(),
            new Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle(),
            new Enqueue\Bundle\EnqueueBundle(),
        ];

        if (in_array($this->getEnvironment(), ['dev', 'test'], true)) {
            $bundles[] = new Symfony\Bundle\TwigBundle\TwigBundle();
            $bundles[] = new Symfony\Bundle\DebugBundle\DebugBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
        }

        return $bundles;
    }

    public function getRootDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        if ($cacheDir = getenv('SYMFONY_CACHE_DIR')) {
            return $cacheDir;
        }

        return dirname(__DIR__).'/var/cache/'.$this->getEnvironment();
    }

    public function getLogDir()
    {
        if ($logDir = getenv('SYMFONY_LOG_DIR')) {
            return $logDir;
        }

        return dirname(__DIR__).'/var/logs';
    }

    /**
     * {@inheritdoc}
     *
     * @throws \RuntimeException if a custom resource is hidden by a resource in a derived bundle
     */
    public function locateResource($name, $dir = null, $first = true)
    {
        if (0 === strpos($name, '@App')) {
            return str_replace(
                '@App',
                realpath($this->getRootDir().'/../src/'),
                $name
            );
        }

        return parent::locateResource($name, $dir, $first);
    }

    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load($this->getRootDir().'/config/config_'.$this->getEnvironment().'.yml');
    }
}
