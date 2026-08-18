<?php

namespace App\Controller;

use App\Entity\Compte;
use App\Entity\Category;
use App\Entity\Operation;
use App\Entity\OperationAction;
use App\Entity\SubCategory;

use App\Form\CompteType;
use App\Form\UserPreferenceType;

use App\Repository\CompteRepository;
use App\Repository\CategoryRepository;
use App\Repository\OperationRepository;
use App\Repository\OperationActionRepository;
use App\Repository\SubCategoryRepository;
use App\Security\CompteVoter;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @IsGranted("ROLE_USER")
 * @Route("/compte", name="compte")
 */
#[IsGranted("ROLE_USER")]
#[Route("/compte", name: "compte")]
class CompteController extends AbstractController
{
	public const MONTHS = [
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
	public const MONTH_DISPLAY_OPTIONS = [
		'year' => ['label' => 'Année entière', 'radius' => null],
		'three' => ['label' => '3 mois avant/après', 'radius' => 3],
		'one' => ['label' => '1 mois avant/après', 'radius' => 1],
		'current' => ['label' => 'Mois en cours', 'radius' => 0],
	];

	private $navigation_max_year;
	private $navigation_min_year;

	private $cr;
	private $or;
	private $oar;
	private $catr;
	private $scr;

	public function __construct(
		CompteRepository $cr,
		OperationRepository $or,
		OperationActionRepository $oar,
		CategoryRepository $catr,
		SubCategoryRepository $scr
	){
		$this->navigation_max_year = 9999;
		$this->navigation_min_year = 1000;
		$this->cr = $cr;
		$this->or = $or;
		$this->oar = $oar;
		$this->catr = $catr;
		$this->scr = $scr;
	}

	// ****************
	// COMPTE
	// ****************

	/**
	 * @Route("/", name="")
	 */
	#[Route("/", name: "")]
	public function index(CompteRepository $cr): Response
	{
		return $this->render('compte/index.html.twig', [
			'comptes' => $cr->getComptesByUser($this->getUser()),
		]);
	}

	/**
	 * @Route("/new", name="_new", methods={"GET", "POST"})
	 */
	#[Route("/new", name: "_new", methods: ["GET", "POST"])]
	public function new(Request $request): Response
	{
		$compte = new Compte();
		$form = $this->createForm(CompteType::class, $compte);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()){

			$compte->addUser($this->getUser());

			// Devient unique main si true
			if ($compte->getMain() == true){
				$user_comptes = $this->getUser()->getComptes();
				foreach ($user_comptes as $c){
					$c->setMain(false);
					$this->cr->add($c, true);
				}
			}

			// Save
			$this->cr->add($compte, true);

			return $this->redirectToRoute('tableau_bord', [], Response::HTTP_SEE_OTHER);
		}

		return $this->render('compte/new.html.twig', [
			'compte' => $compte,
			'form' => $form->createView(),
		]);
	}

	/**
	 * @Route("/{id}", name="_show", methods={"GET"})
	 * Montre un compte
	 */
	#[Route("/{id}", name: "_show", methods: ["GET"])]
	public function show(Compte $compte, Request $request): Response
	{
		$this->denyAccessUnlessGranted(CompteVoter::ACCESS, $compte);

		// Current dates
		$date = new \Datetime('now');
		$current_year = (int) $date->format('Y');
		$current_month = $date->format('n');
		[
			$month_display,
			$visible_months,
		] = $this->resolveMonthDisplay($request, (int) $current_month);

		// Year
		$year = $this->resolveYear($request, $current_year);
		$year_options = $this->yearOptions($compte->getId(), $current_year);
		$anomalies = $this->or->findOverdueAnticipatedForCompte($compte->getId());
		$other_comptes = array_values(array_filter(
			$this->cr->getComptesByUser($this->getUser()),
			fn (Compte $userCompte): bool => $userCompte->getId() !== $compte->getId()
		));

		// Opérations
		$operations_pos = $this->or->OperationsByYearAndCompteAndSign($compte->getId(), $year);
		$operations_neg = $this->or->OperationsByYearAndCompteAndSign($compte->getId(), $year, false);
		$operations_pos_datas = $this->operations($operations_pos);
		$operations_neg_datas = $this->operations($operations_neg, false);

		// Solde
		$current_solde = round(
			($this->or->CompteSoldeActuel($compte->getId(), true) - $this->or->CompteSoldeActuel($compte->getId(), false)),
			2
		);

		// Solde Fin mois
		$soldeFinMensuel = $this->soldeFinMensuel(
			$current_solde,
			$operations_pos_datas,
			$operations_neg_datas,
			$year,
			$current_year,
			(int) $current_month
		);

		// Color solde
		$color_solde = $this->colorSolde($current_solde, $compte->getDecouvert());
		$color_soldeFinMois = $this->colorSolde($soldeFinMensuel, $compte->getDecouvert());
		$preferenceForm = $this->createForm(UserPreferenceType::class, $this->getUser()->getPreferences(), [
			'action' => $this->generateUrl('user_preference', ['id' => $this->getUser()->getId()]),
		]);

		return $this->render('compte/show.html.twig', [
			'compte' => $compte,

			'year' => $year,
			'months' => SELF::MONTHS,
			'months_json' => json_encode(SELF::MONTHS),
			'month_display' => $month_display,
			'month_display_options' => SELF::MONTH_DISPLAY_OPTIONS,
			'visible_months' => $visible_months,
			'future_year_options' => $year_options['future'],
			'past_budget_year_options' => $year_options['past'],
			'other_comptes' => $other_comptes,
			'max_year' => $this->navigation_max_year,
			'min_year' => $this->navigation_min_year,

			'user' => $this->getUser(),
			'current_year' => $current_year,
			'current_month' => $current_month,

			'operations_pos' => $operations_pos_datas,
			'operations_neg' => $operations_neg_datas,

			'color_solde' => $color_solde, // Couleur d'alerte du solde
			'color_soldeFinMois' => $color_soldeFinMois, // Couleur d'alerte du solde
			'current_solde' => $current_solde, // Solde courant du compte
			'current_monthEnd' => $soldeFinMensuel, // Solde courant du compte à la fin du mois
			'gains' => $this->gains($operations_pos, $operations_neg),

			'lastActions' => $this->oar->lastActionsForCompte($compte->getId()), // Last actions
			'anomalies' => $anomalies,
			'account_preference_form' => $preferenceForm->createView(),
		]);
	}

