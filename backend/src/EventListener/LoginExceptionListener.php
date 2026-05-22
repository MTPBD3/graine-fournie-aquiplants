<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::EXCEPTION)]
class LoginExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $exception = $event->getThrowable();

        if (!$exception instanceof BadRequestHttpException) {
            return;
        }

        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api/login')) {
            return;
        }

        // "must be provided" (field absent) → 400 passthrough
        // "must be a non-empty string" (field present but empty) → 401
        if (!str_contains($exception->getMessage(), 'must be a non-empty string')) {
            return;
        }

        $event->setResponse(new JsonResponse(['message' => 'Invalid credentials.'], 401));
    }
}
