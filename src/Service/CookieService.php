<?php 

namespace App\Service;

use Symfony\Component\HttpFoundation\Cookie;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CookieService
{
	/**
	 * Ajoute des cookies
	 * New Cookie('nom', 'valeur', 'date expiration', 'Chemin de serveur', 'Nom domaine', 'Https seulement', 'Protocole HTTP')
	 */
	public function addCookie(Request $request, $user_id, $user_psw)
	{
		// setcookie("anonyme", $user_id);
		// setcookie("anonyme_mdp", $user_psw);

		$cookie_user = new Cookie('anonyme', $user_id);
		$cookie_mdp = new Cookie('anonyme_mdp',	$user_psw);

		// $cookie_user = new Cookie(
		// 	'anonyme',
		// 	$user_id,
		// 	strtotime('tomorrow'),
		// 	'/',
		// 	'localhost',
		// 	true,
		// 	true
		// );
		// $cookie_mdp = new Cookie(
		// 	'anonyme_mdp',
		// 	$user_psw,
		// 	strtotime('tomorrow'),
		// 	'/',
		// 	'localhost',
		// 	true,
		// 	true
		// );

		$res = new Response();
		$res->headers->setCookie($cookie_user);
		$res->headers->setCookie($cookie_mdp);
		
		// TODO - Réparer ce truc qui bloque
		// $res->send();

		return $res;
	}
	/**
	 * Retire des cookies
	 */
	public function removeCookie()
	{
		// setcookie("anonyme", "", time() - 3600);
		// setcookie("anonyme_mdp", "", time() - 3600);

		$res = new Response();
		$res->headers->clearCookie('anonyme');
		$res->headers->clearCookie('anonyme_mdp');

		// TODO - Réparer ce truc qui bloque
		// $res->send();

		return $res;
	}
}