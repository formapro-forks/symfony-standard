<?php
namespace App;

use Symfony\Component\HttpFoundation\JsonResponse;

class RootController
{
    public function __invoke()
    {
        return new JsonResponse(['status' => 'OK']);
    }
}
