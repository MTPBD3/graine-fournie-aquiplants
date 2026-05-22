<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationFailureEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Response\JWTAuthenticationFailureResponse;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;

#[AsEventListener(event: 'lexik_jwt_authentication.on_authentication_failure')]
class LoginThrottlingResponseListener
{
    public function __invoke(AuthenticationFailureEvent $event): void
    {
        if (!$event->getException() instanceof TooManyLoginAttemptsAuthenticationException) {
            return;
        }

        $event->setResponse(new JWTAuthenticationFailureResponse('Too many login attempts. Please try again later.', 429));
    }
}
