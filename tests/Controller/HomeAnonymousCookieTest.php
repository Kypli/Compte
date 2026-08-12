<?php

namespace App\Tests\Controller;

use App\Controller\LoginController;
use App\Entity\User;
use App\Entity\UserProfil;
use App\Form\UserType;
use App\Service\UserRegistrationValidator;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Twig\Environment;

class HomeAnonymousCookieTest extends KernelTestCase
{
	public function testLoginFormSeparatesLoginAndAccountCreationAndCanRememberUser(): void
	{
		self::bootKernel();

		$request = Request::create('/');
		$request->setSession(new Session(new MockArraySessionStorage()));
		self::getContainer()->get(RequestStack::class)->push($request);

		$twig = self::getContainer()->get(Environment::class);
		$html = $twig->render('home/connexion/_form_login.html.twig', [
			'last_username' => '',
		]);

		self::assertStringContainsString('/login-or-register', $html);
		self::assertStringContainsString('Se connecter', $html);
		self::assertStringContainsString('Créer un compte', $html);
		self::assertStringContainsString('placeholder="Login, Email ou Pseudo"', $html);
		self::assertStringContainsString('name="login_action" value="login"', $html);
		self::assertStringContainsString('name="login_action" value="register"', $html);
		self::assertStringContainsString('name="_email"', $html);
		self::assertStringContainsString('type="email"', $html);
		self::assertStringContainsString('name="_password_confirm"', $html);
		self::assertStringContainsString('data-register-fields', $html);
		self::assertStringContainsString('data-password-toggle', $html);
		self::assertStringContainsString('home-password-icon', $html);
		self::assertStringContainsString('home-password-slash', $html);
		self::assertStringContainsString('minlength="5"', $html);
		self::assertStringContainsString('minlength="6"', $html);
		self::assertStringContainsString('pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,}"', $html);
		self::assertStringContainsString('Minimum 6 caractères, avec au moins 1 majuscule, 1 minuscule et 1 chiffre.', $html);
		self::assertStringContainsString('value="login" formnovalidate', $html);
		self::assertStringContainsString('name="_remember_me"', $html);
		self::assertStringContainsString('Se souvenir de moi', $html);
		self::assertStringContainsString('/mot-de-passe-oublie', $html);
		self::assertStringContainsString('Mot de passe oublie ?', $html);
		self::assertStringContainsString('/connect/google', $html);
		self::assertStringContainsString('Continuer avec Google', $html);
		self::assertStringContainsString('home-login-separator', $html);
		self::assertStringContainsString('home-google-icon', $html);
		self::assertStringContainsString('#4285F4', $html);
		self::assertStringContainsString('OU', $html);
		self::assertStringContainsString('home-google-connect', $html);
		self::assertStringContainsString('home-login-submit', $html);
		self::assertStringNotContainsString('radius-button', $html);
		self::assertStringNotContainsString('Se connecter / créer un compte', $html);
	}

	public function testFallbackLoginFormDisplaysGoogleAccountCreation(): void
	{
		$html = file_get_contents(__DIR__.'/../../templates/security/login.html.twig');

		self::assertStringContainsString("path('connect_google_start')", $html);
		self::assertStringContainsString('Créer un compte avec Google', $html);
		self::assertStringContainsString('home-google-connect', $html);
	}

	public function testAnonymousEditFormUsesAccountCreationLayoutOnly(): void
	{
		self::bootKernel();

		$request = Request::create('/user/edit/123');
		$request->setSession(new Session(new MockArraySessionStorage()));
		self::getContainer()->get(RequestStack::class)->push($request);

		$user = (new User())
			->setAnonyme(true)
			->setUserName('Visiteur123')
		;
		$user->setProfil(new UserProfil());

		$form = self::getContainer()
			->get(FormFactoryInterface::class)
			->create(UserType::class, $user)
		;
		$form
			->remove('profil')
			->remove('admin')
			->remove('anonyme')
			->remove('ip')
			->remove('commentaire')
		;

		$html = self::getContainer()->get(Environment::class)->render('user/_form_register_anonymous.html.twig', [
			'form' => $form->createView(),
		]);

		self::assertStringContainsString('/connect/google', $html);
		self::assertStringContainsString('Continuer avec Google', $html);
		self::assertStringContainsString('user-google-icon', $html);
		self::assertStringContainsString('#4285F4', $html);
		self::assertStringContainsString('home-login-separator', $html);
		self::assertStringContainsString('OU', $html);
		self::assertStringContainsString('name="user[userName]"', $html);
		self::assertStringContainsString('name="user[email]"', $html);
		self::assertStringContainsString('type="email"', $html);
		self::assertStringContainsString('name="user[password]"', $html);
		self::assertStringContainsString('name="user[passwordConfirm]"', $html);
		self::assertStringContainsString('data-password-toggle', $html);
		self::assertStringContainsString('home-password-slash', $html);
		self::assertStringContainsString('S&#039;enregistrer', $html);
		self::assertStringNotContainsString('Se connecter', $html);
		self::assertStringNotContainsString('Se souvenir de moi', $html);
		self::assertStringNotContainsString('Mot de passe oublie', $html);
		self::assertStringNotContainsString('name="login_action"', $html);
	}

