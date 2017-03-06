<?php
namespace App\Tests;

class AppKernel extends \AppKernel
{
    /**
     * {@inheritDoc}
     */
    protected function initializeContainer()
    {
        static $first = true;

        $debug = $this->debug;

        if (!$first) {
            // disable debug mode on all but the first initialization
            $this->debug = false;
        }

        // will not work with --process-isolation
        $first = false;

        try {
            parent::initializeContainer();
        } catch (\Exception $e) {
            $this->debug = $debug;

            throw $e;
        }

        $this->debug = $debug;
    }
}
