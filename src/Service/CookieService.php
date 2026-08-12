<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class CookieService
{
	private const ANONYME_ID_COOKIE = 'anonyme';
	private const ANONYME_PASSWORD_COOKIE = 'anonyme_mdp';
	private const ANONYME_COOKIE_DURATION = '+30 days';
	private const ANONYME_EXPIRES_AT_COOKIE = 'anonyme_expires_at';
	private const REGISTERED_USER_COOKIE = 'compte_user_registered';
	private const REGISTERED_USER_COOKIE_DURATION = '+1 year';

	/**
	 * Ajoute les cookies du visiteur anonyme sur la reponse renvoyee au navigateur.
	 */
	public function addAnonymousTestSessionCookies(Response $response, $user_id, $user_psw): Response
	{
		$expires = new \DateTimeImmutable(self::ANONYME_COOKIE_DURATION);

		$response->headers->setCookie(
			Cookie::create(self::ANONYME_ID_COOKIE, (string) $user_id)
				->withExpires($expires)
				->withPath('/')
				->withHttpOnly(true)
				->withSameSite(Cookie::SAMESITE_LAX)
		);
		$response->headers->setCookie(
			Cookie::create(self::ANONYME_PASSWORD_COOKIE, $user_psw)
				->withExpires($expires)
				->withPath('/')
				->withHttpOnly(true)
				->withSameSite(Cookie::SAMESITE_LAX)
		);
		$response->headers->setCookie(
			Cookie::create(self::ANONYME_EXPIRES_AT_COOKIE, (string) $expires->getTimestamp())
				->withExpires($expires)
				->withPath('/')
				->withHttpOnly(true)
				->withSameSite(Cookie::SAMESITE_LAX)
		);

		return $response;
	}

	public function addCookie(Response $response, $user_id, $user_psw): Response
	{
		return $this->addAnonymousTestSessionCookies($response, $user_id, $user_psw);
	}

	public function addRegisteredUserCookie(Response $response): Response
	{
		$response->headers->setCookie(
			Cookie::create(self::REGISTERED_USER_COOKIE, '1')
				->withExpires(new \DateTimeImmutable(self::REGISTERED_USER_COOKIE_DURATION))
				->withPath('/')
				->withHttpOnly(true)
				->withSameSite(Cookie::SAMESITE_LAX)
		);

		return $response;
	}

	public function addRegisteredUserCookieForUser(Response $response, UserInterface $user): Response
	{
		return !$user instanceof User || $user->getAnonyme()
			? $response
			: $this->addRegisteredUserCookie($response)
		;
	}

	public function hasRegisteredUserCookie(Request $request): bool
	{
		return '1' === $request->cookies->get(self::REGISTERED_USER_COOKIE);
	}

	public function canCreateAnonymousTestSession(Request $request): bool
	{
		return !$this->hasRegisteredUserCookie($request);
	}

	public function getRegisteredUserTestSessionBlockedMessage(): string
	{
		return 'Vous avez déjà un compte enregistré sur ce navigateur. Connectez-vous pour continuer.';
	}

	public function getAnonymousTestSessionUser(Request $request, UserRepository $userRepository): ?User
	{
		$anonymousId = $this->getAnonymousTestSessionUserId($request);

		if (null === $anonymousId){
			return null;
		}

		$user = $userRepository->find($anonymousId);

		return $user instanceof User && $this->isValidAnonymousTestSession($request, $user)
			? $user
			: null
		;
	}

	public function getAnonymousTestSessionUserId(Request $request): ?int
	{
		$anonymousId = $request->cookies->get(self::ANONYME_ID_COOKIE);

		if (null === $anonymousId || !ctype_digit((string) $anonymousId)){
			return null;
		}

		return (int) $anonymousId;
	}

	public function isValidAnonymousTestSession(Request $request, User $user): bool
	{
		return $user->getAnonyme()
			&& (string) $user->getId() === (string) $this->getAnonymousTestSessionUserId($request)
			&& $user->getPassword() === $request->cookies->get(self::ANONYME_PASSWORD_COOKIE)
		;
	}

	public function getAnonymousCookieRemainingTimeLabel(Request $request): string
	{
		$expiresAt = $request->cookies->get(self::ANONYME_EXPIRES_AT_COOKIE);

		if (null === $expiresAt || !ctype_digit((string) $expiresAt)){
			return 'moins d\'une minute';
		}

		$remainingSeconds = max(0, (int) $expiresAt - time());

		if ($remainingSeconds >= 86400){
			$days = (int) ceil($remainingSeconds / 86400);

			return $days.' jour'.($days > 1 ? 's' : '');
		}

		if ($remainingSeconds >= 3600){
			$hours = max(1, (int) floor($remainingSeconds / 3600));

			return $hours.' heure'.($hours > 1 ? 's' : '');
		}

		if ($remainingSeconds >= 60){
			$minutes = max(1, (int) floor($remainingSeconds / 60));

			return $minutes.' minute'.($minutes > 1 ? 's' : '');
		}

		return 'moins d\'une minute';
	}

	/**
	 * Retire les cookies du visiteur anonyme sur la reponse renvoyee au navigateur.
	 */
	public function removeAnonymousTestSessionCookies(Response $response): Response
	{
		$response->headers->clearCookie(self::ANONYME_ID_COOKIE, '/');
		$response->headers->clearCookie(self::ANONYME_PASSWORD_COOKIE, '/');
		$response->headers->clearCookie(self::ANONYME_EXPIRES_AT_COOKIE, '/');

		return $response;
	}

	public function removeAllCookies(Response $response, Request $request): Response
	{
		foreach ($request->cookies->keys() as $cookieName){
			$response->headers->clearCookie($cookieName, '/');
		}

		$this->removeAnonymousTestSessionCookies($response);
		$response->headers->clearCookie(self::REGISTERED_USER_COOKIE, '/');

		return $response;
	}

	public function removeCookie(Response $response): Response
	{
		return $this->removeAnonymousTestSessionCookies($response);
	}
}
