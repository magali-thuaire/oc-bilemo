<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class HttpCacheService
{
    public function cache(JsonResponse $jsonResponse, Request $request) : JsonResponse
    {
        $jsonResponse->setEtag(md5($jsonResponse->getContent()));
        $jsonResponse->setPublic();
        $jsonResponse->isNotModified($request);

        return $jsonResponse;
    }
}