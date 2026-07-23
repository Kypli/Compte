<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CookieService
{
	private const ANONYME_COOKIE_DURATION = '+30 days';
	private const ANONYME_EXPIRES_AT_COOKIE = 'anonyme_expires_at';

	/**
	 * Ajoute les cookies du visiteur anonyme sur la reponse renvoyee au navigateur.
	 */
	public function addCookie(Response $response, $user_id, $user_psw): Response
	{
		$expires = new \DateTimeImmutable(self::ANONYME_COOKIE_DURATION);

		$response->headers->setCookie(
			Cookie::create('anonyme', (string) $user_id)
				->withExpires($expires)
				->withPath('/')
				->withHttpOnly(true)
				->withSameSite(Cookie::SAMESITE_LAX)
		);
		$response->headers->setCookie(
			Cookie::create('anonyme_mdp', $user_psw)
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

	public function getAnonymousCookieRemainingTimeLabel(Request $request): string
	{
		$expiresAt = $request->cookies->get(self::ANONYME_EXPIRES_AT_COOKIE);

		if (null === $expiresAt || !ctype_digit((string) $expiresAt)){
			$expiresAt = (new \DateTimeImmutable(self::ANONYME_COOKIE_DURATION))->getTimestamp();
		}

		$remainingSeconds = max(0, (int) $expiresAt - time());

		if ($remainingSeconds >= 86400){
			$days = (int) ceil($remainingSeconds / 86400);

			return $days.' jour'.($days > 1 ? 's' : '');
		}

		if ($remainingSeconds >= 3600){
			$hours = (int) ceil($remainingSeconds / 3600);

			return $hours.' heure'.($hours > 1 ? 's' : '');
		}

		if ($remainingSeconds >= 60){
			$minutes = (int) ceil($remainingSeconds / 60);

			return $minutes.' minute'.($minutes > 1 ? 's' : '');
		}

		return 'moins d\'une minute';
	}

	/**
	 * Retire les cookies du visiteur anonyme sur la reponse renvoyee au navigateur.
	 */
	public function removeCookie(Response $response): Response
	{
		$response->headers->clearCookie('anonyme', '/');
		$response->headers->clearCookie('anonyme_mdp', '/');
		$response->headers->clearCookie(self::ANONYME_EXPIRES_AT_COOKIE, '/');

		return $response;
	}
}