	/**
	 * @Route("/{id}/tables", name="_tables", methods={"POST"})
	 * Renvoie le render des tables
	 * Ajax only
	 */
	#[Route("/{id}/tables", name: "_tables", methods: ["POST"])]
	public function tables(Compte $compte, Request $request): Response
	{
		$this->denyAccessUnlessGranted(CompteVoter::ACCESS, $compte);

		// Control request
		if (!$request->isXmlHttpRequest()){ throw new HttpException('500', 'Requête ajax uniquement'); }

		// Current dates
		$date = new \Datetime('now');
		$current_year = (int) $date->format('Y');
		$current_month = $date->format('n');
		[
			$month_display,
			$visible_months,
		] = $this->resolveMonthDisplay($request, (int) $current_month);

		// Year
		$year = $this->resolveYear($request, $current_year);

		// Opérations
		$operations_pos = $this->or->OperationsByYearAndCompteAndSign($compte->getId(), $year);
		$operations_neg = $this->or->OperationsByYearAndCompteAndSign($compte->getId(), $year, false);
		$operations_pos_datas = $this->operations($operations_pos);
		$operations_neg_datas = $this->operations($operations_neg, false);
		$anomalies = $this->or->findOverdueAnticipatedForCompte($compte->getId());

		// Solde
		$solde = round(
			($this->or->CompteSoldeActuel($compte->getId(), true) - $this->or->CompteSoldeActuel($compte->getId(), false)),
			2
		);

		// Solde Fin mois
		$soldeFinMensuel = $this->soldeFinMensuel(
			$solde,
			$operations_pos_datas,
			$operations_neg_datas,
			$year,
			$current_year,
			(int) $current_month
		);

		$render = $this->render('compte/table/_tables.html.twig', [
			'compte' => $compte,

			'year' => $year,
			'months' => SELF::MONTHS,
			'month_display' => $month_display,
			'visible_months' => $visible_months,

			'user' => $this->getUser(),
			'current_year' => $current_year,
			'current_month' => $current_month,

			'operations_pos' => $this->operations($operations_pos),
			'operations_neg' => $this->operations($operations_neg, false),

			'gains' => $this->gains($operations_pos, $operations_neg),
		])->getContent();

		$render_last_actions = $this->render('compte/_last_actions.html.twig', [
			'compte' => $compte,
			'lastActions' => $this->oar->lastActionsForCompte($compte->getId()),
		])->getContent();
		$render_anomalies = $this->render('compte/_anomalies.html.twig', [
			'compte' => $compte,
			'anomalies' => $anomalies,
		])->getContent();
		$render_anomalies_modal = $this->render('compte/modal/anomalies/index.html.twig', [
			'compte' => $compte,
			'anomalies' => $anomalies,
		])->getContent();

		return new JsonResponse([
			'render' => $render,
			'render_last_actions' => $render_last_actions,
			'render_anomalies' => $render_anomalies,
			'render_anomalies_modal' => $render_anomalies_modal,
			'solde' => $solde,
			'soldeFinMensuel' => $soldeFinMensuel,
		]);
	}

	#[Route('/{id}/anomaly/{operation}/resolve', name: '_anomaly_resolve', methods: ['POST'])]
	public function resolveAnomaly(Compte $compte, Operation $operation, Request $request): JsonResponse
	{
		$this->denyAccessUnlessGranted(CompteVoter::ACCESS, $compte);

		if (!$request->isXmlHttpRequest()){
			return new JsonResponse(['resolved' => false, 'error' => 'Requete ajax uniquement.'], Response::HTTP_BAD_REQUEST);
		}
		if ($operation->getSubcategory()->getCategory()->getCompte()->getId() !== $compte->getId()){
			return new JsonResponse(['resolved' => false, 'error' => 'Anomalie introuvable pour ce compte.'], Response::HTTP_NOT_FOUND);
		}
		if (!$this->isCsrfTokenValid('resolve-operation-anomaly'.$operation->getId(), (string) $request->request->get('_token'))){
			return new JsonResponse(['resolved' => false, 'error' => 'Jeton de securite invalide.'], Response::HTTP_FORBIDDEN);
		}
		if (!$operation->isActif() || !$operation->isAnticipe() || $operation->getDate() >= new \DateTimeImmutable('today')){
			return new JsonResponse(['resolved' => false, 'error' => "Cette operation n'est plus une anomalie."], Response::HTTP_CONFLICT);
		}
		$resolution = (string) $request->request->get('resolution');
		if (!in_array($resolution, ['realize', 'delete'], true)){
			return new JsonResponse(['resolved' => false, 'error' => 'Solution de correction invalide.'], Response::HTTP_BAD_REQUEST);
		}

		$beforeSnapshot = $this->createOperationSnapshot($operation);
		$actionDate = $this->nextOperationActionDate($compte->getId());
		$actionType = 'delete' === $resolution ? 'del' : 'edit';
		$reusableAction = $this->oar->findReusableAnomalyResolution($operation, $resolution);
		$operation
			->setAnticipe('realize' === $resolution ? false : $operation->isAnticipe())
			->setActif('delete' !== $resolution)
			->setLastAction($actionType)
			->setDateLastAction(clone $actionDate)
		;

		if (null !== $reusableAction){
			$this->or->add($operation);
			$reusableAction
				->setActionAt(clone $actionDate)
				->setAfterSnapshot($this->createOperationSnapshot($operation))
				->setCancelled(false)
				->setUndoSnapshot(null)
			;
			$this->oar->add($reusableAction, true);
		} else {
			$this->or->add($operation, true);
			$this->recordOperationAction($operation, $actionType, $actionDate, $beforeSnapshot);
		}

		return new JsonResponse([
			'resolved' => true,
			'resolution' => $resolution,
			'reusedAction' => null !== $reusableAction,
		]);
	}

	private function resolveMonthDisplay(Request $request, int $currentMonth): array
	{
		$requestedMode = $request->query->get('months', 'year');
		$mode = is_string($requestedMode) && array_key_exists($requestedMode, self::MONTH_DISPLAY_OPTIONS)
			? $requestedMode
			: 'year'
		;
		$radius = self::MONTH_DISPLAY_OPTIONS[$mode]['radius'];

		$visibleMonths = null === $radius
			? array_keys(self::MONTHS)
			: range(max(1, $currentMonth - $radius), min(12, $currentMonth + $radius))
		;

		return [$mode, $visibleMonths];
	}