	public function testAccountCreationRequiresStrongEnoughCredentialsAndCompletionFields(): void
	{
		$registrationValidator = new UserRegistrationValidator();

		self::assertSame('Le login doit contenir au minimum 5 caractères.', $registrationValidator->getRegistrationError('abcd', 'Abc123'));
		self::assertSame('Le mot de passe doit contenir au minimum 6 caractères.', $registrationValidator->getRegistrationError('abcde', 'Abc12'));
		self::assertSame('Le mot de passe doit contenir au minimum 1 majuscule.', $registrationValidator->getRegistrationError('abcde', 'abc123'));
		self::assertSame('Le mot de passe doit contenir au minimum 1 minuscule.', $registrationValidator->getRegistrationError('abcde', 'ABC123'));
		self::assertSame('Le mot de passe doit contenir au minimum 1 chiffre.', $registrationValidator->getRegistrationError('abcde', 'Abcdef'));
		self::assertNull($registrationValidator->getRegistrationError('abcde', 'Abc123'));
		self::assertSame('Merci de renseigner un email.', $registrationValidator->getRegistrationCompletionError('', 'Abc123', 'Abc123'));
		self::assertSame('Merci de renseigner un email valide.', $registrationValidator->getRegistrationCompletionError('invalid', 'Abc123', 'Abc123'));
		self::assertSame('Merci de confirmer le mot de passe.', $registrationValidator->getRegistrationCompletionError('pierre@example.com', 'Abc123', ''));
		self::assertSame('Les mots de passe ne correspondent pas.', $registrationValidator->getRegistrationCompletionError('pierre@example.com', 'Abc123', 'Abc124'));
		self::assertNull($registrationValidator->getRegistrationCompletionError('pierre@example.com', 'Abc123', 'Abc123'));
	}

	public function testAnonymousRegistrationFormFieldsExposeBrowserConstraints(): void
	{
		self::bootKernel();

		$user = new User();
		$user
			->setAnonyme(true)
			->setUserName('Visiteur')
			->setPassword('temporary-password')
		;

		$form = self::getContainer()->get(FormFactoryInterface::class)->create(UserType::class, $user);
		$view = $form->createView();

		self::assertSame(5, $view['userName']->vars['attr']['minlength']);
		self::assertSame(6, $view['password']->vars['attr']['minlength']);
		self::assertSame('(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,}', $view['password']->vars['attr']['pattern']);
		self::assertStringContainsString('1 majuscule', $view['password']->vars['attr']['title']);
	}

	public function testLoginErrorsCanKeepLastUsernameInSession(): void
	{
		$request = Request::create('/login-or-register');
		$request->setSession(new Session(new MockArraySessionStorage()));
		$method = (new \ReflectionClass(LoginController::class))->getMethod('rememberLastUsername');

		$method->setAccessible(true);
		$method->invoke(new LoginController(), $request, 'PierreTest');

		self::assertSame('PierreTest', $request->getSession()->get(SecurityRequestAttributes::LAST_USERNAME));
	}

	public function testRegisteredUserCookieHidesTestApplicationButtonAndSeparateCreateAccountButton(): void
	{
		self::bootKernel();

		$request = Request::create('/');
		$request->setSession(new Session(new MockArraySessionStorage()));
		self::getContainer()->get(RequestStack::class)->push($request);

		$twig = self::getContainer()->get(Environment::class);
		$html = $twig->render('home/index.html.twig', [
			'error' => null,
			'last_username' => '',
			'anonyme' => null,
			'test_user' => null,
			'test_session_remaining' => null,
			'can_create_anonymous_test_session' => false,
		]);

		self::assertStringNotContainsString("Tester l'application sans compte", $html);
		self::assertStringNotContainsString('Créer un nouveau compte', $html);
		self::assertStringContainsString('/login-or-register', $html);
		self::assertStringContainsString('Se connecter', $html);
		self::assertStringContainsString('Créer un compte', $html);
	}

