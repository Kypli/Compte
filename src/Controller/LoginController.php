<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserPreference;
use App\Entity\UserProfil;
use App\Repository\UserPreferenceRepository;
use App\Repository\UserProfilRepository;
use App\Repository\UserRepository;
use App\Security\UserChecker;
use App\Service\CookieService;
use App\Service\UserRegistrationValidator;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

class LoginController extends AbstractController
{
	/**
	 * @Route("/login", name="login")
	 * Se retrouve ici en cas de redirection vers le login si non connecté
	 */
	#[Route("/login", name: "login")]
	public function index(AuthenticationUtils $authenticationUtils): Response
	{
		// get the login error if there is one
		$error = $authenticationUtils->getLastAuthenticationError();

		$this->addFlash('login_info', 'Vous devez vous connecter !');

		// last username entered by the user
		$lastUsername = $authenticationUtils->getLastUsername();

		return $this->redirectToRoute('home');
	}

	#[Route("/login-or-register", name: "login_or_register", methods: ["POST"])]
	public function loginOrRegister(
		Request $request,
		UserRepository $userRepository,
		UserProfilRepository $userProfilRepository,
		UserPreferenceRepository $userPreferenceRepository,
		UserPasswordHasherInterface $passwordHasher,
		Security $security,
		CookieService $cookieService,
		UserChecker $userChecker,
		UserRegistrationValidator $registrationValidator
	): Response
	{
		$username = trim((string) $request->request->get('_username'));
		$this->rememberLastUsername($request, $username);

		if (!$this->isCsrfTokenValid('authenticate', $request->request->get('_csrf_token'))){
			$this->addFlash('login_error', 'Jeton de sécurité invalide.');

			return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
		}

		$password = (string) $request->request->get('_password');
		$loginAction = (string) $request->request->get('login_action', 'login');
		$email = trim((string) $request->request->get('_email', ''));
		$passwordConfirm = (string) $request->request->get('_password_confirm', '');

		if ('' === $username || '' === $password){
			$this->addFlash('login_error', 'Merci de renseigner un login et un mot de passe.');

			return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
		}

		if (!in_array($loginAction, ['login', 'register'], true)){
			$this->addFlash('login_error', 'Action de connexion inconnue.');

			return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
		}

		$user = $userRepository->findOneBy(['userName' => $username]);

		if (null !== $user){
			if ('register' === $loginAction){
				$this->addFlash('login_error', 'Ce login existe déjà. Utilisez le bouton Se connecter.');

				return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
			}

			if (!$passwordHasher->isPasswordValid($user, $password)){
				$this->addFlash('login_error', 'Login ou mot de passe incorrect');

				return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
			}

			try {
				$userChecker->checkPreAuth($user);
				$userChecker->checkPostAuth($user);
			} catch (AuthenticationException $exception){
				$this->addFlash('login_error', $this->getLoginErrorMessage($exception->getMessage()));

				return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
			}

			$response = $security->login($user, 'form_login', 'main', [new RememberMeBadge()])
				?? $this->redirectToRoute('tableau_bord', [], Response::HTTP_SEE_OTHER)
			;

			return $cookieService->addRegisteredUserCookieForUser($response, $user);
		}

		if ('login' === $loginAction){
			$this->addFlash('login_error', 'Aucun compte ne correspond à ce login.<br>Utilisez le bouton Créer un compte.');

			return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
		}

		if (null !== $registrationError = $registrationValidator->getRegistrationError($username, $password)){
			$this->addFlash('login_error', $registrationError);

			return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
		}

		if ('' === $email && false !== filter_var($username, FILTER_VALIDATE_EMAIL)){
			$email = $username;
		}

		if (null !== $registrationCompletionError = $registrationValidator->getRegistrationCompletionError($email, $password, $passwordConfirm)){
			$this->addFlash('login_error', $registrationCompletionError);

			return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
		}

		if (null !== $userRepository->findOneBy(['email' => $email])){
			$this->addFlash('login_error', 'Cet email est dÃ©jÃ  rattachÃ© Ã  un compte.');

			return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
		}

		$user = new User();
		$user
			->setAnonyme(false)
			->setRoles(["ROLE_USER"])
			->setCode($this->createUserCode($userRepository))
			->setUserName($username)
			->setEmail($email)
			->setPassword($passwordHasher->hashPassword($user, $password))
		;

		$userProfil = new UserProfil();
		$userProfil->setUser($user);

		$userPreference = new UserPreference();
		$userPreference->setUser($user);

		$userRepository->add($user, true);
		$userProfilRepository->add($userProfil, true);
		$userPreferenceRepository->add($userPreference, true);

		$this->addFlash('success', 'Félicitations '.$user->getUserName().', votre compte a été créé.');

		$response = $security->login($user, 'form_login', 'main', [new RememberMeBadge()])
			?? $this->redirectToRoute('tableau_bord', [], Response::HTTP_SEE_OTHER)
		;

		return $cookieService->addRegisteredUserCookie($response);
	}

