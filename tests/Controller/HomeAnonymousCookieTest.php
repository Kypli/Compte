<?php

namespace App\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

class HomeAnonymousCookieTest extends KernelTestCase
{
	public function testRememberedAnonymousVisitorActionsAreDisplayedWithoutLogout(): void
	{
		self::bootKernel();

		$request = Request::create('/');
		$request->setSession(new Session(new MockArraySessionStorage()));
		self::getContainer()->get(RequestStack::class)->push($request);

		$twig = self::getContainer()->get(Environment::class);
		$testUser = new class {
			public function getId(): int
			{
				return 123;
			}
		};

		$html = $twig->render('home/connexion/_index.html.twig', [
			'test_user' => $testUser,
			'test_session_remaining' => '29 jours',
			'last_username' => '',
		]);

		self::assertStringContainsString('Aller au tableau de bord', $html);
		self::assertStringContainsString("S'enregistrer", $html);
		self::assertStringContainsString('home-button-register', $html);
		self::assertStringContainsString('Supprimer ma session de test', $html);
		self::assertStringContainsString('data-confirm-test-delete', $html);
		self::assertStringContainsString('Supprimer la session de test ?', $html);
		self::assertStringContainsString('Il vous reste 29 jours avant la fin de votre session de test.', $html);
		self::assertStringContainsString("N'oubliez pas", $html);
		self::assertStringContainsString('de vous enregistrer pour ne pas perdre votre travail.', $html);
		self::assertStringContainsString('/user/anonyme/dashboard/123', $html);
		self::assertStringContainsString('/user/anonyme/session-test/delete/123', $html);
		self::assertStringNotContainsString('onsubmit="return confirm', $html);
		self::assertStringNotContainsString('se déconnecter', $html);
		self::assertStringNotContainsString('name="_username"', $html);
	}
}