	public function testHomeTestButtonUsesFullTextClass(): void
	{
		self::bootKernel();

		$request = Request::create('/');
		$request->setSession(new Session(new MockArraySessionStorage()));
		self::getContainer()->get(RequestStack::class)->push($request);

		$twig = self::getContainer()->get(Environment::class);
		$html = $twig->render('home/index.html.twig', [
			'error' => null,
			'last_username' => '',
			'anonyme' => null,
			'test_user' => null,
			'test_session_remaining' => null,
			'can_create_anonymous_test_session' => true,
		]);

		self::assertStringContainsString("Tester l'application sans compte", $html);
		self::assertStringContainsString('home-test-button', $html);
	}

	public function testTestEnvironmentDisplaysDeleteAllCookiesButton(): void
	{
		self::bootKernel();

		$request = Request::create('/');
		$request->attributes->set('_route', 'home');
		$request->setSession(new Session(new MockArraySessionStorage()));
		self::getContainer()->get(RequestStack::class)->push($request);

		$twig = self::getContainer()->get(Environment::class);
		$html = $twig->render('partiel/_footer.html.twig');

		self::assertStringContainsString('Supprimer tous les cookies', $html);
		self::assertStringContainsString('/test/cookies/delete-all', $html);
	}

	public function testDeleteAllCookiesButtonIsHiddenOutsideHome(): void
	{
		self::bootKernel();

		$request = Request::create('/dashboard/');
		$request->attributes->set('_route', 'tableau_bord');
		$request->setSession(new Session(new MockArraySessionStorage()));
		self::getContainer()->get(RequestStack::class)->push($request);

		$twig = self::getContainer()->get(Environment::class);
		$html = $twig->render('partiel/_footer.html.twig');

		self::assertStringNotContainsString('Supprimer tous les cookies', $html);
		self::assertStringNotContainsString('/test/cookies/delete-all', $html);
	}

	public function testNavbarDisplaysPlaceholderLogoOnTheRight(): void
	{
		self::bootKernel();

		$user = new User();
		$idProperty = (new \ReflectionClass(User::class))->getProperty('id');

		$idProperty->setAccessible(true);
		$idProperty->setValue($user, 123);
		$user
			->setAnonyme(false)
			->setUserName('Pierre')
			->setPassword('temporary-password')
		;
		$request = Request::create('/dashboard/');
		$request->attributes->set('_route', 'tableau_bord');
		$request->setSession(new Session(new MockArraySessionStorage()));
		self::getContainer()->get(RequestStack::class)->push($request);
		self::getContainer()
			->get(TokenStorageInterface::class)
			->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()))
		;

		$twig = self::getContainer()->get(Environment::class);
		$html = $twig->render('navbar/index.html.twig');

		self::assertStringContainsString('navbar-logo-placeholder', $html);
		self::assertStringContainsString('https://placehold.co/96x42?text=Logo', $html);
		self::assertStringContainsString('alt="Logo"', $html);
	}

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

	public function testForgotPasswordPagesExposeRequestAndResetForms(): void
	{
		self::bootKernel();

		$request = Request::create('/mot-de-passe-oublie');
		$request->setSession(new Session(new MockArraySessionStorage()));
		self::getContainer()->get(RequestStack::class)->push($request);

		$twig = self::getContainer()->get(Environment::class);
		$html = $twig->render('security/forgot_password.html.twig', [
			'last_identifier' => '',
		]);

		self::assertStringContainsString('/mot-de-passe-oublie', $html);
		self::assertStringContainsString('name="identifier"', $html);
		self::assertStringContainsString('Recevoir le lien', $html);

		$html = $twig->render('security/reset_password.html.twig', [
			'token' => 'temporary-token',
		]);

		self::assertStringContainsString('/mot-de-passe-reinitialisation/temporary-token', $html);
		self::assertStringContainsString('name="_password"', $html);
		self::assertStringContainsString('name="_password_confirm"', $html);
		self::assertStringContainsString('Changer le mot de passe', $html);
	}
}
