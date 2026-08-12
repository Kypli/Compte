<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\CookieService;

use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/", name="home")
 */
#[Route("/", name: "home")]
class HomeController extends AbstractController
{
	/**
	 * @Route("/", name="")
	 */
	#[Route("/", name: "")]
	public function index(Request $request, AuthenticationUtils $authenticationUtils, UserRepository $userRepository, CookieService $cookieService){
		$testUser = null;

		if (null === $this->getUser()){
			$testUser = $cookieService->getAnonymousTestSessionUser($request, $userRepository);
		}

		$currentUser = $this->getUser();
		$isAnonymousContext = null !== $testUser || (null !== $currentUser && method_exists($currentUser, 'getAnonyme') && $currentUser->getAnonyme());

		return $this->render('home/index.html.twig',[

			// Authentification
			'error' => $authenticationUtils->getLastAuthenticationError(),	// get the login error if there is one
			'last_username' => $authenticationUtils->getLastUsername(),		// last username entered by the user
			'anonyme' => null !== $testUser ? $testUser->getId() : null,
			'test_user' => $testUser,
			'test_session_remaining' => $isAnonymousContext ? $cookieService->getAnonymousCookieRemainingTimeLabel($request) : null,
			'can_create_anonymous_test_session' => $cookieService->canCreateAnonymousTestSession($request),
		]);
	}
}