	#[Route("/mot-de-passe-oublie", name: "forgot_password_request", methods: ["GET", "POST"])]
	public function forgotPasswordRequest(
		Request $request,
		UserRepository $userRepository,
		MailerInterface $mailer
	): Response
	{
		$identifier = trim((string) $request->request->get('identifier', ''));

		if ($request->isMethod('POST')){
			if (!$this->isCsrfTokenValid('forgot_password_request', $request->request->get('_csrf_token'))){
				$this->addFlash('login_error', 'Jeton de securite invalide.');

				return $this->redirectToRoute('forgot_password_request', [], Response::HTTP_SEE_OTHER);
			}

			if ('' === $identifier){
				$this->addFlash('login_error', 'Merci de renseigner votre login ou votre email.');

				return $this->redirectToRoute('forgot_password_request', [], Response::HTTP_SEE_OTHER);
			}

			$user = $userRepository->findOneForPasswordReset($identifier);

			if (null !== $user && null !== $user->getEmail()){
				$plainToken = bin2hex(random_bytes(32));
				$user
					->setPasswordResetToken($this->hashPasswordResetToken($plainToken))
					->setPasswordResetTokenExpiresAt(new \DateTimeImmutable('+1 hour'))
				;

				$userRepository->add($user, true);

				try {
					$mailer->send((new Email())
						->from('no-reply@a-vos-comptes.local')
						->to($user->getEmail())
						->subject('Reinitialisation de votre mot de passe')
						->html($this->renderView('emails/reset_password.html.twig', [
							'reset_url' => $this->generateUrl('reset_password', ['token' => $plainToken], UrlGeneratorInterface::ABSOLUTE_URL),
							'expires_at' => $user->getPasswordResetTokenExpiresAt(),
						]))
					);
				} catch (TransportExceptionInterface $exception){
					$this->addFlash('login_error', "Impossible d'envoyer l'email de reinitialisation.");

					return $this->redirectToRoute('forgot_password_request', [], Response::HTTP_SEE_OTHER);
				}
			}

			$this->addFlash('login_info', "Si un compte existe avec cet identifiant, un email de reinitialisation vient d'etre envoye.");

			return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
		}

		return $this->render('security/forgot_password.html.twig', [
			'last_identifier' => $identifier,
		]);
	}

