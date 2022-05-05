<?php

namespace App\EventSubscriber;

use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ExceptionSubscriber implements EventSubscriberInterface
{
    private ParameterBagInterface $parameterBag;

    public function __construct(
        ParameterBagInterface $parameterBag,
    ) {
        $this->parameterBag = $parameterBag;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_contains($request->getPathInfo(), '/api')) {
            return;
        }

        $e = $event->getThrowable();
        $statusCode = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : '500';

        if ($statusCode == 500 && $this->parameterBag->get('kernel.debug')) {
            return;
        }

        if ($e instanceof ApiProblemException) {
            $apiProblem = $e->getApiProblem();
        } else {
            $apiProblem = new ApiProblem($statusCode);

            if ($e instanceof HttpExceptionInterface && $apiProblem->getStatusCode() != Response::HTTP_NOT_FOUND) {
                $apiProblem->set('detail', $e->getMessage());
            }
        }

        $data = $apiProblem->toArray();
        if ($data['type'] != 'about:blank') {
            $data['type'] = 'https://localhost:8000/docs/errors#' . $data['type'];
        }

        $response = new JsonResponse(
            $data,
            $apiProblem->getStatusCode(),
            [
                'content_type' => 'application/problem+json'
            ]
        );
        $event->setResponse($response);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }
}
