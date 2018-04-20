<?php

namespace App\EventSubscriber;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $e = $event->getException();
        $statusCode = 500;
        if ( method_exists($e,"getStatusCode") )
            $statusCode = $e->getStatusCode();
        $resp = new JsonResponse([
            "status"  => $statusCode,
            "message" => $e->getMessage()
            ],$statusCode);

        $event->setResponse($resp);

    }

    public static function getSubscribedEvents()
    {
        return [
           'kernel.exception' => 'onKernelException',
        ];
    }
}
