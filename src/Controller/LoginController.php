<?php

namespace App\Controller;

use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LoginController extends AbstractController
{
	/**
	 * @Route("/login", name="login")
	 * Se retrouve ici en cas de redirection vers le login si non connecté
	 */
	public function index(AuthenticationUtils $authenticationUtils): Response
	{
		// get the login error if there is one
		$error = $authenticationUtils->getLastAuthenticationError();

		$this->addFlash('login_info', 'Vous devez vous connecter !');

		// last username entered by the user
		$lastUsername = $authenticationUtils->getLastUsername();

		return $this->redirectToRoute('home');
	}

	/**
	 * @Route("/login_error", name="login_error")
	 * Erreur de connection + Messages
	 */
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
	public function logout_alert(): Response
	{
		$this->addFlash('login_info', 'Déconnexion !');

		return $this->redirectToRoute('home');
	}

    /**
     * @Route("/logout", name="logout")
     * Pas de passage ici
     */
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