	private function resolveYear(Request $request, int $currentYear): int
	{
		$requestedYear = $request->query->get('year');
		if (!is_scalar($requestedYear)){
			return $currentYear;
		}

		$year = filter_var((string) $requestedYear, FILTER_VALIDATE_INT, [
			'options' => [
				'min_range' => $this->navigation_min_year,
				'max_range' => $this->navigation_max_year,
			],
		]);

		return false === $year ? $currentYear : $year;
	}

	/**
	 * @return array{future: int[], past: int[]}
	 */
	private function yearOptions(int $compteId, int $currentYear): array
	{
		$futureYears = range($currentYear, min($currentYear + 10, $this->navigation_max_year));
		$pastBudgetYears = array_filter(
			$this->catr->yearsWithBudgetForCompte($compteId, $currentYear),
			fn (int $year): bool => $year >= $this->navigation_min_year
		);

		return [
			'future' => $futureYears,
			'past' => array_values($pastBudgetYears),
		];
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
	 * Renvoie la couleur du solde selon l'alerte
	 */
	public function colorSolde($solde, $decouvert)
	{
		if ($solde == 0){
			$color = 'neutre';
		} elseif ($solde > 0){
			$color = 'pos';
		} elseif ($solde < ($decouvert * -1)){
			$color = 'neg';
		} else {
			$color = 'dec';
		}

		return $color;
	}

	private function soldeFinMensuel(float $solde, array $operationsPosDatas, array $operationsNegDatas, int $year, int $currentYear, int $currentMonth)
	{
		if ($currentYear !== $year){
			return false;
		}

		return round(
			$solde
			+ $this->anticipatedTotalUntilMonth($operationsPosDatas, $currentMonth)
			+ $this->anticipatedTotalUntilMonth($operationsNegDatas, $currentMonth),
			2
		);
	}

	private function anticipatedTotalUntilMonth(array $operationsDatas, int $monthLimit): float
	{
		$total = 0.0;

		if (!isset($operationsDatas['totaux_mois'])){
			return $total;
		}

		foreach ($operationsDatas['totaux_mois'] as $month => $monthTotals){
			if ((int) $month <= $monthLimit && isset($monthTotals['anticipe'])){
				$total += (float) $monthTotals['anticipe'];
			}
		}

		return $total;
	}
	/**
	 * Renvoie array avec gains mensuels + cumulé
	 */
	public function gains($opes_pos, $opes_neg): Array
	{
		// Gains
		$gains = [];

		// Pos
		foreach($opes_pos as $ope){

			$mois = $ope->getDate()->format('n');

			// Total by month
			isset($gains[$mois]['gain'])
				? $gains[$mois]['gain'] += $ope->getNumber()
				: $gains[$mois]['gain'] = $ope->getNumber()
			;
		}

		// Neg
		foreach($opes_neg as $ope){

			$mois = $ope->getDate()->format('n');

			// Total by month
			isset($gains[$mois]['gain'])
				? $gains[$mois]['gain'] -= $ope->getNumber()
				: $gains[$mois]['gain'] = -$ope->getNumber()
			;
		}

		// Cumulé
		$cumule = 0;
		ksort($gains);
		foreach($gains as $key => $mois){
			$cumule += $mois['gain'];
			$gains[$key]['cumule'] = $cumule;
		}

		return $gains;
	}

	/**
	 * @Route("/{id}/edit", name="_edit", methods={"GET", "POST"})
	 */
	#[Route("/{id}/edit", name: "_edit", methods: ["GET", "POST"])]
	public function edit(Compte $compte, Request $request): Response
	{
		$this->denyAccessUnlessGranted(CompteVoter::ACCESS, $compte);

		$form = $this->createForm(CompteType::class, $compte);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()){

			// Devient unique main si true
			if ($compte->getMain() == true){
				$user_comptes = $this->getUser()->getComptes();
				foreach ($user_comptes as $c){
					if ($compte->getId() != $c->getId()){
						$c->setMain(false);
						$this->cr->add($c, true);
					}
				}
			}

			// Save
			$this->cr->add($compte, true);

			return $this->redirectToRoute('compte', [], Response::HTTP_SEE_OTHER);
		}

		return $this->render('compte/edit.html.twig', [
			'compte' => $compte,
			'form' => $form->createView(),
		]);
	}

	/**
	 * @Route("/{id}", name="_delete", methods={"POST"})
	 */
	#[Route("/{id}", name: "_delete", methods: ["POST"])]
	public function delete(Compte $compte, Request $request): Response
	{
		$this->denyAccessUnlessGranted(CompteVoter::ACCESS, $compte);

		if ($this->isCsrfTokenValid('delete'.$compte->getId(), $request->request->get('_token'))) {
			$this->cr->remove($compte, true);
		}

		return $this->redirectToRoute('compte', [], Response::HTTP_SEE_OTHER);
	}

	// ****************
	// MODAL OPERATIONS
	// ****************

	/**
	 * @Route("/operation/{sc}/{year}/{month}/{sign}", name="_operation", methods={"POST"})
	 * Renvoie les opérations selon la sc, l'année, le mois et le signe
	 * Ajax only
	 */
	#[Route("/operation/{sc}/{year}/{month}/{sign}", name: "_operation", methods: ["POST"])]
	public function operation_datas(SubCategory $sc, $year, $month, $sign, Request $request): Response
	{
		$this->denyAccessUnlessGranted(CompteVoter::ACCESS, $sc->getCategory()->getCompte());

		// Control request
		if (!$request->isXmlHttpRequest()){ throw new HttpException('500', 'Requête ajax uniquement'); }

		$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		$datas['days_in_month'] = $daysInMonth;
		$datas['subcategory_libelle'] = $sc->getLibelle();
		$datas['category_libelle'] = $sc->getCategory()->getLibelle();
		$datas['operations'] = $this->or->gestion($sc, $year, $month, $sign, $daysInMonth);
		$datas['addRender'] = $this->operation_add($month, $year, $daysInMonth, $sign);
		$datas['tBodyRender'] = $this->operation_tbody($datas['operations'], $month, $year, $daysInMonth, $sign);

		return new JsonResponse($datas);
	}

