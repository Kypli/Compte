<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserProfil;
use App\Entity\UserPreference;

use App\Repository\UserRepository;
use App\Repository\UserProfilRepository;
use App\Repository\UserPreferenceRepository;

use App\Form\UserType;
use App\Service\CompteService;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use App\Security\LoginFormAuthenticator;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;

use Symfony\Component\HttpFoundation\Cookie;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @Route("/user", name="user")
 */
class UserController extends AbstractController
{
	// Repository
	private $ur;
	private $upr;
	private $uprer;

	// Password
	private $passwordHasher;

	// User
	private $guard;
	private $loginAuthenticator;

	public function __construct(
		UserRepository $ur,
		UserProfilRepository $upr,
		UserPreferenceRepository $uprer,
		UserPasswordHasherInterface $passwordHasher,
		GuardAuthenticatorHandler $guard,
		LoginFormAuthenticator $loginAuthenticator
	){
		$this->ur = $ur;
		$this->upr = $upr;
		$this->uprer = $uprer;
		$this->passwordHasher = $passwordHasher;
		$this->guard = $guard;
		$this->loginAuthenticator = $loginAuthenticator;
	}

	/**
	 * @IsGranted("ROLE_ADMIN")
	 * @Route("/", name="", methods={"GET"})
	 */
	public function index(): Response
	{
		return $this->render('user/index.html.twig', [
			'users' => $this->ur->findAll(),
		]);
	}

	/**
	 * @Route("/inscription/{id}", defaults={"id": null}, name="_add", methods={"GET", "POST"})
	 */
	public function add($id, Request $request)
	{
		// Ne doit pas être membre ou alors être admin
		if (null !== $this->getUser() && !$this->getUser()->getAnonyme() && !$this->isGranted('ROLE_ADMIN')){
			$this->addFlash('error', 'Vous ne pouvez pas vous inscrire si vous êtes déjà membre.');
			return $this->redirectToRoute('logout', [], Response::HTTP_SEE_OTHER);
		}

		// User
		if ($id == null){
			$user = new User();
			$new = true;
		} else {
			$user = $this->ur->find($id);
			$user->setUserName('');
			$new = false;
		}

		$form = $this->createForm(UserType::class, $user);

		$form
			->remove('admin')
			->remove('anonyme')
			->remove('ip')
			->remove('profil')
			->remove('preferences')
		;

		$form->handleRequest($request);

		// Valid form
		if ($form->isSubmitted() && $form->isValid()){

			// Duplicate control
			if (!empty($this->ur->findOneByUserName($form->getData()->getUserName()))){
				$this->addFlash('error', "Ce login est déjà pris. Merci d'en sélectionner un autre.");

			// Save
			} else {

				// Code
				code:
				$code = $this->randMdp();
				if (!empty($this->ur->findOneByCode($code))){ goto code; }

				// Default datas
				$user
					->setAnonyme(false)
					->setRoles(["ROLE_USER"])
					->setCode($code)
					->setPassword($this->passwordHasher->hashPassword(
						$user,
						$request->request->get('user')['password'],
					))
				;

				// Save
				$this->ur->add($user, true);

				if ($new){

					$userProfil = new UserProfil();
					$userProfil
						->setUser($user)
					;

					$userPreference = new UserPreference();
					$userPreference
						->setUser($user)
					;

					$this->upr->add($userProfil, true);
					$this->uprer->add($userPreference, true);
				}

				$this->addFlash(
					'success',
					'Félicitations '.$user->getUserName().', vous inscription est prise en compte.'
				);

				// Authenticate user 
				$this->guard->authenticateUserAndHandleSuccess(
					$user,
					$request,
					$this->loginAuthenticator,
					'main'
				);

				return $this->redirectToRoute('tableau_bord', [], Response::HTTP_SEE_OTHER);
			}
		}

		return $this->render('user/add.html.twig', [
			'user' => $user,
			'form' => $form->createView(),
		]);
	}

