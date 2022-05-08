<?php

namespace App\Api;

use Symfony\Component\HttpFoundation\JsonResponse;

class ResponseFactory
{
    public function createResponse(ApiProblem $apiProblem): JsonResponse
    {
        $data = $apiProblem->toArray();

        return new JsonResponse(
            $data,
            $apiProblem->getStatusCode(),
            [
                'content-type' => 'application/problem+json'
            ]
        );
    }
}