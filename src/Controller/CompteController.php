<?php

namespace App\Controller;

use App\Entity\Compte;
use App\Entity\Operation;
use App\Entity\SubCategory;

use App\Form\CompteType;

use App\Repository\CompteRepository;
use App\Repository\OperationRepository;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @IsGranted("ROLE_USER")
 * @Route("/compte", name="compte")
 */
class CompteController extends AbstractController
{
	private $navigation_max_year;

	private $navigation_min_year;

	public function __construct()
	{
		$this->navigation_max_year = ((int)date('Y') + 40);
		$this->navigation_min_year = ((int)date('Y') - 40);
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
		// Current Month
		$date = new \Datetime('now');
		$current_year = $date->format('Y');
		$current_month = $date->format('n');

		// Year
		$year = (int) $request->query->get('year');
		$year =
			0 != $year &&
			$year >= $this->navigation_min_year &&
			$year <= $this->navigation_max_year
				? $year
				: date('Y')
		;

		// Solde
		$solde = round(($or->CompteSoldeActuel($compte->getId(), true) - $or->CompteSoldeActuel($compte->getId(), false)), 2);

		// Opérations
		$operations_pos = $or->OperationsByYearAndCompteAndSign($compte->getId(), $year);
		$operations_neg = $or->OperationsByYearAndCompteAndSign($compte->getId(), $year, false);

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

			'year' => $year,
			'months' => $months,
			'months_json' => json_encode($months),
			'max_year' => $this->navigation_max_year,
			'min_year' => $this->navigation_min_year,

			'user' => $this->getUser(),
			'current_year' => $current_year,
			'current_month' => $current_month,

			'operations_pos' => $this->operations($operations_pos),
			'operations_neg' => $this->operations($operations_neg, false),

			'solde' => $solde, // Solde du compte
			'soldes' => $this->soldesByMonth($operations_pos, $operations_neg),
		]);
	}

	/**
	 * Renvoie sous formes d'array les informations liés à des opérations
	 */
	public function operations($operations_ent, $sign = true): Array
	{
		$total_final = 0;
		$operations = [];

		foreach($operations_ent as $operation){

			$number = $sign ? $operation->getNumber() : $operation->getNumber() * -1;

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
	 * Renvoie sous formes d'array le solde d'un compte
	 */
	public function soldesByMonth($operations_pos, $operations_neg): Array
	{
		$cumule = 0;
		$total_final = 0;
		$operations = [];

		foreach($operations_pos as $operation){

			$total_final += $operation->getNumber();
			$mois = $operation->getDate()->format('n');

			// Total by month
			isset($operations['totaux_solde'][$mois]['solde'])
				? $operations['totaux_solde'][$mois]['solde'] += $operation->getNumber()
				: $operations['totaux_solde'][$mois]['solde'] = $operation->getNumber()
			;
		}

		foreach($operations_neg as $operation){

			$total_final -= $operation->getNumber();
			$mois = $operation->getDate()->format('n');

			// Total by month
			isset($operations['totaux_solde'][$mois]['solde'])
				? $operations['totaux_solde'][$mois]['solde'] -= $operation->getNumber()
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

	// ****************
	// MODAL GESTION
	// ****************

	/**
	 * @Route("/gestion/{sc}/{year}/{month}/{sign}", name="_gestion")
	 * Ajax only
	 */
	public function gestion(SubCategory $sc, $year, $month, $sign, Request $request, OperationRepository $or): Response
	{
		// Control request
		if (!$request->isXmlHttpRequest()){ throw new HttpException('500', 'Requête ajax uniquement'); }

		$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

		$datas['days_in_month'] = $daysInMonth;
		$datas['subcategory_libelle'] = $sc->getLibelle();
		$datas['category_libelle'] = $sc->getCategory()->getLibelle();
		$datas['operations'] = $or->gestion($sc, $year, $month, $sign, $daysInMonth);

		return new JsonResponse($datas);
	}

	/**
	 * @Route("/gestion/save/{sc}/{year}/{month}/{sign}", name="_gestion_save", methods={"GET", "POST"}, options={"expose"=true})
	 * Ajax only
	 */
	public function gestion_save(SubCategory $sc, $year, $month, $sign, Request $request, OperationRepository $or): Response
	{
		// Control request
		if (!$request->isXmlHttpRequest()){ throw new HttpException('500', 'Requête ajax uniquement'); }

		// Control Sc owner
		$user = $this->getUser();
		if (!$user->hasSubCategory($user, $sc)){
			return new JsonResponse(['save' => "Pas propriétaire de la subcategorie."]);
		}

		// Datas from DB
		$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		$operations = $or->gestion($sc, $year, $month, $sign, $daysInMonth);

		// Datas from ajax
		$datas = isset($request->request->all()['datas'])
			? $request->request->all()['datas']
			: []
		;
	
		// Save
		foreach($datas as $ope){

			// Edit
			if (!empty($ope['id'])){
				$id = $ope['id'];
				$ope_ent = $or->find($id);

				if ($ope_ent == null){ return new JsonResponse(['save' => false]); }

			// Add
			} else {
				$id = null;
				$ope_ent = new operation();
				$ope_ent->setSubcategory($sc);
			}

			// Do not delete
			foreach($operations as $key => $operation){
				if ($id == $operation['id'] || $id == null){
					unset($operations[$key]);
				}
			}

			// Save ?
			if (
				// Add
				$id == null ||

				// Edit
				(
					$ope_ent->hasSubCategory($ope_ent, $sc) &&
					(
						($ope['number'] != null && $ope['number'] != 0 && $ope['number'] != '0') ||
						($ope['number_anticipe'] != null && $ope['number_anticipe'] != 0 && $ope['number_anticipe'] != '0')
					)
				)
			){
				$date = new \Datetime($ope['year'].'/'.$ope['month'].'/'.$ope['day']);
				$number = $ope['number'] == null || $ope['number'] == 0 || $ope['number'] == '0'
					? (float) $ope['number_anticipe']
					: (float) $ope['number']
				;
				$anticipe = $ope['number'] == null || $ope['number'] == 0 || $ope['number'] == '0'
					? true
					: false
				;
				$ope_ent
					->setNumber($number)
					->setDate($date)
					->setComment($ope['comment'])
					->setAnticipe($anticipe)
				;
				$or->add($ope_ent, true);
			}
		}

		// Delete
		foreach($operations as $operation){
			$del = $or->find($operation['id']);
			$or->remove($del, true);
		}

		$operations = $or->gestion($sc, $year, $month, $sign, $daysInMonth);

		return new JsonResponse(['save' => true, 'operations' => $operations]);
	}

	/**
	 * @Route("/operation/add/{month}/{year}/{daysInMonth}/{sign}", name="_operation_add")
	 * Ajax only
	 */
	public function operationAdd($month, $year, $daysInMonth, $sign, Request $request): Response
	{
		// Control request
		if (!$request->isXmlHttpRequest()){ throw new HttpException('500', 'Requête ajax uniquement'); }

		$render = $this->render('compte/modal/operations/_add.html.twig', [
			'sign' => $sign,
			'year' => $year,
			'month' => (int) $month,
			'daysInMonth' => $daysInMonth,
		])->getContent();

		return new JsonResponse([
			'render' => $render,
		]);
	}
}
