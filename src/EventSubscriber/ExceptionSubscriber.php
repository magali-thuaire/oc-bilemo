<?php

namespace App\EventSubscriber;

use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Api\ResponseFactory;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Pagerfanta\Exception\NotValidCurrentPageException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;

class ExceptionSubscriber implements EventSubscriberInterface
{
    private ParameterBagInterface $parameterBag;
    private ResponseFactory $responseFactory;

    public function __construct(
        ParameterBagInterface $parameterBag,
        ResponseFactory $responseFactory
    ) {
        $this->parameterBag = $parameterBag;
        $this->responseFactory = $responseFactory;
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        if (!str_contains($request->getPathInfo(), '/api')) {
            return;
        }

        $e = $event->getThrowable();

        if ($e instanceof HttpExceptionInterface) {
            $statusCode = $e->getStatusCode();
        } elseif ($e instanceof NotValidCurrentPageException) {
            $statusCode = Response::HTTP_NOT_FOUND;
        } elseif ($e instanceof UnexpectedValueException) {
            $statusCode = Response::HTTP_UNSUPPORTED_MEDIA_TYPE;
        } else {
            $statusCode = 500;
        }

        if ($e instanceof ApiProblemException) {
            $apiProblem = $e->getApiProblem();
        } else {
            $apiProblem = new ApiProblem($statusCode);

            if ($e instanceof HttpExceptionInterface && $apiProblem->getStatusCode() != Response::HTTP_NOT_FOUND) {
                $apiProblem->set('detail', $e->getMessage());
            }
        }

        $response = $this->responseFactory->createResponse($apiProblem);

        $event->setResponse($response);
    }

    public function onAuthenticationFailure(AuthenticationFailureEvent $event)
    {
        $authException = $event->getException();

        $apiProblem = new ApiProblem($authException ? Response::HTTP_UNAUTHORIZED : 500);
        $message = $authException ? $authException->getMessageKey() : 'Missing credentials';
        $apiProblem->set('detail', $message);

        $response = $this->responseFactory->createResponse($apiProblem);

        $event->setResponse($response);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
            Events::AUTHENTICATION_FAILURE => 'onAuthenticationFailure',
            Events::JWT_INVALID => 'onAuthenticationFailure',
            Events::JWT_EXPIRED => 'onAuthenticationFailure',
            Events::JWT_NOT_FOUND => 'onAuthenticationFailure',
        ];
    }
}