	/**
	 * @Route("/operation/save/{sc}/{year}/{month}/{sign}", name="_operation_save", methods={"POST"})
	 * Sauvegarde les opérations d'une sc
	 * Ajax only
	 */
	#[Route("/operation/save/{sc}/{year}/{month}/{sign}", name: "_operation_save", methods: ["POST"])]
	public function operation_save(SubCategory $sc, $year, $month, $sign, Request $request): Response
	{
		$this->denyAccessUnlessGranted(CompteVoter::ACCESS, $sc->getCategory()->getCompte());

		// Control request
		if (!$request->isXmlHttpRequest()){ throw new HttpException('500', 'Requête ajax uniquement'); }

		// Datas from ajax
		$datas = isset($request->request->all()['datas'])
			? $request->request->all()['datas']
			: []
		;

		$current_date = $this->nextOperationActionDate($sc->getCategory()->getCompte()->getId());
	
		// Save
		foreach($datas as $ope){
			$ope_ent = null;
			if (!empty($ope['id'])){
				$ope_ent = $this->or->find($ope['id']);
				if (null === $ope_ent || $ope_ent->getSubcategory()->getId() !== $sc->getId()){
					return new JsonResponse(['save' => false, 'error' => 'Operation introuvable pour cette sous-categorie.'], Response::HTTP_NOT_FOUND);
				}
			}

			// Delete
			if ((int) ($ope['delete'] ?? 0) == 1){
				if (null === $ope_ent){
					return new JsonResponse(['save' => false, 'error' => 'Operation a supprimer manquante.'], Response::HTTP_BAD_REQUEST);
				}
				$beforeSnapshot = $this->createOperationSnapshot($ope_ent);
				$ope_ent
					->setActif(false)
					->setLastAction('del')
					->setDateLastAction(clone $current_date)
				;
				$this->or->add($ope_ent, true);
				$this->recordOperationAction($ope_ent, 'del', $current_date, $beforeSnapshot);
				$current_date->modify('+1 second');
				continue;
			}

			$numberValue = $ope['number'] ?? null;
			$anticipatedValue = $ope['anticipe'] ?? null;
			$hasNumber = $this->isOperationAmount($numberValue);
			$hasAnticipated = $this->isOperationAmount($anticipatedValue);
			if (!$hasNumber && !$hasAnticipated){
				continue;
			}

			$date = new \Datetime($ope['year'].'/'.$ope['month'].'/'.$ope['day']);
			$number = $hasNumber ? (float) $numberValue : (float) $anticipatedValue;
			$anticipe = !$hasNumber;
			$comment = $ope['comment'] ?? null;

			// Edit
			if (!empty($ope['id'])){
				$beforeSnapshot = $this->createOperationSnapshot($ope_ent);
				$changed =
					$number !== (float) $ope_ent->getNumber() ||
					$anticipe !== $ope_ent->isAnticipe() ||
					$date->format('Y-m-d') !== $ope_ent->getDate()->format('Y-m-d') ||
					$comment !== $ope_ent->getComment()
				;
				if (!$changed){
					continue;
				}
				$actionType = 'edit';

			// Add
			} else {
				$beforeSnapshot = null;
				$actionType = 'create';
				$ope_ent = (new Operation())->setSubcategory($sc);
			}

			$ope_ent
				->setNumber($number)
				->setDate($date)
				->setComment($comment)
				->setAnticipe($anticipe)
				->setActif(true)
				->setLastAction($actionType)
				->setDateLastAction(clone $current_date)
			;
			$this->or->add($ope_ent, true);
			$this->recordOperationAction($ope_ent, $actionType, $current_date, $beforeSnapshot);
			$current_date->modify('+1 second');
		}

		return new JsonResponse(['save' => true]);
	}

	#[Route('/{id}/categories/reorder', name: '_category_reorder', methods: ['POST'])]
	public function reorderCategories(Compte $compte, Request $request): JsonResponse
	{
		$this->denyAccessUnlessGranted(CompteVoter::ACCESS, $compte);

		if (!$request->isXmlHttpRequest()){
			return new JsonResponse(['moved' => false, 'error' => 'Requete ajax uniquement.'], Response::HTTP_BAD_REQUEST);
		}
		if (!$this->isCsrfTokenValid('reorder-categories'.$compte->getId(), (string) $request->request->get('_token'))){
			return new JsonResponse(['moved' => false, 'error' => 'Jeton de securite invalide.'], Response::HTTP_FORBIDDEN);
		}

		$categoryId = filter_var($request->request->get('category'), FILTER_VALIDATE_INT);
		$beforeId = filter_var($request->request->get('before'), FILTER_VALIDATE_INT);
		$category = false === $categoryId ? null : $this->catr->find($categoryId);
		if (null === $category || $category->getCompte()->getId() !== $compte->getId()){
			return new JsonResponse(['moved' => false, 'error' => 'Categorie introuvable pour ce compte.'], Response::HTTP_NOT_FOUND);
		}

		$beforeCategory = false === $beforeId ? null : $this->catr->find($beforeId);
		if (null !== $beforeCategory && $beforeCategory->getId() === $category->getId()){
			return new JsonResponse(['moved' => false, 'unchanged' => true]);
		}
		if (null !== $beforeCategory && (
			$beforeCategory->getCompte()->getId() !== $compte->getId() ||
			$beforeCategory->isSign() !== $category->isSign() ||
			$beforeCategory->getYear() !== $category->getYear()
		)){
			return new JsonResponse(['moved' => false, 'error' => 'Zone de destination invalide.'], Response::HTTP_CONFLICT);
		}

		$categories = $this->catr->findOrderedForBudget(
			$compte->getId(),
			(bool) $category->isSign(),
			(int) $category->getYear()
		);
		$beforeSnapshot = $this->createCategoryOrderSnapshot($categories);
		$beforeSnapshot['position'] = $category->getPosition();
		$orderedCategories = array_values(array_filter(
			$categories,
			static fn (Category $candidate): bool => $candidate->getId() !== $category->getId()
		));

		$targetIndex = count($orderedCategories);
		if (null !== $beforeCategory){
			foreach ($orderedCategories as $index => $candidate){
				if ($candidate->getId() === $beforeCategory->getId()){
					$targetIndex = $index;
					break;
				}
			}
		}
		array_splice($orderedCategories, $targetIndex, 0, [$category]);

		$nextOrder = array_map(static fn (Category $candidate): int => $candidate->getId(), $orderedCategories);
		if ($nextOrder === $beforeSnapshot['order']){
			return new JsonResponse(['moved' => false, 'unchanged' => true]);
		}

		foreach ($orderedCategories as $index => $candidate){
			$candidate->setPosition($index + 1);
			$this->catr->add($candidate);
		}
		$afterSnapshot = $this->createCategoryOrderSnapshot($orderedCategories);
		$afterSnapshot['position'] = $category->getPosition();
		$action = (new OperationAction())
			->setCategory($category)
			->setActionType('move')
			->setActionAt($this->nextOperationActionDate($compte->getId()))
			->setBeforeSnapshot($beforeSnapshot)
			->setAfterSnapshot($afterSnapshot)
		;
		$this->oar->add($action, true);

		return new JsonResponse(['moved' => true]);
	}

