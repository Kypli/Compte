<?php

namespace App\Controller;

use App\Entity\UserProfil;

use App\Repository\UserRepository;
use App\Repository\UserProfilRepository;
use App\Repository\OperationRepository;

use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER")
 * @Route("/tableauDeBord", name="tableau_bord")
 */
class DashBoardController extends AbstractController
{
	/**
	 * @Route("/", name="")
	 */
	public function index(Request $request, AuthenticationUtils $authenticationUtils, OperationRepository $or){

		// User
		$user = $this->getUser();

		return $this->render('dashboard/index.html.twig');
	}
}
