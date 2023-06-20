<?php

namespace App\Controller;

use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/", name="home")
 */
class HomeController extends AbstractController
{
	/**
	 * @Route("/", name="")
	 */
	public function index(Request $request, AuthenticationUtils $authenticationUtils){

		// Redirection Dashboard if log
		if (null !== $this->getUser()){ 
			return $this->redirectToRoute('tableau_bord', [], Response::HTTP_SEE_OTHER);
		}

		$login_error = $authenticationUtils->getLastAuthenticationError();

		if ($login_error != null){
			$mess = $login_error->getMessage();
			$this->addFlash('login_error', $mess == 'Bad credentials.' ? 'Login ou mot de passe incorrect' : $mess);
		}

		return $this->render('home/index.html.twig',[

			// Authentification
			'error' => $login_error,									// get the login error if there is one
			'last_username' => $authenticationUtils->getLastUsername(),	// last username entered by the user
		]);
	}
}