	#[Route('/{id}/subcategories/reorder', name: '_subcategory_reorder', methods: ['POST'])]
	public function reorderSubCategories(Compte $compte, Request $request): JsonResponse
	{
		$this->denyAccessUnlessGranted(CompteVoter::ACCESS, $compte);

		if (!$request->isXmlHttpRequest()){
			return new JsonResponse(['moved' => false, 'error' => 'Requete ajax uniquement.'], Response::HTTP_BAD_REQUEST);
		}
		if (!$this->isCsrfTokenValid('reorder-subcategories'.$compte->getId(), (string) $request->request->get('_token'))){
			return new JsonResponse(['moved' => false, 'error' => 'Jeton de securite invalide.'], Response::HTTP_FORBIDDEN);
		}

		$subCategoryId = filter_var($request->request->get('subcategory'), FILTER_VALIDATE_INT);
		$beforeId = filter_var($request->request->get('before'), FILTER_VALIDATE_INT);
		$categoryId = filter_var($request->request->get('category'), FILTER_VALIDATE_INT);
		$subCategory = false === $subCategoryId ? null : $this->scr->find($subCategoryId);
		if (null === $subCategory || $subCategory->getCategory()->getCompte()->getId() !== $compte->getId()){
			return new JsonResponse(['moved' => false, 'error' => 'Sous-categorie introuvable pour ce compte.'], Response::HTTP_NOT_FOUND);
		}

		$category = $subCategory->getCategory();
		if (false !== $categoryId && $categoryId !== $category->getId()){
			return new JsonResponse(['moved' => false, 'error' => 'Zone de destination invalide.'], Response::HTTP_CONFLICT);
		}

		$beforeSubCategory = false === $beforeId ? null : $this->scr->find($beforeId);
		if (null !== $beforeSubCategory && $beforeSubCategory->getId() === $subCategory->getId()){
			return new JsonResponse(['moved' => false, 'unchanged' => true]);
		}
		if (null !== $beforeSubCategory && $beforeSubCategory->getCategory()->getId() !== $category->getId()){
			return new JsonResponse(['moved' => false, 'error' => 'Zone de destination invalide.'], Response::HTTP_CONFLICT);
		}

		$subCategories = $this->scr->findOrderedForCategory($category->getId());
		$beforeSnapshot = $this->createSubCategoryOrderSnapshot($category, $subCategories, $subCategory);
		$orderedSubCategories = array_values(array_filter(
			$subCategories,
			static fn (SubCategory $candidate): bool => $candidate->getId() !== $subCategory->getId()
		));

		$targetIndex = count($orderedSubCategories);
		if (null !== $beforeSubCategory){
			foreach ($orderedSubCategories as $index => $candidate){
				if ($candidate->getId() === $beforeSubCategory->getId()){
					$targetIndex = $index;
					break;
				}
			}
		}
		array_splice($orderedSubCategories, $targetIndex, 0, [$subCategory]);

		$nextOrder = array_map(static fn (SubCategory $candidate): int => $candidate->getId(), $orderedSubCategories);
		if ($nextOrder === $beforeSnapshot['order']){
			return new JsonResponse(['moved' => false, 'unchanged' => true]);
		}

		foreach ($orderedSubCategories as $index => $candidate){
			$candidate->setPosition($index + 1);
			$this->scr->add($candidate);
		}
		$afterSnapshot = $this->createSubCategoryOrderSnapshot($category, $orderedSubCategories, $subCategory);
		$action = (new OperationAction())
			->setCategory($category)
			->setActionType('move')
			->setActionAt($this->nextOperationActionDate($compte->getId()))
			->setBeforeSnapshot($beforeSnapshot)
			->setAfterSnapshot($afterSnapshot)
		;
		$this->oar->add($action, true);

		return new JsonResponse(['moved' => true]);
	}

	#[Route('/{id}/operation/action/{action}/undo', name: '_operation_action_undo', methods: ['POST'])]
	public function undoOperationAction(Compte $compte, int $action, Request $request): JsonResponse
	{
		$this->denyAccessUnlessGranted(CompteVoter::ACCESS, $compte);

		if (!$request->isXmlHttpRequest()){
			return new JsonResponse(['undo' => false, 'error' => 'Requete ajax uniquement.'], Response::HTTP_BAD_REQUEST);
		}
		if (!$this->isCsrfTokenValid('undo-operation-action'.$action, (string) $request->request->get('_token'))){
			return new JsonResponse(['undo' => false, 'error' => 'Jeton de securite invalide.'], Response::HTTP_FORBIDDEN);
		}

		$operationAction = $this->oar->find($action);
		$actionCompteId = $operationAction?->getCategory()?->getCompte()?->getId()
			?? $operationAction?->getOperation()?->getSubcategory()?->getCategory()?->getCompte()?->getId()
		;
		if (null === $operationAction || $actionCompteId !== $compte->getId()){
			return new JsonResponse(['undo' => false, 'error' => 'Action introuvable pour ce compte.'], Response::HTTP_NOT_FOUND);
		}
		if (!$operationAction->isUndoable()){
			return new JsonResponse(['undo' => false, 'error' => "Cette action n'est pas annulable."], Response::HTTP_CONFLICT);
		}
		if ($operationAction->isSubCategoryMove()){
			return $this->undoSubCategoryMoveAction($compte, $operationAction);
		}
		if ($operationAction->isCategoryMove()){
			return $this->undoCategoryMoveAction($compte, $operationAction);
		}

		$operation = $operationAction->getOperation();
		if ($operationAction->isCancelled()){
			$this->restoreOperationSnapshot($operation, $operationAction->getUndoSnapshot());
			$operationAction
				->setCancelled(false)
				->setUndoSnapshot(null)
			;
			$this->or->add($operation);
			$this->oar->add($operationAction, true);

			return new JsonResponse(['undo' => true, 'undoReverted' => true]);
		}

		$beforeUndo = $this->createOperationSnapshot($operation);
		if ('create' === $operationAction->getActionType()){
			$operation->setActif(false);
		} else {
			$this->restoreOperationSnapshot($operation, $operationAction->getBeforeSnapshot());
		}

		$actionDate = $this->nextOperationActionDate($compte->getId());
		$operation
			->setLastAction('undo')
			->setDateLastAction($actionDate)
		;
		$operationAction
			->setCancelled(true)
			->setUndoSnapshot($beforeUndo)
		;
		$this->or->add($operation);
		$this->oar->add($operationAction, true);

		return new JsonResponse(['undo' => true, 'undoReverted' => false]);
	}

