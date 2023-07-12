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
	public function index(CompteRepository $cr, OperationRepository $or)
	{
		// To do
		$total = 0;
		$credits = [];
		$mobiliers = [];
		$immobiliers = [];
		$investissements = [];

		// Comptes Datas
		$comptes_solde = [];
		$comptes = $cr->getComptesByUser($this->getUser());
		foreach($comptes as $compte){
			$solde = round(
				($or->CompteSoldeActuel($compte->getId(), true) - $or->CompteSoldeActuel($compte->getId(), false)),
				2
			);
			$comptes_solde[$compte->getId()]['solde'] = number_format($solde, 2, ',', ' ');
			$total += $solde;
		}

		return $this->render('dashboard/index.html.twig', [
			'comptes' => $comptes,
			'comptes_solde' => $comptes_solde,

			'credits' => $credits,

			'mobiliers' => $mobiliers,

			'immobiliers' => $immobiliers,

			'investissements' => $investissements,

			'total' => number_format($total, 2, ',', ' '),
		]);
	}
}
