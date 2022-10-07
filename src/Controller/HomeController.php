<?php

namespace App\Controller;

use App\Entity\UserProfil;

use App\Repository\UserRepository;
use App\Repository\UserProfilRepository;

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

		// User
		$user = $this->getUser();

		return $this->render('home/index.html.twig',[

			// Authentification
			'error' => $authenticationUtils->getLastAuthenticationError(),		// get the login error if there is one
			'last_username' => $authenticationUtils->getLastUsername(),			// last username entered by the user
		]);
	}
}