	private function recordOperationAction(
		Operation $operation,
		string $actionType,
		\DateTimeInterface $actionAt,
		?array $beforeSnapshot
	): void
	{
		$action = (new OperationAction())
			->setOperation($operation)
			->setActionType($actionType)
			->setActionAt(clone $actionAt)
			->setBeforeSnapshot($beforeSnapshot)
			->setAfterSnapshot($this->createOperationSnapshot($operation))
		;
		$this->oar->add($action, true);
	}

	private function undoCategoryMoveAction(Compte $compte, OperationAction $action): JsonResponse
	{
		$category = $action->getCategory();
		$categories = $this->catr->findOrderedForBudget(
			$compte->getId(),
			(bool) $category->isSign(),
			(int) $category->getYear()
		);
		$currentSnapshot = $this->createCategoryOrderSnapshot($categories);

		if ($action->isCancelled()){
			$this->restoreCategoryOrderSnapshot($compte, $action->getUndoSnapshot());
			$action
				->setCancelled(false)
				->setUndoSnapshot(null)
			;
			$this->oar->add($action, true);

			return new JsonResponse(['undo' => true, 'undoReverted' => true]);
		}

		$this->restoreCategoryOrderSnapshot($compte, $action->getBeforeSnapshot());
		$action
			->setCancelled(true)
			->setUndoSnapshot($currentSnapshot)
		;
		$this->oar->add($action, true);

		return new JsonResponse(['undo' => true, 'undoReverted' => false]);
	}

	private function undoSubCategoryMoveAction(Compte $compte, OperationAction $action): JsonResponse
	{
		$category = $action->getCategory();
		$subCategories = $this->scr->findOrderedForCategory($category->getId());
		$currentSnapshot = $this->createSubCategoryOrderSnapshot($category, $subCategories);

		if ($action->isCancelled()){
			$this->restoreSubCategoryOrderSnapshot($compte, $action->getUndoSnapshot());
			$action
				->setCancelled(false)
				->setUndoSnapshot(null)
			;
			$this->oar->add($action, true);

			return new JsonResponse(['undo' => true, 'undoReverted' => true]);
		}

		$this->restoreSubCategoryOrderSnapshot($compte, $action->getBeforeSnapshot());
		$action
			->setCancelled(true)
			->setUndoSnapshot($currentSnapshot)
		;
		$this->oar->add($action, true);

		return new JsonResponse(['undo' => true, 'undoReverted' => false]);
	}

	/**
	 * @param Category[] $categories
	 */
	private function createCategoryOrderSnapshot(array $categories): array
	{
		$firstCategory = $categories[0] ?? null;

		return [
			'order' => array_map(static fn (Category $category): int => $category->getId(), $categories),
			'year' => $firstCategory?->getYear(),
			'sign' => $firstCategory?->isSign(),
		];
	}

	/**
	 * @param SubCategory[] $subCategories
	 */
	private function createSubCategoryOrderSnapshot(Category $category, array $subCategories, ?SubCategory $movedSubCategory = null): array
	{
		return [
			'scope' => 'subcategory',
			'categoryId' => $category->getId(),
			'categoryLabel' => $category->getLibelle(),
			'subcategoryId' => $movedSubCategory?->getId(),
			'subcategoryLabel' => $movedSubCategory?->getLibelle(),
			'position' => $movedSubCategory?->getPosition(),
			'order' => array_map(static fn (SubCategory $subCategory): int => $subCategory->getId(), $subCategories),
		];
	}

	private function restoreCategoryOrderSnapshot(Compte $compte, array $snapshot): void
	{
		$categories = $this->catr->findOrderedForBudget(
			$compte->getId(),
			(bool) ($snapshot['sign'] ?? false),
			(int) ($snapshot['year'] ?? 0)
		);
		$categoriesById = [];
		foreach ($categories as $category){
			$categoriesById[$category->getId()] = $category;
		}

		$orderedCategories = [];
		foreach (($snapshot['order'] ?? []) as $categoryId){
			if (isset($categoriesById[$categoryId])){
				$orderedCategories[] = $categoriesById[$categoryId];
				unset($categoriesById[$categoryId]);
			}
		}
		array_push($orderedCategories, ...array_values($categoriesById));

		foreach ($orderedCategories as $index => $category){
			$category->setPosition($index + 1);
			$this->catr->add($category);
		}
	}

	private function restoreSubCategoryOrderSnapshot(Compte $compte, array $snapshot): void
	{
		$category = $this->catr->find($snapshot['categoryId'] ?? null);
		if (null === $category || $category->getCompte()->getId() !== $compte->getId()){
			return;
		}

		$subCategories = $this->scr->findOrderedForCategory($category->getId());
		$subCategoriesById = [];
		foreach ($subCategories as $subCategory){
			$subCategoriesById[$subCategory->getId()] = $subCategory;
		}

		$orderedSubCategories = [];
		foreach (($snapshot['order'] ?? []) as $subCategoryId){
			if (isset($subCategoriesById[$subCategoryId])){
				$orderedSubCategories[] = $subCategoriesById[$subCategoryId];
				unset($subCategoriesById[$subCategoryId]);
			}
		}
		array_push($orderedSubCategories, ...array_values($subCategoriesById));

		foreach ($orderedSubCategories as $index => $subCategory){
			$subCategory->setPosition($index + 1);
			$this->scr->add($subCategory);
		}
	}

