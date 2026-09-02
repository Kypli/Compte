<?php

namespace App\Controller;

use App\Entity\Compte;
use App\Entity\Credit;
use App\Entity\Immobilier;
use App\Entity\Invest;
use App\Entity\Mobilier;
use App\Form\CompteType;
use App\Form\CreditType;
use App\Form\ImmobilierType;
use App\Form\InvestType;
use App\Form\MobilierType;

use App\Repository\CompteRepository;
use App\Repository\CreditRepository;
use App\Repository\ImmobilierRepository;
use App\Repository\InvestRepository;
use App\Repository\MobilierRepository;
use App\Repository\OperationRepository;

use Symfony\Component\Security\Http\Attribute\IsGranted;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER")
 */
#[IsGranted("ROLE_USER")]
class DashBoardController extends AbstractController
{
	/**
	 * @Route("/dashboard/", name="tableau_bord")
	 */
	#[Route("/dashboard/", name: "tableau_bord")]
	public function index(
		Request $request,
		CompteRepository $cr,
		OperationRepository $or,
		CreditRepository $creditRepository,
		ImmobilierRepository $immobilierRepository,
		MobilierRepository $mobilierRepository,
		InvestRepository $investRepository
	): Response
	{
		$compte = new Compte();
		$compteForm = $this->createForm(CompteType::class, $compte);
		$compteForm->handleRequest($request);

		$credit = new Credit();
		$creditForm = $this->createForm(CreditType::class, $credit);
		$creditForm->handleRequest($request);

		$immobilier = new Immobilier();
		$immobilierForm = $this->createForm(ImmobilierType::class, $immobilier);
		$immobilierForm->handleRequest($request);

		$mobilier = new Mobilier();
		$mobilierForm = $this->createForm(MobilierType::class, $mobilier);
		$mobilierForm->handleRequest($request);

		$investissement = new Invest();
		$investissementForm = $this->createForm(InvestType::class, $investissement);
		$investissementForm->handleRequest($request);

		if ($compteForm->isSubmitted() && $compteForm->isValid()){
			$compte->addUser($this->getUser());

			if ($compte->getMain() == true){
				$user_comptes = $cr->getComptesByUser($this->getUser());
				foreach ($user_comptes as $c){
					$c->setMain(false);
					$cr->add($c, true);
				}
			}

			$cr->add($compte, true);
			$this->addFlash('success', 'Le compte a bien été ajouté.');

			return $this->redirectToRoute('tableau_bord', [], Response::HTTP_SEE_OTHER);
		}
		if ($creditForm->isSubmitted() && $creditForm->isValid()){
			$credit->setUser($this->getUser());
			$creditRepository->add($credit, true);
			$this->addFlash('success', 'Le credit a bien ete ajoute.');

			return $this->redirectToRoute('tableau_bord', [], Response::HTTP_SEE_OTHER);
		}
		if ($immobilierForm->isSubmitted() && $immobilierForm->isValid()){
			$immobilier->setUser($this->getUser());
			$immobilierRepository->add($immobilier, true);
			$this->addFlash('success', 'Le bien immobilier a bien ete ajoute.');

			return $this->redirectToRoute('tableau_bord', [], Response::HTTP_SEE_OTHER);
		}
		if ($mobilierForm->isSubmitted() && $mobilierForm->isValid()){
			$mobilier->setUser($this->getUser());
			$mobilierRepository->add($mobilier, true);
			$this->addFlash('success', 'Le bien mobilier a bien ete ajoute.');

			return $this->redirectToRoute('tableau_bord', [], Response::HTTP_SEE_OTHER);
		}
		if ($investissementForm->isSubmitted() && $investissementForm->isValid()){
			$investRepository->add($investissement, true);
			$this->addFlash('success', 'L investissement a bien ete ajoute.');

			return $this->redirectToRoute('tableau_bord', [], Response::HTTP_SEE_OTHER);
		}

		// To do
		$total = 0;
		$credits = $creditRepository->findByUser($this->getUser());
		$mobiliers = $mobilierRepository->findByUser($this->getUser());
		$immobiliers = $immobilierRepository->findByUser($this->getUser());
		$investissements = [];
		$total += $mobilierRepository->sumValueByUser($this->getUser());
		$total += $immobilierRepository->sumValueByUser($this->getUser());

		// Comptes Datas
		$comptes_solde = [];
		$comptes = $cr->getComptesByUser($this->getUser());
		foreach($comptes as $compte){
			$solde = round(
				($or->CompteSoldeActuel($compte->getId(), true) - $or->CompteSoldeActuel($compte->getId(), false)),
				2
			);
			$comptes_solde[$compte->getId()]['solde'] = $solde;
			$total += $solde;
		}

		return $this->render('dashboard/index.html.twig', [
			'comptes' => $comptes,
			'comptes_solde' => $comptes_solde,

			'credits' => $credits,

			'mobiliers' => $mobiliers,

			'immobiliers' => $immobiliers,

			'investissements' => $investissements,

			'total' => $total,

			'compte_form' => $compteForm->createView(),
			'credit_form' => $creditForm->createView(),
			'immobilier_form' => $immobilierForm->createView(),
			'mobilier_form' => $mobilierForm->createView(),
			'investissement_form' => $investissementForm->createView(),
			'open_compte_modal' => $compteForm->isSubmitted() && !$compteForm->isValid(),
			'open_credit_modal' => $creditForm->isSubmitted() && !$creditForm->isValid(),
			'open_immobilier_modal' => $immobilierForm->isSubmitted() && !$immobilierForm->isValid(),
			'open_mobilier_modal' => $mobilierForm->isSubmitted() && !$mobilierForm->isValid(),
			'open_investissement_modal' => $investissementForm->isSubmitted() && !$investissementForm->isValid(),
		]);
	}

	/**
	 * @Route("/tableauDeBord/", name="tableau_bord_legacy")
	 */
	#[Route("/tableauDeBord/", name: "tableau_bord_legacy")]
	public function legacy(): Response
	{
		return $this->redirectToRoute('tableau_bord', [], Response::HTTP_MOVED_PERMANENTLY);
	}
}