	#[Route("/mot-de-passe-reinitialisation/{token}", name: "reset_password", methods: ["GET", "POST"])]
	public function resetPassword(
		string $token,
		Request $request,
		UserRepository $userRepository,
		UserPasswordHasherInterface $passwordHasher,
		UserRegistrationValidator $registrationValidator
	): Response
	{
		$user = $userRepository->findOneByValidPasswordResetToken(
			$this->hashPasswordResetToken($token),
			new \DateTimeImmutable()
		);

		if (null === $user){
			$this->addFlash('login_error', 'Le lien de reinitialisation est invalide ou expire.');

			return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
		}

		if ($request->isMethod('POST')){
			if (!$this->isCsrfTokenValid('reset_password_'.$token, $request->request->get('_csrf_token'))){
				$this->addFlash('login_error', 'Jeton de securite invalide.');

				return $this->redirectToRoute('reset_password', ['token' => $token], Response::HTTP_SEE_OTHER);
			}

			$password = (string) $request->request->get('_password', '');
			$passwordConfirm = (string) $request->request->get('_password_confirm', '');

			if (null !== $passwordError = $registrationValidator->getRegistrationError('valid-login', $password)){
				$this->addFlash('login_error', $passwordError);

				return $this->redirectToRoute('reset_password', ['token' => $token], Response::HTTP_SEE_OTHER);
			}

			if ('' === $passwordConfirm){
				$this->addFlash('login_error', 'Merci de confirmer le mot de passe.');

				return $this->redirectToRoute('reset_password', ['token' => $token], Response::HTTP_SEE_OTHER);
			}

			if ($password !== $passwordConfirm){
				$this->addFlash('login_error', 'Les mots de passe ne correspondent pas.');

				return $this->redirectToRoute('reset_password', ['token' => $token], Response::HTTP_SEE_OTHER);
			}

			$user
				->setPassword($passwordHasher->hashPassword($user, $password))
				->clearPasswordResetToken()
			;

			$userRepository->add($user, true);

			$this->addFlash('login_success', 'Votre mot de passe a ete modifie. Vous pouvez vous connecter.');

			return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
		}

		return $this->render('security/reset_password.html.twig', [
			'token' => $token,
		]);
	}

	/**
	 * @Route("/login_error", name="login_error")
	 * Erreur de connection + Messages
	 */
	#[Route("/login_error", name: "login_error")]
	public function login_error(AuthenticationUtils $authenticationUtils): Response
	{
		// Get the login error if there is one
		$error = $authenticationUtils->getLastAuthenticationError();

		if (null !== $error){

			switch ($error->getMessage()){
				case 'The presented password is invalid.':
				case 'Bad credentials.':
					$this->addFlash('login_error', 'Login ou mot de passe incorrect');
					break;

				case 'bloque':
					$this->addFlash('login_error', 'Votre compte a été bloqué.');
					break;

				case 'inactif':
					$this->addFlash('login_error', 'Votre compte est inactif.');
					break;

				case 'delete':
					$this->addFlash('login_error', 'Votre compte a été supprimé.');
					break;
				
				case '':
					$this->addFlash('login_error', 'Erreur de connexion !');
					break;
				
				default:
					$this->addFlash('login_error', $error->getMessage());
					break;
			}
		}

		return $this->redirectToRoute('home');
	}

	/**
	 * @Route("/logout_alert", name="logout_alert")
	 * Information de déconnexion
	 */
	#[Route("/logout_alert", name: "logout_alert")]
	public function logout_alert(): Response
	{
		$this->addFlash('toaster', 'Déconnexion !');

		return $this->redirectToRoute('home');
	}

    /**
     * @Route("/logout", name="logout")
     * Pas de passage ici
     */
    #[Route("/logout", name: "logout")]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

	private function getLoginErrorMessage(string $message): string
	{
		return match ($message) {
			'bloque' => 'Votre compte a été bloqué.',
			'inactif' => 'Votre compte est inactif.',
			'delete' => 'Votre compte a été supprimé.',
			'' => 'Erreur de connexion !',
			default => $message,
		};
	}

	private function rememberLastUsername(Request $request, string $username): void
	{
		if ($request->hasSession()){
			$request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $username);
		}
	}

	private function hashPasswordResetToken(string $token): string
	{
		return hash('sha256', $token);
	}

	private function createUserCode(UserRepository $userRepository): string
	{
		do {
			$code = $this->randMdp();
		} while (!empty($userRepository->findOneByCode($code)));

		return $code;
	}

	private function randMdp(int $nbCharacter = 8): string
	{
		$comb = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
		$pass = [];
		$combLen = strlen($comb) - 1;

		for ($i = 0; $i < $nbCharacter; $i++){
			$n = rand(0, $combLen);
			$pass[] = $comb[$n];
		}

		return implode($pass);
	}
}
