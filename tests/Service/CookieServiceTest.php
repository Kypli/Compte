<?php

namespace App\Tests\Service;

use App\Service\CookieService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

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
}
