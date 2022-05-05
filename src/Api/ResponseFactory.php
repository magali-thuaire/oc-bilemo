<?php

namespace App\Api;

use Symfony\Component\HttpFoundation\JsonResponse;

class ResponseFactory
{
    public function createResponse(ApiProblem $apiProblem): JsonResponse
    {
        $data = $apiProblem->toArray();
        if ($data['type'] != 'about:blank') {
            $data['type'] = 'https://localhost:8000/docs/errors#' . $data['type'];
        }

        return new JsonResponse(
            $data,
            $apiProblem->getStatusCode(),
            [
                'content_type' => 'application/problem+json'
            ]
        );
    }
}