	/**
	 * @Route("/inscription_test", name="_add_test", methods={"GET", "POST"})
	 */
	public function add_test(Request $request, CompteService $cs)
	{
		// Ne doit pas être membre
		if (null !== $this->getUser()){
			$this->addFlash('error', 'Vous ne pouvez pas vous inscrire à une session de test si vous êtes déjà membre.');
			return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
		}

		// Login anonyme
		$last_anonyme = $this->ur->getLastAnonyme();
		$login_ano_count = $last_anonyme == null ? 0 : (int) str_replace('Visiteur', '', $last_anonyme['userName']);
		$login_ano_count++;

		// Default datas
		$user = new User();
		$user
			->setAnonyme(true)
			->setUserName('Visiteur'.(string) $login_ano_count)
			->setPassword($this->passwordHasher->hashPassword(
				$user,
				$this->randMdp(),
			))
		;

		$userProfil = new UserProfil();
		$userProfil
			->setUser($user)
		;

		$userPreference = new UserPreference();
		$userPreference
			->setUser($user)
		;

		// Save
		$this->ur->add($user, true);
		$this->upr->add($userProfil, true);
		$this->uprer->add($userPreference, true);

		// Ajout d'un compte modèle
		$cs->addModele($user);

		// Add Cookie
		$this->cookie($user->getUserName(), $user->getPassword());

		$this->addFlash(
			'success',
			'Voici votre tableau de bord fournit avec un modèle de votre compte principal. N\'oubliez pas de vous enregistrer vous pour sauvegarder votre travail.'
		);

		// Authenticate user 
		$this->guard->authenticateUserAndHandleSuccess(
			$user,
			$request,
			$this->loginAuthenticator,
			'main'
		);

		return $this->redirectToRoute('tableau_bord', [], Response::HTTP_SEE_OTHER);
	}

	/**
	 * @IsGranted("ROLE_USER")
	 * @Route("/{id}", name="_show", methods={"GET"})
	 */
	public function show(User $user): Response
	{
		// Acces control
		if ($this->accesControl($user->getId()) == false){
			return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
		}

		return $this->render('user/show.html.twig', [
			'user' => $user,
		]);
	}

	/**
	 * @IsGranted("ROLE_USER")
	 * @Route("/edit/{id}", name="_edit", methods={"GET", "POST"})
	 */
	public function edit(Request $request, User $user): Response
	{
		// Acces control
		if ($this->accesControl($user->getId()) == false){
			return $this->redirectToRoute('tableau_bord', [], Response::HTTP_SEE_OTHER);
		}

		$form = $this->createForm(UserType::class, $user);
		$req_user = $request->request->get('user');


		// Champs exclusif à l'admin
		if (!$this->isGranted('ROLE_ADMIN')){
			$form
				->remove('userName')
				->remove('admin')
				->remove('anonyme')
				->remove('ip')
				->remove('accesPhoto')
				->remove('accesPhotoLanceurAlerte')
				->remove('adherant')
				->remove('dateInscription')
				->remove('dateFinAdhesion')
				->remove('roleCa')
				->remove('dateFinMandat')
				->remove('membreHonneur')
				->remove('commentaire')
			;
		}

		// Alimenter dans le request le champ password si inutilisé
		if (null !== $request->request->get('user') && $request->request->get('user')['password'] == ''){
			$noEditPassword = true;
			$requestArray = $request->request->all();
			$requestArray['user']['password'] = $form->getData()->getPassword();
			$request->request->replace($requestArray);
		}

		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid() && $this->formControl($user)){

			// Lower Nom + Prenom
			$user->getProfil()
				->setNom(strtolower($user->getProfil()->getNom()))
				->setPrenom(strtolower($user->getProfil()->getPrenom()))
			;

			// Edit admin
			if ($this->isGranted('ROLE_ADMIN')){

				// True
				if (isset($req_user['admin']) && $req_user['admin'] == 1){
					$user->setRoles(["ROLE_ADMIN"]);

				// False
				} elseif(!$user->isAdmin() || ($user->isAdmin() && $this->ur->countAdmin() > 1)){
					$user->setRoles(["ROLE_USER"]);

				// Null
				} else {
					$this->addFlash('error', 'Suppression du rôle Admin annulée, il doit au moins en rester un.');
				}
			}

			// Si plus anonyme, retrait du mdp temporaire + ip
			if (!$user->getAnonyme()){
				$user
					->setPasswordTempo('')
					->setIp('')
				;
			}

			// Encrypt password
			if (!isset($noEditPassword)){
				$user->setPassword($this->passwordHasher->hashPassword(
						$user,
						$form->getData()->getPassword(),
					))
				;
			}

			$this->ur->add($user);
			$this->addFlash('success', 'Vos modifications ont bien été prise en compte.');
			return $this->redirectToRoute('user_show', ['id' => $user->getId()], Response::HTTP_SEE_OTHER);
		}

