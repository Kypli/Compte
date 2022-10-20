<?php

namespace App\Controller;

use App\Entity\Compte;

use App\Form\CompteType;

use App\Repository\CompteRepository;
use App\Repository\OperationRepository;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER")
 * @Route("/compte", name="compte")
 */
class CompteController extends AbstractController
{
	private $max_year;

	private $min_year;


	public function __construct()
	{
		$this->max_year = ((int)date('Y') + 40);
		$this->min_year = ((int)date('Y') - 40);
	}

	/**
	 * @Route("/", name="")
	 */
	public function index(CompteRepository $cr): Response
	{
		return $this->render('compte/index.html.twig', [
			'comptes' => $cr->findAll(),
		]);
	}

	/**
	 * @Route("/new", name="_new", methods={"GET", "POST"})
	 */
	public function new(Request $request, CompteRepository $cr): Response
	{
		$compte = new Compte();
		$form = $this->createForm(CompteType::class, $compte);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$cr->add($compte, true);

			return $this->redirectToRoute('compte_index', [], Response::HTTP_SEE_OTHER);
		}

		return $this->renderForm('compte/new.html.twig', [
			'compte' => $compte,
			'form' => $form,
		]);
	}

	/**
	 * @Route("/{id}", name="_show", methods={"GET"})
	 */
	public function show(Compte $compte, OperationRepository $or, Request $request): Response
	{
		// Year
		$year = (int) $request->query->get('year');
		$year =
			0 != $year &&
			$year >= $this->min_year &&
			$year <= $this->max_year
				? $year
				: date('Y')
		;

		// Solde
		$solde = $or->CompteSoldeActuel($compte->getId());

		// Opérations
		$operations_pos = $or->OperationsByYearAndCompte($compte->getId(), $year);
		$operations_neg = $or->OperationsByYearAndCompte($compte->getId(), $year, false);

		// Current Month
		$date_month = new \Datetime('now');
		$date_month = $date_month->format('n');

		// Month
		$months = [
			1 => 'janvier',
			2 => 'février',
			3 => 'mars',
			4 => 'avril',
			5 => 'mai',
			6 => 'juin',
			7 => 'juillet',
			8 => 'aout',
			9 => 'septembre',
			10 => 'octobre',
			11 => 'novembre',
			12 => 'décembre',
		];

		return $this->render('compte/show.html.twig', [
			'compte' => $compte,

			'months' => $months,
			'year' => $year,
			'max_year' => $this->max_year,
			'min_year' => $this->min_year,

			'user' => $this->getUser(),
			'current_month' => $date_month,

			'operations_pos' => $this->operations($operations_pos),
			'operations_neg' => $this->operations($operations_neg, false),

			'solde' => $solde, // Solde du compte
			'soldes' => $this->soldes(array_merge($operations_pos, $operations_neg)), // Solde by month
		]);
	}

	/**
	 * Renvoie sous formes d'array les informations liés à des opérations
	 */
	public function operations($operations_ent, $pos = true): Array
	{
		$total_final = 0;
		$operations = [];

		foreach($operations_ent as $operation){

			$number = $pos ? $operation->getNumber() : $operation->getNumber() * -1;

			$total_final += $number;
			$mois = $operation->getDate()->format('n');
			$sc_id = $operation->getSubCategory()->getId();

			// Reel
			if (!$operation->isAnticipe()){

				// Add number to [sc][month][reel]
				isset($operations[$sc_id][$mois]['reel'])
					? $operations[$sc_id][$mois]['reel'] += $number
					: $operations[$sc_id][$mois]['reel'] = $number
				;

				// Total reel by month
				isset($operations['totaux_mois'][$mois]['reel'])
					? $operations['totaux_mois'][$mois]['reel'] += $number
					: $operations['totaux_mois'][$mois]['reel'] = $number
				;

			// Anticipe
			} else {

				// Add number to [sc][month][anticipe]
				isset($operations[$sc_id][$mois]['anticipe'])
					? $operations[$sc_id][$mois]['anticipe'] += $number
					: $operations[$sc_id][$mois]['anticipe'] = $number
				;

				// Total anticipe by month
				isset($operations['totaux_mois'][$mois]['anticipe'])
					? $operations['totaux_mois'][$mois]['anticipe'] += $number
					: $operations['totaux_mois'][$mois]['anticipe'] = $number
				;
			}

			// Total by month
			isset($operations['totaux_mois'][$mois]['total'])
				? $operations['totaux_mois'][$mois]['total'] += $number
				: $operations['totaux_mois'][$mois]['total'] = $number
			;

			// Total by Sc
			isset($operations[$sc_id]['total'])
				? $operations[$sc_id]['total'] += $number
				: $operations[$sc_id]['total'] = $number
			;
		}

		// Total par année
		$operations['total_final'] = $total_final;

		return $operations;
	}

	/**
	 * Renvoie sous formes d'array les informations liés à des opérations
	 */
	public function soldes($operations_ent): Array
	{
		$cumule = 0;
		$total_final = 0;
		$operations = [];

		foreach($operations_ent as $operation){

			$total_final += $operation->getNumber();
			$mois = $operation->getDate()->format('n');

			// Total by month
			isset($operations['totaux_solde'][$mois]['solde'])
				? $operations['totaux_solde'][$mois]['solde'] += $operation->getNumber()
				: $operations['totaux_solde'][$mois]['solde'] = $operation->getNumber()
			;
		}

		if (isset($operations['totaux_solde'])){
			foreach($operations['totaux_solde'] as $key => $mois){

				$cumule += $mois['solde'];
				$operations['totaux_solde'][$key]['cumule'] = $cumule;
			}
		}

		// Total par année
		$operations['total_final'] = $total_final;

		return $operations;
	}

	/**
	 * @Route("/{id}/edit", name="_edit", methods={"GET", "POST"})
	 */
	public function edit(Request $request, Compte $compte, CompteRepository $cr): Response
	{
		$form = $this->createForm(CompteType::class, $compte);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$cr->add($compte, true);

			return $this->redirectToRoute('compte_index', [], Response::HTTP_SEE_OTHER);
		}

		return $this->renderForm('compte/edit.html.twig', [
			'compte' => $compte,
			'form' => $form,
		]);
	}

	/**
	 * @Route("/{id}", name="_delete", methods={"POST"})
	 */
	public function delete(Request $request, Compte $compte, CompteRepository $cr): Response
	{
		if ($this->isCsrfTokenValid('delete'.$compte->getId(), $request->request->get('_token'))) {
			$cr->remove($compte, true);
		}

		return $this->redirectToRoute('compte_index', [], Response::HTTP_SEE_OTHER);
	}
}