	private function createOperationSnapshot(Operation $operation): array
	{
		return [
			'number' => $operation->getNumber(),
			'anticipe' => $operation->isAnticipe(),
			'date' => $operation->getDate()->format(DATE_ATOM),
			'comment' => $operation->getComment(),
			'actif' => $operation->isActif(),
			'lastAction' => $operation->getLastAction(),
			'dateLastAction' => $operation->getDateLastAction()->format(DATE_ATOM),
		];
	}

	private function restoreOperationSnapshot(Operation $operation, array $snapshot): void
	{
		$operation
			->setNumber((float) $snapshot['number'])
			->setAnticipe((bool) $snapshot['anticipe'])
			->setDate(new \DateTime($snapshot['date']))
			->setComment($snapshot['comment'])
			->setActif((bool) $snapshot['actif'])
			->setLastAction($snapshot['lastAction'])
			->setDateLastAction(new \DateTime($snapshot['dateLastAction']))
		;
	}

	private function nextOperationActionDate(int $compteId): \DateTime
	{
		$actionDate = new \DateTime(date('Y-m-d H:i:s'));
		$lastAction = $this->oar->findLatestForCompte($compteId);
		if (null !== $lastAction && $lastAction->getActionAt() >= $actionDate){
			$actionDate = (clone $lastAction->getActionAt())->modify('+1 second');
		}

		return $actionDate;
	}

	private function isOperationAmount($value): bool
	{
		return null !== $value && !in_array((string) $value, ['', '0', 'NaN', 'Nan'], true);
	}

	/**
	 * Renvoie le render d'une nouvelle opération
	 */
	public function operation_add($month, $year, $daysInMonth, $sign)
	{
		return $this->render('compte/modal/operations/operation/_add.html.twig', [
			'sign' => $sign,
			'year' => $year,
			'month' => (int) $month,
			'daysInMonth' => $daysInMonth,
			'day' => date('n') == $month ? date('d') : 1,
		])->getContent();
	}

	/**
	 * Renvoie le render du tbody
	 */
	public function operation_tbody($operations, $month, $year, $daysInMonth, $sign)
	{
		return $this->render('compte/modal/operations/operation/_tbody.html.twig', [
			'operations' => $operations,
			'sign' => $sign,
			'year' => $year,
			'month' => (int) $month,
			'daysInMonth' => $daysInMonth,
			'day' => date('n') == $month ? date('d') : 1,
		])->getContent();
	}

	// ****************
	// MODAL CATEGORY
	// ****************

	/**
	 * @Route("/cat/{id}/{cat}/{sign}", name="_category", methods={"POST"})
	 * Récupère datas d'une catégorie
	 * Ajax only
	 */
	#[Route("/cat/{id}/{cat}/{sign}", name: "_category", methods: ["POST"])]
	public function category(
		#[MapEntity(id: 'id')] Compte $compte,
		#[MapEntity(id: 'cat')] Category $cat,
		$sign,
		Request $request
	): Response
	{
		$this->denyAccessUnlessGranted(CompteVoter::ACCESS, $compte);
		if ($cat->getCompte()->getId() !== $compte->getId()){
			throw $this->createNotFoundException('Categorie introuvable pour ce compte.');
		}

		// Control request
		if (!$request->isXmlHttpRequest()){ throw new HttpException('500', 'Requête ajax uniquement'); }

		$delete = $this->or->countOpeByCat($cat->getId()) == 0
			? true
			: false
		;

		$render = $this->render('compte/modal/category/table/_tbody.html.twig', [
			'category' => $cat,
			'delete' => $delete,
			'categories_before' => $this->catr->mycategoriesBefore($compte->getId(), $sign, $cat->getPosition()),
			'categories_after' => $this->catr->mycategoriesAfter($compte->getId(), $sign, $cat->getPosition()),
		])->getContent();

		return new JsonResponse([
			'render' => $render,
		]);
	}

	/**
	 * @Route("/caty/add/{id}/{sign}", name="_category_add", methods={"POST"})
	 * Renvoie le render d'une nouvelle catégorie
	 * URL: Caty au lieu de cat a cause d'un bug ParamConverter
	 * Ajax only
	 */
	#[Route("/caty/add/{id}/{sign}", name: "_category_add", methods: ["POST"])]
	public function category_add(Compte $compte, $sign, Request $request): Response
	{
		$this->denyAccessUnlessGranted(CompteVoter::ACCESS, $compte);

		// Control request
		if (!$request->isXmlHttpRequest()){ throw new HttpException('500', 'Requête ajax uniquement'); }

		$cat = new Category();
		$cat->setCompte($compte);

		$render = $this->render('compte/modal/category/table/_tbody.html.twig', [
			'category' => $cat,
			'categories_before' => $this->catr->mycategoriesBefore($compte->getId(), $sign, $cat->getPosition()),
			'categories_after' => $this->catr->mycategoriesAfter($compte->getId(), $sign, $cat->getPosition()-1),
		])->getContent();

		return new JsonResponse([
			'render' => $render,
		]);
	}