		return $this->renderForm('user/edit.html.twig', [
			'user' => $user,
			'form' => $form,
		]);
	}

	/**
	 * @Route("/delete/{id}", name="_delete", methods={"POST"})
	 */
	public function delete(Request $request, User $user, DiscussionRepository $dr): Response
	{
		// Acces control
		if ($this->accesControl($user->getId()) == false){
			return $this->redirectToRoute('home', [], Response::HTTP_SEE_OTHER);
		}

		// Doit rester 1 admin
		$countAdmin = $this->ur->countAdmin();
		if (
			$countAdmin > 1 ||
			(
				$countAdmin == 1 &&
				!in_array($user->getId(), $this->ur->getAdminsId())
			)
		){
			if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))){

				// Delete messages
				foreach ($user->getDiscussionsAuteur() as $discussion){
					$dr->remove($discussion);
				}
				foreach ($user->getDiscussionsDestinataire() as $discussion){
					$dr->remove($discussion);
				}

				// Delete
				$this->ur->remove($user);
			}	

		} else {
			$this->addFlash('error', 'Il doit rester au moins 1 admin.');
		}

		return $this->redirectToRoute('user', [], Response::HTTP_SEE_OTHER);
	}

	/**
	 * @IsGranted("ROLE_USER")
	 * @Route("/preference/{id}", name="_preference")
	 */
	public function preference(User $user): Response
	{
		$user = $this->getUser();
		return $this->render('user/preference.html.twig', [
			'user' => $user,
		]);
	}

	public function accesControl($user_id)
	{
		// Si non-admin
		if (!$this->isGranted('ROLE_ADMIN')){

			// Doit être connecté
			if (null === $this->getUser()){
				$this->addFlash('error', 'Vous devez être connecté pour accéder à votre profil.');
				return false;
			}

			// Doit être propriétaire
			if ($user_id != $this->getUser()->getId()){
				$this->addFlash('error', 'Vous devez être propriétaire de ce profil.');
				return false;
			}
		}
		return true;
	}

	public function formControl($user)
	{
		// Si adhérant, rajouter date inscription + date fin adhesion
		if (
			null != $user->getAsso()->isAdherant() &&
			(
				null == $user->getAsso()->getDateInscription() ||
				null == $user->getAsso()->getDateFinAdhesion()
			)
		){
			$this->addFlash('error', "Si l'utilisateur est un adhérant, il doit avoir une date d'inscription et de fin d'adhésion.");
			return false;
		}

		// Courriel valide
		if (!empty($user->getProfil()->getMail()) && !filter_var($user->getProfil()->getMail(), FILTER_VALIDATE_EMAIL)){
			$this->addFlash('error', "Le courriel n'est pas valide.");
			return false;
		}

		// Si newsletter, doit avoir un mail
		if (empty($user->getProfil()->getMail()) && $user->getNewsletter()){
			$this->addFlash('error', "Vous devez avoir un courriel pour être inscrit à la newsletter.");
			return false;
		}

		return true;
	}

	public function randMdp($nbCharacter = 8)
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

	public function cookie($user_id, $user_psw): Response
	{

		$cookie_user = new Cookie(
			'anonyme', // Nom cookie
			$user_id, // Valeur
			strtotime('tomorrow'), //expire le
			'/', // Chemin de serveur
			'stacktraceback.com', //Nom domaine
			true, // Https seulement
			true
		); // Disponible uniquement dans le protocole HTTP

		$cookie_mdp = new Cookie(
			'anonyme_mdp', // Nom cookie
			$user_psw, // Valeur
			strtotime('tomorrow'), //expire le
			'/', // Chemin de serveur
			'stacktraceback.com', //Nom domaine
			true, // Https seulement
			true
		); // Disponible uniquement dans le protocole HTTP

		$res = new Response();
		$res->headers->setCookie($cookie_user);
		$res->headers->setCookie($cookie_mdp);

		return $res;
	}
}
