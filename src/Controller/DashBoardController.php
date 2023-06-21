<?php

namespace App\Controller;

use App\Entity\UserProfil;

use App\Repository\UserRepository;
use App\Repository\CompteRepository;
use App\Repository\OperationRepository;

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
	public function index(CompteRepository $cr, OperationRepository $or){


		// Comptes Datas
		foreach($cr->getComptesByUser($this->getUser()) as $compte){
			$comptes[$compte->getId()]['solde'] = round(
				($or->CompteSoldeActuel($compte->getId(), true) - $or->CompteSoldeActuel($compte->getId(), false)),
				2
			);
			$comptes[$compte->getId()]['label'] = $compte->getLabel();
		}

		// dd($comptes);

		return $this->render('dashboard/index.html.twig', [
			'comptes' => $comptes,
		]);
	}
}
