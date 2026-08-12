<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\CookieService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CookieServiceTest extends TestCase
{
	public function testRemainingTimeShowsHoursBelowTwentyFourHours(): void
	{
		$request = Request::create('/');
		$request->cookies->set('anonyme_expires_at', (string) (time() + 23 * 3600 + 59 * 60));

		self::assertSame('23 heures', (new CookieService())->getAnonymousCookieRemainingTimeLabel($request));
	}

	public function testRemainingTimeShowsMinutesBelowOneHour(): void
	{
		$request = Request::create('/');
		$request->cookies->set('anonyme_expires_at', (string) (time() + 59 * 60 + 30));

		self::assertSame('59 minutes', (new CookieService())->getAnonymousCookieRemainingTimeLabel($request));
	}

	public function testRegisteredUserCookieCanBeSetAndDetected(): void
	{
		$service = new CookieService();
		$response = $service->addRegisteredUserCookie(new Response());
		$request = Request::create('/');

		$request->cookies->set('compte_user_registered', '1');

		self::assertNotNull($response->headers->getCookies()[0] ?? null);
		self::assertTrue($service->hasRegisteredUserCookie($request));
	}

	public function testRegisteredUserCookieBlocksAnonymousTestSessionCreation(): void
	{
		$service = new CookieService();
		$request = Request::create('/');

		self::assertTrue($service->canCreateAnonymousTestSession($request));

		$request->cookies->set('compte_user_registered', '1');

		self::assertFalse($service->canCreateAnonymousTestSession($request));
	}

	public function testAnonymousTestSessionCookiesAreCreatedTogether(): void
	{
		$response = (new CookieService())->addAnonymousTestSessionCookies(new Response(), 12, 'hashed-password');
		$cookies = [];

		foreach ($response->headers->getCookies() as $cookie){
			$cookies[$cookie->getName()] = $cookie;
		}

		self::assertArrayHasKey('anonyme', $cookies);
		self::assertArrayHasKey('anonyme_mdp', $cookies);
		self::assertArrayHasKey('anonyme_expires_at', $cookies);
		self::assertSame('12', $cookies['anonyme']->getValue());
		self::assertSame('hashed-password', $cookies['anonyme_mdp']->getValue());
		self::assertGreaterThan(time() + 29 * 86400, $cookies['anonyme_expires_at']->getValue());
	}

	public function testAnonymousTestSessionValidationIsCentralizedInCookieService(): void
	{
		$service = new CookieService();
		$request = Request::create('/');
		$user = new User();
		$property = (new \ReflectionClass(User::class))->getProperty('id');

		$property->setAccessible(true);
		$property->setValue($user, 42);
		$user
			->setAnonyme(true)
			->setUserName('Visiteur42')
			->setPassword('hashed-password')
		;
		$request->cookies->set('anonyme', '42');
		$request->cookies->set('anonyme_mdp', 'hashed-password');

		self::assertTrue($service->isValidAnonymousTestSession($request, $user));

		$request->cookies->set('anonyme_mdp', 'wrong-password');

		self::assertFalse($service->isValidAnonymousTestSession($request, $user));
	}

	public function testAnonymousTestSessionCookiesCanBeRemoved(): void
	{
		$response = (new CookieService())->removeAnonymousTestSessionCookies(new Response());
		$cookieNames = array_map(static fn ($cookie) => $cookie->getName(), $response->headers->getCookies());

		self::assertContains('anonyme', $cookieNames);
		self::assertContains('anonyme_mdp', $cookieNames);
		self::assertContains('anonyme_expires_at', $cookieNames);
	}

	public function testAllCookiesCanBeRemovedForTestEnvironment(): void
	{
		$request = Request::create('/');
		$request->cookies->set('PHPSESSID', 'session-id');
		$request->cookies->set('custom_cookie', 'value');
		$response = (new CookieService())->removeAllCookies(new Response(), $request);
		$cookieNames = array_map(static fn ($cookie) => $cookie->getName(), $response->headers->getCookies());

		self::assertContains('PHPSESSID', $cookieNames);
		self::assertContains('custom_cookie', $cookieNames);
		self::assertContains('anonyme', $cookieNames);
		self::assertContains('anonyme_mdp', $cookieNames);
		self::assertContains('anonyme_expires_at', $cookieNames);
		self::assertContains('compte_user_registered', $cookieNames);
	}
}
