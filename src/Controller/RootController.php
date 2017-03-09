<?php

namespace App\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class RootController
{
    /**
     * @Route("/", name="root")
     */
    public function __invoke()
    {
        return new JsonResponse(['status' => 'OK']);
    }
}
