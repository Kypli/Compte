<?php

namespace App\EventSubscriber;

use App\Service\CookieService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class RegisteredUserLoginSubscriber implements EventSubscriberInterface
{
	public function __construct(
		private readonly CookieService $cookieService,
	){
	}

	public function onLoginSuccess(LoginSuccessEvent $event): void
	{
		$user = $event->getUser();
		$response = $event->getResponse();

		if (null === $response){
			return;
		}

		$this->cookieService->addRegisteredUserCookieForUser($response, $user);
	}

	public static function getSubscribedEvents(): array
	{
		return [
			LoginSuccessEvent::class => 'onLoginSuccess',
		];
	}
}