	/**
	 * @Route("/{compte}/cat/save/{year}", name="_category_save", methods={"POST"})
	 * Edit tr_category / Edit tr_subcategories / Add tr_subcategories_add
	 * URL: Caty au lieu de cat a cause d'un bug ParamConverter
	 * Ajax only
	 */
	#[Route("/{compte}/cat/save/{year}", name: "_category_save", methods: ["POST"])]
	public function category_save(Compte $compte, $year, Request $request): Response
	{
		$this->denyAccessUnlessGranted(CompteVoter::ACCESS, $compte);

		// Control request
		if (!$request->isXmlHttpRequest()){
			return new JsonResponse(['save' => false, 'error' => 'Requete ajax uniquement.'], Response::HTTP_BAD_REQUEST);
		}

		$datas = $request->request->all('datas');
		if (!isset($datas[0]) || !is_array($datas[0]) || ($datas[0]['type'] ?? null) !== 'cat'){
			return new JsonResponse(['save' => false, 'error' => 'Donnees de categorie invalides.'], Response::HTTP_BAD_REQUEST);
		}

		// Categorie
		$scs = [];
		$datas_cat = $datas[0];

		// Edit
		if (($datas_cat['id'] ?? null) != 'add'){
			$cat = $this->catr->find($datas_cat['id'] ?? null);
			if (null === $cat || $cat->getCompte()->getId() !== $compte->getId()){
				return new JsonResponse(['save' => false, 'error' => 'Categorie introuvable pour ce compte.'], Response::HTTP_NOT_FOUND);
			}
			$scs = $this->scr->idsFromCat($cat->getId());

		// Add
		} else {
			$cat = new Category();
			$cat
				->setCompte($compte)
				->setSign((bool) ($datas_cat['sign'] ?? true))
				->setYear((int) $year)
			;
		}

		// Commun Edit
		$cat
			->setLibelle(trim((string) ($datas_cat['libelle'] ?? '')))
			->setPosition((int) ($datas_cat['position'] ?? 1))
		;

		if ('' === $cat->getLibelle()){
			return new JsonResponse(['save' => false, 'error' => 'Le libelle de la categorie est obligatoire.'], Response::HTTP_BAD_REQUEST);
		}

		// Save
		$this->catr->add($cat, true);

		// Corrige les autres positions
		$this->orderCatPosition($compte->getId(), $cat->getId(), $cat->isSign(), $year, $cat->getPosition());

		unset($datas[0]);

		// Sub-categories
		foreach ($datas as $key => $datas_sc){
			if (!is_array($datas_sc) || ($datas_sc['type'] ?? null) !== 'sc'){
				return new JsonResponse(['save' => false, 'error' => 'Donnees de sous-categorie invalides.'], Response::HTTP_BAD_REQUEST);
			}

			// Edit
			if (($datas_sc['id'] ?? '') != ''){
				$sc = $this->scr->find($datas_sc['id']);
				if (null === $sc || $sc->getCategory()->getId() !== $cat->getId()){
					return new JsonResponse(['save' => false, 'error' => 'Sous-categorie introuvable pour cette categorie.'], Response::HTTP_NOT_FOUND);
				}
				unset($scs[$datas_sc['id']]);

			// Add
			} else {
				$sc = new SubCategory();
				$sc->setCategory($cat);
			}

			$sc
				->setPosition((int) $key)
				->setLibelle(trim((string) ($datas_sc['libelle'] ?? '')))
			;

			if ('' === $sc->getLibelle()){
				return new JsonResponse(['save' => false, 'error' => 'Le libelle des sous-categories est obligatoire.'], Response::HTTP_BAD_REQUEST);
			}

			$this->scr->add($sc, true);
		}

		// Delete SubCategories
		foreach($scs as $key => $osef){
			$sc = $this->scr->find($key);
			if (null !== $sc){
				$this->scr->remove($sc, true);
			}
		}

		return new JsonResponse(['save' => true]);
	}

	/**
	 * @Route("/cat/delete/{id}/{cat}", name="_category_delete", methods={"POST"})
	 * Delete category
	 * URL: Caty au lieu de cat a cause d'un bug ParamConverter
	 * Ajax only
	 */
	#[Route("/cat/delete/{id}/{cat}", name: "_category_delete", methods: ["POST"], priority: 10)]
	public function category_delete(
		#[MapEntity(id: 'id')] Compte $compte,
		#[MapEntity(id: 'cat')] Category $cat,
		Request $request
	): Response
	{
		$this->denyAccessUnlessGranted(CompteVoter::ACCESS, $compte);
		if ($cat->getCompte()->getId() !== $compte->getId()){
			throw $this->createNotFoundException('Categorie introuvable pour ce compte.');
		}

		// Control request
		if (!$request->isXmlHttpRequest()){ throw new HttpException('500', 'Requête ajax uniquement'); }

		// Delete SubCategories
		$scs = $cat->getSubCategories();
		foreach($scs as $sc){

			// Delete Operations
			$ops = $sc->getOperations();
			foreach($ops as $ope){
				$this->or->remove($ope);
			}

			$this->scr->remove($sc);
		}

		// Delete Cat
		$this->catr->remove($cat, true);

		// Corrige les autres positions
		$this->orderCatPosition($compte->getId(), $cat->getId(), $cat->isSign(), $cat->getYear(), 0);

		return new JsonResponse([
			'save' => true,
		]);
	}

	/**
	 * @Route("/sc/{id}", name="_subcategory", methods={"POST"})
	 * Récupère render de tr_subcategorie_back
	 * Ajax only
	 */
	#[Route("/sc/{id}", name: "_subcategory", methods: ["POST"])]
	public function subcategory(SubCategory $sc, Request $request): Response
	{
		$this->denyAccessUnlessGranted(CompteVoter::ACCESS, $sc->getCategory()->getCompte());

		// Control request
		if (!$request->isXmlHttpRequest()){ throw new HttpException('500', 'Requête ajax uniquement'); }

		$render = $this->render('compte/modal/category/table/_tr_sc.html.twig', [
			'sc' => $sc,
		])->getContent();

		return new JsonResponse([
			'render' => $render,
		]);
	}

	/**
	 * @Route("/sc/add/{addMod}", name="_subcategory_add", methods={"POST"})
	 * Récupère render de tr_subcategories_add
	 * Ajax only
	 */
	#[Route("/sc/add/{addMod}", name: "_subcategory_add", methods: ["POST"])]
	public function subCategory_add($addMod, Request $request): Response
	{
		// Control request
		if (!$request->isXmlHttpRequest()){ throw new HttpException('500', 'Requête ajax uniquement'); }

		$render = $this->render('compte/modal/category/table/_tr_sc_add.html.twig', ['addMod' => $addMod])->getContent();

		return new JsonResponse([
			'render' => $render,
		]);
	}

	/**
	 * Edit position categories from compte
	 */
	public function orderCatPosition($compte_id, $cat_id, $sign, $year, $pos)
	{
		// Corrige les autres positions
		$allPosAfterCatPos = $this->catr->getAllPosFromCompte(
			$compte_id,
			$cat_id,
			$sign,
			$year
		);

		// Change positions
		$i = 0;
		foreach($allPosAfterCatPos as $cat){

			$i++;
			if ($i == $pos){ $i++; } // Position réservé par la cat sauvegardée

			if ($cat['position'] != $i){
				$cat_change = $this->catr->find($cat['id']);
				$cat_change->setPosition($i);
				$this->catr->add($cat_change, true);
			}
		}
	}
}
