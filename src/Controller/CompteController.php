<?php

namespace App\Controller;

use App\Entity\Compte;
use App\Entity\Category;
use App\Entity\Operation;
use App\Entity\OperationAction;
use App\Entity\SubCategory;
use App\Entity\User;

use App\Form\CompteType;
use App\Form\UserPreferenceType;

use App\Repository\CompteRepository;
use App\Repository\CategoryRepository;
use App\Repository\OperationRepository;
use App\Repository\OperationActionRepository;
use App\Repository\SubCategoryRepository;
use App\Repository\UserRepository;
use App\Security\CompteVoter;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Form\FormError;

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
		'year' => ['label' => 'Année entière', 'radius' => null, 'basis' => 'current'],
		'three_current' => ['label' => '1 mois avant / 3 mois après le mois en cours', 'before' => 1, 'after' => 3, 'basis' => 'current'],
		'three_selected' => ['label' => '1 mois avant / 3 mois après le mois sélectionné', 'before' => 1, 'after' => 3, 'basis' => 'selected'],
		'one_current' => ['label' => '1 mois avant/après le mois en cours', 'radius' => 1, 'basis' => 'current'],
		'one_selected' => ['label' => '1 mois avant/après le mois sélectionné', 'radius' => 1, 'basis' => 'selected'],
		'current_current' => ['label' => 'Mois en cours uniquement', 'radius' => 0, 'basis' => 'current'],
		'current_selected' => ['label' => 'Mois sélectionné uniquement', 'radius' => 0, 'basis' => 'selected'],
		'custom_current' => ['label' => 'Personnalisé autour du mois en cours', 'before' => 1, 'after' => 3, 'basis' => 'current', 'custom' => true],
		'custom_selected' => ['label' => 'Personnalisé autour du mois sélectionné', 'before' => 1, 'after' => 3, 'basis' => 'selected', 'custom' => true],
	];
	private const MONTH_DISPLAY_ALIASES = [
		'three' => 'three_current',
		'one' => 'one_current',
		'current' => 'current_current',
	];

	private $navigation_max_year;
	private $navigation_min_year;

	private $cr;
	private $or;
	private $oar;
	private $catr;
	private $scr;
	private $ur;

	public function __construct(
		CompteRepository $cr,
		OperationRepository $or,
		OperationActionRepository $oar,
		CategoryRepository $catr,
		SubCategoryRepository $scr,
		UserRepository $ur
	){
		$this->navigation_max_year = 9999;
		$this->navigation_min_year = 1000;
		$this->cr = $cr;
		$this->or = $or;
		$this->oar = $oar;
		$this->catr = $catr;
		$this->scr = $scr;
		$this->ur = $ur;
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
		$sharedUser = $this->resolveSharedUserFromForm($form);

		if ($form->isSubmitted() && $form->isValid()){

			$compte->setOwner($this->getUser());
			if (null !== $sharedUser){
				$compte
					->addUser($sharedUser)
					->setUserSharing(
						$sharedUser,
						(string) ($form->get('users_access')->getData() ?: 'observer'),
						(bool) $form->get('users_participant')->getData()
					)
				;
			}

			// Devient unique main si true
			if ($compte->getMain() == true){
				$user_comptes = $this->cr->getComptesByUser($this->getUser());
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
		$combinedCompte = $this->resolveCombinedCompte($request, $compte);
		$viewComptes = array_values(array_filter([$compte, $combinedCompte]));
		$viewCompteIds = array_map(fn (Compte $viewCompte): int => $viewCompte->getId(), $viewComptes);

		// Current dates
		$date = new \Datetime('now');
		$current_year = (int) $date->format('Y');
		$current_month = $date->format('n');
		$selected_month = $this->resolveSelectedMonth($request);

		// Year
		$year = $this->resolveYear($request, $current_year);
		$selected_year = $this->resolveSelectedYear($request, $year);
		$detail_months = $this->resolveDetailMonths($request);
		// This preference only hides the "Fait / A venir" label row; month columns remain detailed.
		$show_month_details = true;
		[
			$month_display,
			$visible_months,
			$visible_month_years,
			$month_display_before,
			$month_display_after,
		] = $this->resolveMonthDisplay($request, $current_year, (int) $current_month, $year, $selected_month, $selected_year, $detail_months, $show_month_details);
		$month_colspan = $this->monthColspan($visible_months, $detail_months, $show_month_details);
		$display_month_years = $this->displayMonthYears($visible_month_years, $detail_months, $show_month_details);
		$year_options = $this->yearOptions($viewCompteIds, $year);
		$anomalies = $this->or->findOverdueAnticipatedForCompte($compte->getId());
		$other_comptes = array_values(array_filter(
			$this->cr->getComptesByUser($this->getUser()),
			fn (Compte $userCompte): bool => $userCompte->getId() !== $compte->getId()
		));

		// Opérations
		$operation_years = array_values(array_unique(array_map('intval', $visible_month_years)));
		$operations_pos = $this->operationsByYearsAndComptesAndSign($viewCompteIds, $operation_years);
		$operations_neg = $this->operationsByYearsAndComptesAndSign($viewCompteIds, $operation_years, false);
		$table_operations_pos = $this->operations($operations_pos, true, $display_month_years);
		$table_operations_neg = $this->operations($operations_neg, false, $display_month_years);
		$month_anticipation_visible_pos = $this->monthAnticipationVisibility($visible_months, $visible_month_years, $table_operations_pos, $current_year, (int) $current_month);
		$month_anticipation_visible_neg = $this->monthAnticipationVisibility($visible_months, $visible_month_years, $table_operations_neg, $current_year, (int) $current_month);
		$month_anticipation_visible_merged = $this->mergeMonthAnticipationVisibility($visible_months, $month_anticipation_visible_pos, $month_anticipation_visible_neg);
		$month_colspans_pos = $this->monthColspans($visible_months, $detail_months);
		$month_colspans_neg = $this->monthColspans($visible_months, $detail_months);
		$month_colspans_merged = $this->monthColspans($visible_months, $detail_months);
		$operations_pos_datas = $this->operations($this->operationsByYearsAndComptesAndSign($viewCompteIds, [$year]));
		$operations_neg_datas = $this->operations($this->operationsByYearsAndComptesAndSign($viewCompteIds, [$year], false), false);

		// Solde
		$current_solde = $this->currentBalance($viewCompteIds);
		$combined_decouvert = array_sum(array_map(
			fn (Compte $viewCompte): int => $viewCompte->getDecouvert(),
			$viewComptes
		));

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
		$color_solde = $this->colorSolde($current_solde, $combined_decouvert);
		$color_soldeFinMois = $this->colorSolde($soldeFinMensuel, $combined_decouvert);
		$preferenceForm = $this->createForm(UserPreferenceType::class, $this->getUser()->getPreferences(), [
			'action' => $this->generateUrl('user_preference', ['id' => $this->getUser()->getId()]),
		]);
		$accountSettingsForm = $this->createForm(CompteType::class, $compte, [
			'action' => $this->generateUrl('compte_settings', ['id' => $compte->getId()]),
			'method' => 'POST',
		]);

		return $this->render('compte/show.html.twig', [
			'compte' => $compte,
			'combined_compte' => $combinedCompte,
			'view_comptes' => $viewComptes,
			'combined_decouvert' => $combined_decouvert,

			'year' => $year,
			'months' => SELF::MONTHS,
			'months_json' => json_encode(SELF::MONTHS),
			'month_display' => $month_display,
			'month_display_options' => SELF::MONTH_DISPLAY_OPTIONS,
			'month_display_before' => $month_display_before,
			'month_display_after' => $month_display_after,
			'visible_months' => $visible_months,
			'visible_month_years' => $visible_month_years,
			'detail_months' => $detail_months,
			'month_colspan' => $month_colspan,
			'display_month_count' => count($display_month_years),
			'selected_month' => $selected_month,
			'selected_year' => $selected_year,
			'year_options' => $year_options['years'],
			'budget_year_options' => $year_options['budget'],
			'other_comptes' => $other_comptes,
			'max_year' => $this->navigation_max_year,
			'min_year' => $this->navigation_min_year,

			'user' => $this->getUser(),
			'current_year' => $current_year,
			'current_month' => $current_month,

			'operations_pos' => $table_operations_pos,
			'operations_neg' => $table_operations_neg,
			'month_anticipation_visible_pos' => $month_anticipation_visible_pos,
			'month_anticipation_visible_neg' => $month_anticipation_visible_neg,
			'month_anticipation_visible_merged' => $month_anticipation_visible_merged,
			'month_colspans_pos' => $month_colspans_pos,
			'month_colspans_neg' => $month_colspans_neg,
			'month_colspans_merged' => $month_colspans_merged,

			'color_solde' => $color_solde, // Couleur d'alerte du solde
			'color_soldeFinMois' => $color_soldeFinMois, // Couleur d'alerte du solde
			'current_solde' => $current_solde, // Solde courant du compte
			'current_monthEnd' => $soldeFinMensuel, // Solde courant du compte à la fin du mois
			'gains' => $this->gains($operations_pos, $operations_neg, $display_month_years),

			'lastActions' => $this->oar->lastActionsForCompte($compte->getId()), // Last actions
			'canUndoTodayActions' => $this->oar->hasUndoableActionsForCompteToday($compte->getId(), new \DateTimeImmutable('today')),
			'anomalies' => $anomalies,
			'account_preference_form' => $preferenceForm->createView(),
			'account_settings_form' => $accountSettingsForm->createView(),
			'open_account_settings' => $request->query->getBoolean('settings'),
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
		$combinedCompte = $this->resolveCombinedCompte($request, $compte);
		$viewComptes = array_values(array_filter([$compte, $combinedCompte]));
		$viewCompteIds = array_map(fn (Compte $viewCompte): int => $viewCompte->getId(), $viewComptes);

		// Control request
		if (!$request->isXmlHttpRequest()){ throw new HttpException('500', 'Requête ajax uniquement'); }

		// Current dates
		$date = new \Datetime('now');
		$current_year = (int) $date->format('Y');
		$current_month = $date->format('n');
		$selected_month = $this->resolveSelectedMonth($request);

		// Year
		$year = $this->resolveYear($request, $current_year);
		$selected_year = $this->resolveSelectedYear($request, $year);
		$detail_months = $this->resolveDetailMonths($request);
		// This preference only hides the "Fait / A venir" label row; month columns remain detailed.
		$show_month_details = true;
		[
			$month_display,
			$visible_months,
			$visible_month_years,
		] = $this->resolveMonthDisplay($request, $current_year, (int) $current_month, $year, $selected_month, $selected_year, $detail_months, $show_month_details);
		$month_colspan = $this->monthColspan($visible_months, $detail_months, $show_month_details);
		$display_month_years = $this->displayMonthYears($visible_month_years, $detail_months, $show_month_details);

		// Opérations
		$operation_years = array_values(array_unique(array_map('intval', $visible_month_years)));
		$operations_pos = $this->operationsByYearsAndComptesAndSign($viewCompteIds, $operation_years);
		$operations_neg = $this->operationsByYearsAndComptesAndSign($viewCompteIds, $operation_years, false);
		$table_operations_pos = $this->operations($operations_pos, true, $display_month_years);
		$table_operations_neg = $this->operations($operations_neg, false, $display_month_years);
		$month_anticipation_visible_pos = $this->monthAnticipationVisibility($visible_months, $visible_month_years, $table_operations_pos, $current_year, (int) $current_month);
		$month_anticipation_visible_neg = $this->monthAnticipationVisibility($visible_months, $visible_month_years, $table_operations_neg, $current_year, (int) $current_month);
		$month_anticipation_visible_merged = $this->mergeMonthAnticipationVisibility($visible_months, $month_anticipation_visible_pos, $month_anticipation_visible_neg);
		$month_colspans_pos = $this->monthColspans($visible_months, $detail_months);
		$month_colspans_neg = $this->monthColspans($visible_months, $detail_months);
		$month_colspans_merged = $this->monthColspans($visible_months, $detail_months);
		$operations_pos_datas = $this->operations($this->operationsByYearsAndComptesAndSign($viewCompteIds, [$year]));
		$operations_neg_datas = $this->operations($this->operationsByYearsAndComptesAndSign($viewCompteIds, [$year], false), false);
		$anomalies = $this->or->findOverdueAnticipatedForCompte($compte->getId());

		// Solde
		$solde = $this->currentBalance($viewCompteIds);

		// Solde Fin mois
		$soldeFinMensuel = $this->soldeFinMensuel(
			$solde,
			$operations_pos_datas,
			$operations_neg_datas,
			$year,
			$current_year,
			(int) $current_month
		);

		$preferences = $this->getUser()->getPreferences();
		$moneyDisplayFormat = (string) $request->query->get('money_display_format', $preferences->getMoneyDisplayFormat() ?? 'comma');
		$moneyCurrency = (string) $request->query->get('money_currency', $preferences->getMoneyCurrency() ?? 'EUR');
		$moneyTrimZeros = '1' === (string) $request->query->get('money_trim_zeros', $preferences->isMoneyTrimZeros() ? '1' : '0');
		$moneyShowZeroDecimals = '0' !== (string) $request->query->get('money_show_zero_decimals', $preferences->isMoneyShowZeroDecimals() ? '1' : '0');

		$render = $this->render('compte/table/_tables.html.twig', [
			'compte' => $compte,
			'combined_compte' => $combinedCompte,
			'view_comptes' => $viewComptes,

			'year' => $year,
			'months' => SELF::MONTHS,
			'month_display' => $month_display,
			'visible_months' => $visible_months,
			'visible_month_years' => $visible_month_years,
			'detail_months' => $detail_months,
			'month_colspan' => $month_colspan,
			'display_month_count' => count($display_month_years),
			'selected_month' => $selected_month,
			'selected_year' => $selected_year,

			'user' => $this->getUser(),
			'current_year' => $current_year,
			'current_month' => $current_month,

			'operations_pos' => $table_operations_pos,
			'operations_neg' => $table_operations_neg,
			'month_anticipation_visible_pos' => $month_anticipation_visible_pos,
			'month_anticipation_visible_neg' => $month_anticipation_visible_neg,
			'month_anticipation_visible_merged' => $month_anticipation_visible_merged,
			'month_colspans_pos' => $month_colspans_pos,
			'month_colspans_neg' => $month_colspans_neg,
			'month_colspans_merged' => $month_colspans_merged,

			'gains' => $this->gains($operations_pos, $operations_neg, $display_month_years),
			'money_display_format' => $moneyDisplayFormat,
			'money_currency' => $moneyCurrency,
			'money_trim_zeros' => $moneyTrimZeros,
			'money_show_zero_decimals' => $moneyShowZeroDecimals,
		])->getContent();

		$render_last_actions = $this->render('compte/_last_actions.html.twig', [
			'compte' => $compte,
			'lastActions' => $this->oar->lastActionsForCompte($compte->getId()),
			'canUndoTodayActions' => $this->oar->hasUndoableActionsForCompteToday($compte->getId(), new \DateTimeImmutable('today')),
			'money_display_format' => $moneyDisplayFormat,
			'money_currency' => $moneyCurrency,
			'money_trim_zeros' => $moneyTrimZeros,
			'money_show_zero_decimals' => $moneyShowZeroDecimals,
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
		$this->denyAccessUnlessGranted(CompteVoter::EDIT, $compte);

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
		if (!in_array($resolution, ['realize', 'delete', 'postpone', 'ignore', 'ignore_15_days'], true)){
			return new JsonResponse(['resolved' => false, 'error' => 'Solution de correction invalide.'], Response::HTTP_BAD_REQUEST);
		}

		$futureDate = null;
		if ('postpone' === $resolution){
			$futureDateValue = (string) $request->request->get('future_date');
			$futureDate = \DateTimeImmutable::createFromFormat('!Y-m-d', $futureDateValue);
			if (false === $futureDate || $futureDate->format('Y-m-d') !== $futureDateValue){
				return new JsonResponse(['resolved' => false, 'error' => 'Date future invalide.'], Response::HTTP_BAD_REQUEST);
			}
			if ($futureDate < new \DateTimeImmutable('tomorrow')){
				return new JsonResponse(['resolved' => false, 'error' => 'La nouvelle date doit etre future.'], Response::HTTP_CONFLICT);
			}
		}

		$beforeSnapshot = $this->createOperationSnapshot($operation);
		$actionDate = $this->nextOperationActionDate($compte->getId());
		$actionType = 'delete' === $resolution ? 'del' : 'edit';
		$temporaryIgnoredUntil = 'ignore_15_days' === $resolution
			? (new \DateTimeImmutable('today'))->modify('+15 days')
			: null
		;
		$reusableAction = in_array($resolution, ['realize', 'delete'], true)
			? $this->oar->findReusableAnomalyResolution($operation, $resolution)
			: null
		;
		$operation
			->setAnticipe('realize' === $resolution ? false : $operation->isAnticipe())
			->setActif('delete' !== $resolution)
			->setDate(null !== $futureDate ? \DateTime::createFromImmutable($futureDate) : $operation->getDate())
			->setAnomalyIgnored('ignore' === $resolution)
			->setAnomalyIgnoredUntil(null !== $temporaryIgnoredUntil ? \DateTime::createFromImmutable($temporaryIgnoredUntil) : null)
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

	private function resolveSelectedMonth(Request $request): ?int
	{
		$requestedMonth = $request->query->get('selected_month');
		if (!is_scalar($requestedMonth) || '' === (string) $requestedMonth){
			return null;
		}

		$month = filter_var((string) $requestedMonth, FILTER_VALIDATE_INT, [
			'options' => [
				'min_range' => 1,
				'max_range' => 12,
			],
		]);

		return false === $month ? null : (int) $month;
	}

	private function resolveSelectedYear(Request $request, int $displayYear): int
	{
		$requestedYear = $request->query->get('selected_year');
		if (!is_scalar($requestedYear) || '' === (string) $requestedYear){
			return $displayYear;
		}

		$year = filter_var((string) $requestedYear, FILTER_VALIDATE_INT, [
			'options' => [
				'min_range' => $this->navigation_min_year,
				'max_range' => $this->navigation_max_year,
			],
		]);

		return false === $year ? $displayYear : (int) $year;
	}

	private function resolveMonthDisplay(Request $request, int $currentYear, int $currentMonth, int $displayYear, ?int $selectedMonth = null, ?int $selectedYear = null, ?array $detailMonths = null, bool $showDetails = false): array
	{
		$requestedMode = $request->query->get('months', 'year');
		if (is_string($requestedMode) && array_key_exists($requestedMode, self::MONTH_DISPLAY_ALIASES)){
			$requestedMode = self::MONTH_DISPLAY_ALIASES[$requestedMode];
		}
		$mode = is_string($requestedMode) && array_key_exists($requestedMode, self::MONTH_DISPLAY_OPTIONS)
			? $requestedMode
			: 'year'
		;
		$option = self::MONTH_DISPLAY_OPTIONS[$mode];
		[$monthsBefore, $monthsAfter] = $this->monthDisplayRange($request, $option);
		$referenceMonth = 'selected' === $option['basis'] && null !== $selectedMonth
			? $selectedMonth
			: $currentMonth
		;
		$referenceYear = 'selected' === $option['basis'] && null !== $selectedMonth
			? ($selectedYear ?? $displayYear)
			: $currentYear
		;

		if (null === ($option['radius'] ?? null) && !($option['custom'] ?? false) && !array_key_exists('before', $option)){
			$visibleMonths = array_keys(self::MONTHS);
			return [$mode, $visibleMonths, array_fill_keys($visibleMonths, $displayYear), $monthsBefore, $monthsAfter];
		}

		$referenceDate = (new \DateTimeImmutable())->setDate($referenceYear, $referenceMonth, 1)->setTime(0, 0);

		[$startOffset, $endOffset] = $this->monthDisplayOffsets($referenceDate, $monthsBefore, $monthsAfter, $detailMonths, $showDetails);
		$visibleMonths = [];
		$visibleMonthYears = [];
		$seenMonths = [];
		for ($offset = $startOffset; $offset <= $endOffset; $offset++){
			$monthDate = $referenceDate->modify(sprintf('%+d months', $offset));
			$month = (int) $monthDate->format('n');
			if (isset($seenMonths[$month])){
				[$visibleMonths, $visibleMonthYears] = $this->calendarMonthDisplay($referenceDate, $monthsBefore, $monthsAfter);
				return [$mode, $visibleMonths, $visibleMonthYears, $monthsBefore, $monthsAfter];
			}
			$seenMonths[$month] = true;
			$visibleMonths[] = $month;
			$visibleMonthYears[$month] = (int) $monthDate->format('Y');
		}

		return [$mode, $visibleMonths, $visibleMonthYears, $monthsBefore, $monthsAfter];
	}

	/**
	 * @return array{0: int, 1: int}
	 */
	private function monthDisplayRange(Request $request, array $option): array
	{
		if (($option['custom'] ?? false) === true){
			return $this->normalizeMonthDisplayRange(
				$this->resolveMonthRangeValue($request, 'months_before', (int) ($option['before'] ?? 1)),
				$this->resolveMonthRangeValue($request, 'months_after', (int) ($option['after'] ?? 3))
			);
		}

		if (array_key_exists('before', $option) || array_key_exists('after', $option)){
			return $this->normalizeMonthDisplayRange((int) ($option['before'] ?? 0), (int) ($option['after'] ?? 0));
		}

		$radius = $option['radius'];
		return null === $radius ? [1, 3] : $this->normalizeMonthDisplayRange((int) $radius, (int) $radius);
	}

	/**
	 * @return array{0: int, 1: int}
	 */
	private function normalizeMonthDisplayRange(int $monthsBefore, int $monthsAfter): array
	{
		$monthsBefore = min(max($monthsBefore, 0), 11);
		$monthsAfter = min(max($monthsAfter, 0), 11 - $monthsBefore);

		return [$monthsBefore, $monthsAfter];
	}

	private function resolveMonthRangeValue(Request $request, string $key, int $default): int
	{
		$value = filter_var($request->query->get($key, $default), FILTER_VALIDATE_INT, [
			'options' => [
				'min_range' => 0,
				'max_range' => 11,
			],
		]);

		return false === $value ? $default : (int) $value;
	}

	/**
	 * @return array{0: int, 1: int}
	 */
	private function monthDisplayOffsets(\DateTimeImmutable $referenceDate, int $monthsBefore, int $monthsAfter, ?array $detailMonths, bool $showDetails): array
	{
		if (!$showDetails || null === $detailMonths || 0 === count($detailMonths) || 12 === count($detailMonths)){
			return [-$monthsBefore, $monthsAfter];
		}

		$detailMonthsByNumber = array_flip(array_map('intval', $detailMonths));

		$startOffset = 0;
		$foundBefore = 0;
		for ($offset = -1; $foundBefore < $monthsBefore && $offset >= -11; $offset--){
			$month = (int) $referenceDate->modify(sprintf('%+d months', $offset))->format('n');
			$startOffset = $offset;
			if (isset($detailMonthsByNumber[$month])){
				$foundBefore++;
			}
		}

		$endOffset = 0;
		$foundAfter = 0;
		for ($offset = 1; $foundAfter < $monthsAfter && $offset <= 11; $offset++){
			$month = (int) $referenceDate->modify(sprintf('%+d months', $offset))->format('n');
			$endOffset = $offset;
			if (isset($detailMonthsByNumber[$month])){
				$foundAfter++;
			}
		}

		return [$startOffset, $endOffset];
	}

	/**
	 * @return array{0: int[], 1: array<int, int>}
	 */
	private function calendarMonthDisplay(\DateTimeImmutable $referenceDate, int $monthsBefore, int $monthsAfter): array
	{
		$visibleMonths = [];
		$visibleMonthYears = [];
		for ($offset = -$monthsBefore; $offset <= $monthsAfter; $offset++){
			$monthDate = $referenceDate->modify(sprintf('%+d months', $offset));
			$month = (int) $monthDate->format('n');
			$visibleMonths[] = $month;
			$visibleMonthYears[$month] = (int) $monthDate->format('Y');
		}

		return [$visibleMonths, $visibleMonthYears];
	}

	private function resolveDetailMonths(Request $request): array
	{
		$query = $request->query->all();
		if (!array_key_exists('detail_months', $query) && !array_key_exists('detail_months_set', $query)){
			return array_keys(self::MONTHS);
		}

		$requestedMonths = $query['detail_months'] ?? [];
		if (is_string($requestedMonths)){
			$requestedMonths = array_filter(explode(',', $requestedMonths), static fn ($month): bool => '' !== trim($month));
		} elseif (!is_array($requestedMonths)){
			$requestedMonths = [];
		}

		$detailMonths = array_values(array_unique(array_filter(array_map('intval', $requestedMonths), static function(int $month): bool {
			return $month >= 1 && $month <= 12;
		})));

		return $detailMonths;
	}

	private function monthColspan(array $visibleMonths, array $detailMonths, bool $showDetails): int
	{
		if (!$showDetails){
			return count($visibleMonths);
		}

		return array_reduce($visibleMonths, static function(int $colspan, int $month) use ($detailMonths): int {
			return $colspan + (in_array((int) $month, $detailMonths, true) ? 2 : 1);
		}, 0);
	}

	private function monthAnticipationVisibility(array $visibleMonths, array $visibleMonthYears, array $operationsDatas, int $currentYear, int $currentMonth): array
	{
		$visibility = [];
		foreach ($visibleMonths as $month){
			$month = (int) $month;
			$monthYear = (int) ($visibleMonthYears[$month] ?? $currentYear);
			$monthIsPast = $currentYear > $monthYear || ($currentYear === $monthYear && $month < $currentMonth);
			$hasAnticipation = (int) ($operationsDatas['totaux_mois'][$month]['anticipe_count'] ?? 0) > 0;
			$visibility['m'.$month] = !$monthIsPast || $hasAnticipation;
		}

		return $visibility;
	}

	private function mergeMonthAnticipationVisibility(array $visibleMonths, array ...$visibilityMaps): array
	{
		$visibility = [];
		foreach ($visibleMonths as $month){
			$key = 'm'.((int) $month);
			$visibility[$key] = false;
			foreach ($visibilityMaps as $visibilityMap){
				$visibility[$key] = $visibility[$key] || (bool) ($visibilityMap[$key] ?? false);
			}
		}

		return $visibility;
	}

	private function monthColspans(array $visibleMonths, array $detailMonths): array
	{
		$detailMonths = array_map('intval', $detailMonths);
		$colspans = [];
		foreach ($visibleMonths as $month){
			$month = (int) $month;
			$key = 'm'.$month;
			$colspans[$key] = in_array($month, $detailMonths, true) ? 2 : 1;
		}

		return $colspans;
	}

	private function displayMonthYears(array $visibleMonthYears, array $detailMonths, bool $showDetails): array
	{
		if (!$showDetails){
			return $visibleMonthYears;
		}

		return array_filter(
			$visibleMonthYears,
			static fn (int $year, int $month): bool => in_array($month, $detailMonths, true),
			ARRAY_FILTER_USE_BOTH
		);
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
	 * @return array{years: int[], budget: int[]}
	 */
	private function yearOptions(array $compteIds, int $displayYear): array
	{
		$years = range(
			max($displayYear - 5, $this->navigation_min_year),
			min($displayYear + 5, $this->navigation_max_year)
		);
		$budgetYears = [];
		foreach ($compteIds as $compteId){
			$budgetYears = array_merge($budgetYears, $this->catr->yearsWithBudgetForCompte($compteId));
		}
		$budgetYears = array_values(array_unique(array_intersect($years, $budgetYears)));

		return [
			'years' => $years,
			'budget' => $budgetYears,
		];
	}

	private function operationsByYearsAndCompteAndSign(int $compteId, array $years, bool $sign = true): array
	{
		$operations = [];
		foreach ($years as $operationYear){
			foreach ($this->or->OperationsByYearAndCompteAndSign($compteId, (int) $operationYear, $sign) as $operation){
				$operations[] = $operation;
			}
		}

		return $operations;
	}

	private function operationsByYearsAndComptesAndSign(array $compteIds, array $years, bool $sign = true): array
	{
		$operations = [];
		foreach ($compteIds as $compteId){
			$operations = array_merge(
				$operations,
				$this->operationsByYearsAndCompteAndSign($compteId, $years, $sign)
			);
		}

		return $operations;
	}

	private function currentBalance(array $compteIds): float
	{
		$balance = 0.0;
		foreach ($compteIds as $compteId){
			$balance += (float) $this->or->CompteSoldeActuel($compteId, true);
			$balance -= (float) $this->or->CompteSoldeActuel($compteId, false);
		}

		return round($balance, 2);
	}

	private function resolveCombinedCompte(Request $request, Compte $compte): ?Compte
	{
		$combinedCompteId = filter_var($request->query->get('avec'), FILTER_VALIDATE_INT);
		if (false === $combinedCompteId || null === $combinedCompteId || $combinedCompteId === $compte->getId()){
			return null;
		}

		$combinedCompte = $this->cr->find($combinedCompteId);
		if (null === $combinedCompte){
			throw $this->createNotFoundException('Le second compte est introuvable.');
		}

		$this->denyAccessUnlessGranted(CompteVoter::ACCESS, $combinedCompte);

		return $combinedCompte;
	}

	private function operationMatchesVisibleMonthYears($operation, ?array $visibleMonthYears): bool
	{
		if (null === $visibleMonthYears){
			return true;
		}

		$month = (int) $operation->getDate()->format('n');
		$year = (int) $operation->getDate()->format('Y');

		return isset($visibleMonthYears[$month]) && (int) $visibleMonthYears[$month] === $year;
	}
	/**
	 * Renvoie sous formes d'array les informations liés à des opérations
	 */
	public function operations($operations_ent, $sign = true, ?array $visibleMonthYears = null): Array
	{
		$total_final = 0;
		$operations = [];

		foreach($operations_ent as $operation){

			if (!$this->operationMatchesVisibleMonthYears($operation, $visibleMonthYears)){
				continue;
			}

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
				isset($operations['totaux_mois'][$mois]['anticipe_count'])
					? $operations['totaux_mois'][$mois]['anticipe_count']++
					: $operations['totaux_mois'][$mois]['anticipe_count'] = 1
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
	public function gains($opes_pos, $opes_neg, ?array $visibleMonthYears = null): Array
	{
		// Gains
		$gains = [];

		// Pos
		foreach($opes_pos as $ope){

			if (!$this->operationMatchesVisibleMonthYears($ope, $visibleMonthYears)){
				continue;
			}

			$mois = $ope->getDate()->format('n');

			// Total by month
			isset($gains[$mois]['gain'])
				? $gains[$mois]['gain'] += $ope->getNumber()
				: $gains[$mois]['gain'] = $ope->getNumber()
			;
		}

		// Neg
		foreach($opes_neg as $ope){

			if (!$this->operationMatchesVisibleMonthYears($ope, $visibleMonthYears)){
				continue;
			}

			$mois = $ope->getDate()->format('n');

			// Total by month
			isset($gains[$mois]['gain'])
				? $gains[$mois]['gain'] -= $ope->getNumber()
				: $gains[$mois]['gain'] = -$ope->getNumber()
			;
		}

		// Cumulé
		$cumule = 0;
		if (null !== $visibleMonthYears){
			$orderedGains = [];
			foreach ($visibleMonthYears as $key => $visibleYear){
				if (!isset($gains[$key])){
					continue;
				}
				$cumule += $gains[$key]['gain'];
				$gains[$key]['cumule'] = $cumule;
				$orderedGains[$key] = $gains[$key];
			}

			return $orderedGains;
		}

		ksort($gains);
		foreach($gains as $key => $mois){
			$cumule += $mois['gain'];
			$gains[$key]['cumule'] = $cumule;
		}

		return $gains;
	}

	#[Route("/{id}/settings", name: "_settings", methods: ["POST"])]
	public function settings(Compte $compte, Request $request): JsonResponse
	{
		$this->denyAccessUnlessGranted(CompteVoter::EDIT, $compte);

		if (!$request->isXmlHttpRequest()){
			return $this->json(['saved' => false, 'error' => 'Requete ajax uniquement.'], Response::HTTP_BAD_REQUEST);
		}

		$form = $this->createForm(CompteType::class, $compte, [
			'action' => $this->generateUrl('compte_settings', ['id' => $compte->getId()]),
			'method' => 'POST',
		]);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()){
			if ($compte->getMain()){
				foreach ($this->cr->getComptesByUser($this->getUser()) as $userCompte){
					if ($compte->getId() !== $userCompte->getId()){
						$userCompte->setMain(false);
						$this->cr->add($userCompte);
					}
				}
			}

			$this->cr->add($compte, true);

			$freshForm = $this->createForm(CompteType::class, $compte, [
				'action' => $this->generateUrl('compte_settings', ['id' => $compte->getId()]),
				'method' => 'POST',
			]);

			return $this->json([
				'saved' => true,
				'form' => $this->renderView('compte/modal/settings/_form.html.twig', [
					'account_settings_form' => $freshForm->createView(),
					'compte' => $compte,
				]),
				'account' => [
					'id' => $compte->getId(),
					'libelle' => $compte->getLibelle(),
					'type' => $compte->getType()?->getLibelle(),
					'main' => $compte->getMain(),
					'decouvert' => $compte->getDecouvert(),
				],
			]);
		}

		return $this->json([
			'saved' => false,
			'form' => $this->renderView('compte/modal/settings/_form.html.twig', [
				'account_settings_form' => $form->createView(),
				'compte' => $compte,
			]),
		], Response::HTTP_UNPROCESSABLE_ENTITY);
	}

	#[Route("/{id}/sharing/lookup", name: "_sharing_lookup", methods: ["POST"])]
	public function sharingLookup(Compte $compte, Request $request): JsonResponse
	{
		$this->denyAccessUnlessGranted(CompteVoter::ACCESS, $compte);
		$this->denyAccessUnlessAccountOwner($compte);

		if (!$request->isXmlHttpRequest()){
			return $this->json(['found' => false, 'error' => 'Requete ajax uniquement.'], Response::HTTP_BAD_REQUEST);
		}
		if (!$this->isCsrfTokenValid('account-sharing'.$compte->getId(), (string) $request->request->get('_token'))){
			return $this->json(['found' => false, 'error' => 'Jeton de securite invalide.'], Response::HTTP_FORBIDDEN);
		}

		$code = trim((string) $request->request->get('code'));
		$sharedUser = '' === $code ? null : $this->ur->findOneBy(['code' => $code]);
		if (null === $sharedUser){
			return $this->json(['found' => false, 'error' => 'Aucune personne ne correspond à ce code utilisateur.'], Response::HTTP_NOT_FOUND);
		}
		if ($sharedUser->getId() === $this->getUser()->getId()){
			return $this->json(['found' => false, 'error' => 'Ce code correspond à votre propre profil.'], Response::HTTP_CONFLICT);
		}

		$isAssociated = $compte->getUsers()->contains($sharedUser);
		if (!$isAssociated && $compte->getUsers()->count() >= Compte::MAX_ASSOCIATED_USERS){
			return $this->json([
				'found' => false,
				'error' => 'Ce compte est déjà associé au maximum de 3 personnes.',
			], Response::HTTP_CONFLICT);
		}

		return $this->json([
			'found' => true,
			'associated' => $isAssociated,
			'access' => $isAssociated ? $compte->getUserAccessRole($sharedUser) : 'observer',
			'participant' => $isAssociated && $compte->isUserParticipant($sharedUser),
			'person' => [
				'login' => $sharedUser->getUserIdentifier(),
				'lastName' => $sharedUser->getProfil()?->getNom(),
				'firstName' => $sharedUser->getProfil()?->getPrenom(),
			],
		]);
	}

	#[Route("/{id}/sharing", name: "_sharing_save", methods: ["POST"])]
	public function sharingSave(Compte $compte, Request $request): JsonResponse
	{
		$this->denyAccessUnlessGranted(CompteVoter::ACCESS, $compte);
		$this->denyAccessUnlessAccountOwner($compte);

		if (!$request->isXmlHttpRequest()){
			return $this->json(['saved' => false, 'error' => 'Requete ajax uniquement.'], Response::HTTP_BAD_REQUEST);
		}
		if (!$this->isCsrfTokenValid('account-sharing'.$compte->getId(), (string) $request->request->get('_token'))){
			return $this->json(['saved' => false, 'error' => 'Jeton de securite invalide.'], Response::HTTP_FORBIDDEN);
		}

		$code = trim((string) $request->request->get('code'));
		$sharedUser = '' === $code ? null : $this->ur->findOneBy(['code' => $code]);
		if (null === $sharedUser){
			return $this->json(['saved' => false, 'error' => 'Aucune personne ne correspond à ce code utilisateur.'], Response::HTTP_NOT_FOUND);
		}
		if ($sharedUser->getId() === $this->getUser()->getId()){
			return $this->json(['saved' => false, 'error' => 'Vous êtes déjà associé à ce compte.'], Response::HTTP_CONFLICT);
		}

		$wasAssociated = $compte->getUsers()->contains($sharedUser);
		if (!$wasAssociated && $compte->getUsers()->count() >= Compte::MAX_ASSOCIATED_USERS){
			return $this->json([
				'saved' => false,
				'error' => 'Ce compte est déjà associé au maximum de 3 personnes.',
			], Response::HTTP_CONFLICT);
		}

		$access = (string) $request->request->get('access', 'observer');
		if (!in_array($access, ['none', 'observer', 'editor'], true)){
			return $this->json(['saved' => false, 'error' => 'Droit d’accès invalide.'], Response::HTTP_UNPROCESSABLE_ENTITY);
		}

		$compte
			->addUser($sharedUser)
			->setUserSharing($sharedUser, $access, $request->request->getBoolean('participant'))
		;
		$this->cr->add($compte, true);

		return $this->json([
			'saved' => true,
			'updated' => $wasAssociated,
			'sharing' => $this->renderView('compte/modal/settings/_sharing.html.twig', [
				'compte' => $compte,
			]),
		]);
	}

	#[Route("/{id}/sharing/{user}/remove", name: "_sharing_remove", methods: ["POST"])]
	public function sharingRemove(Compte $compte, #[MapEntity(id: 'user')] User $user, Request $request): JsonResponse
	{
		$this->denyAccessUnlessGranted(CompteVoter::ACCESS, $compte);
		$this->denyAccessUnlessAccountOwner($compte);

		if (!$request->isXmlHttpRequest()){
			return $this->json(['saved' => false, 'error' => 'Requete ajax uniquement.'], Response::HTTP_BAD_REQUEST);
		}
		if (!$this->isCsrfTokenValid('account-sharing'.$compte->getId(), (string) $request->request->get('_token'))){
			return $this->json(['saved' => false, 'error' => 'Jeton de securite invalide.'], Response::HTTP_FORBIDDEN);
		}
		if (!$compte->getUsers()->contains($user)){
			return $this->json(['saved' => false, 'error' => 'Cette personne n’est pas associée au compte.'], Response::HTTP_NOT_FOUND);
		}
		if ($compte->isUserOwner($user)){
			return $this->json(['saved' => false, 'error' => 'Transférez le compte avant de retirer son propriétaire.'], Response::HTTP_CONFLICT);
		}

		foreach ($this->or->findAssignedToUserForCompte($compte, $user) as $operation){
			$operation->setAssignee(null);
			$this->or->add($operation);
		}
		$compte->removeUser($user);
		$this->cr->add($compte, true);

		return $this->json([
			'saved' => true,
			'sharing' => $this->renderView('compte/modal/settings/_sharing.html.twig', [
				'compte' => $compte,
			]),
		]);
	}

	#[Route("/{id}/sharing/{user}/transfer", name: "_sharing_transfer", methods: ["POST"])]
	public function sharingTransfer(Compte $compte, #[MapEntity(id: 'user')] User $user, Request $request): JsonResponse
	{
		$this->denyAccessUnlessGranted(CompteVoter::ACCESS, $compte);
		$this->denyAccessUnlessAccountOwner($compte);

		if (!$request->isXmlHttpRequest()){
			return $this->json(['saved' => false, 'error' => 'Requete ajax uniquement.'], Response::HTTP_BAD_REQUEST);
		}
		if (!$this->isCsrfTokenValid('account-sharing'.$compte->getId(), (string) $request->request->get('_token'))){
			return $this->json(['saved' => false, 'error' => 'Jeton de securite invalide.'], Response::HTTP_FORBIDDEN);
		}
		if (!$compte->getUsers()->contains($user)){
			return $this->json(['saved' => false, 'error' => 'Cette personne n’est pas associée au compte.'], Response::HTTP_NOT_FOUND);
		}
		if ($compte->isUserOwner($user)){
			return $this->json(['saved' => false, 'error' => 'Cette personne est déjà propriétaire du compte.'], Response::HTTP_CONFLICT);
		}

		$previousOwner = $compte->getOwner();
		if (null !== $previousOwner){
			$compte->setUserSharing($previousOwner, 'editor', $compte->isUserParticipant($previousOwner));
		}
		$compte
			->setUserSharing($user, 'editor', $compte->isUserParticipant($user))
			->setOwner($user)
		;
		$this->cr->add($compte, true);

		return $this->json([
			'saved' => true,
			'ownerTransferred' => true,
			'sharing' => $this->renderView('compte/modal/settings/_sharing.html.twig', [
				'compte' => $compte,
			]),
		]);
	}

	/**
	 * @Route("/{id}", name="_delete", methods={"POST"})
	 */
	#[Route("/{id}", name: "_delete", methods: ["POST"])]
	public function delete(Compte $compte, Request $request): Response
	{
		$this->denyAccessUnlessGranted(CompteVoter::EDIT, $compte);

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
		$compte = $sc->getCategory()->getCompte();
		$this->denyAccessUnlessGranted(CompteVoter::ACCESS, $compte);

		// Control request
		if (!$request->isXmlHttpRequest()){ throw new HttpException('500', 'Requête ajax uniquement'); }

		$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
		$datas['days_in_month'] = $daysInMonth;
		$datas['subcategory_libelle'] = $sc->getLibelle();
		$datas['category_libelle'] = $sc->getCategory()->getLibelle();
		$datas['operations'] = $this->or->gestion($sc, $year, $month, $sign, $daysInMonth);
		$canEdit = $this->isGranted(CompteVoter::EDIT, $compte);
		$datas['canEdit'] = $canEdit;
		$accountUsers = $compte->getUsers()->toArray();
		$participantUsers = array_values(array_filter(
			$accountUsers,
			fn (User $user): bool => $compte->isUserParticipant($user)
		));
		$datas['members'] = array_map(
			fn (User $user): array => ['id' => $user->getId(), 'name' => $user->getUserName()],
			$participantUsers
		);
		$canAssign = $canEdit && count($accountUsers) > 1 && count($datas['members']) > 0;
		$datas['addRender'] = $this->operation_add($month, $year, $daysInMonth, $sign, $canAssign, $canEdit);
		$datas['tBodyRender'] = $this->operation_tbody($datas['operations'], $month, $year, $daysInMonth, $sign, $canAssign, $canEdit);

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
		$this->denyAccessUnlessGranted(CompteVoter::EDIT, $sc->getCategory()->getCompte());

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

			$operationYear = (int) ($ope['year'] ?? 0);
			$operationMonth = (int) ($ope['month'] ?? 0);
			$operationDay = (int) ($ope['day'] ?? 0);
			if (
				$operationYear < 1900
				|| $operationYear > 2100
				|| !checkdate($operationMonth, $operationDay, $operationYear)
			){
				return new JsonResponse(
					['save' => false, 'error' => 'Date d’opération invalide.'],
					Response::HTTP_BAD_REQUEST
				);
			}
			$date = new \Datetime(sprintf('%04d-%02d-%02d', $operationYear, $operationMonth, $operationDay));
			$number = $hasNumber ? (float) $numberValue : (float) $anticipatedValue;
			$anticipe = !$hasNumber;
			$comment = $ope['comment'] ?? null;
			$assignee = null;
			if (!empty($ope['assignee'])){
				$assignee = $this->ur->find((int) $ope['assignee']);
				if (
					null === $assignee
					|| !$sc->getCategory()->getCompte()->getUsers()->contains($assignee)
					|| !$sc->getCategory()->getCompte()->isUserParticipant($assignee)
				){
					return new JsonResponse(['save' => false, 'error' => 'Personne attribuée invalide.'], Response::HTTP_CONFLICT);
				}
			}

			// Edit
			if (!empty($ope['id'])){
				$beforeSnapshot = $this->createOperationSnapshot($ope_ent);
				$changed =
					$number !== (float) $ope_ent->getNumber() ||
					$anticipe !== $ope_ent->isAnticipe() ||
					$date->format('Y-m-d') !== $ope_ent->getDate()->format('Y-m-d') ||
					$comment !== $ope_ent->getComment() ||
					$assignee?->getId() !== $ope_ent->getAssignee()?->getId()
				;
				if (!$changed){
					continue;
				}
				$actionType = 'edit';

			// Add
			} else {
				$beforeSnapshot = null;
				$actionType = 'create';
				$ope_ent = new Operation();
			}

			$ope_ent
				->setSubcategory($sc)
				->setAssignee($assignee)
				->setNumber($number)
				->setDate($date)
				->setComment($comment)
				->setAnticipe($anticipe)
				->setActif(true)
				->setAnomalyIgnored(false)
				->setAnomalyIgnoredUntil(null)
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
		$this->denyAccessUnlessGranted(CompteVoter::EDIT, $compte);

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
		$this->denyAccessUnlessGranted(CompteVoter::EDIT, $compte);

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
		$this->denyAccessUnlessGranted(CompteVoter::EDIT, $compte);

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

		return new JsonResponse($this->applyOperationActionUndo($compte, $operationAction));
	}

	#[Route('/{id}/operation/actions/today/undo', name: '_operation_actions_today_undo', methods: ['POST'])]
	public function undoTodayOperationActions(Compte $compte, Request $request): JsonResponse
	{
		$this->denyAccessUnlessGranted(CompteVoter::EDIT, $compte);

		if (!$request->isXmlHttpRequest()){
			return new JsonResponse(['undo' => false, 'error' => 'Requete ajax uniquement.'], Response::HTTP_BAD_REQUEST);
		}
		if (!$this->isCsrfTokenValid('undo-today-operation-actions'.$compte->getId(), (string) $request->request->get('_token'))){
			return new JsonResponse(['undo' => false, 'error' => 'Jeton de securite invalide.'], Response::HTTP_FORBIDDEN);
		}

		$undone = 0;
		foreach ($this->oar->undoableActionsForCompteToday($compte->getId(), new \DateTimeImmutable('today')) as $operationAction){
			if (!$operationAction->isUndoable() || $operationAction->isCancelled()){
				continue;
			}

			$result = $this->applyOperationActionUndo($compte, $operationAction);
			if (true === ($result['undo'] ?? false)){
				++$undone;
			}
		}

		return new JsonResponse(['undo' => true, 'undone' => $undone]);
	}

	private function applyOperationActionUndo(Compte $compte, OperationAction $operationAction): array
	{
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

			return ['undo' => true, 'undoReverted' => true];
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

		return ['undo' => true, 'undoReverted' => false];
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

	private function undoCategoryMoveAction(Compte $compte, OperationAction $action): array
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

			return ['undo' => true, 'undoReverted' => true];
		}

		$this->restoreCategoryOrderSnapshot($compte, $action->getBeforeSnapshot());
		$action
			->setCancelled(true)
			->setUndoSnapshot($currentSnapshot)
		;
		$this->oar->add($action, true);

		return ['undo' => true, 'undoReverted' => false];
	}

	private function undoSubCategoryMoveAction(Compte $compte, OperationAction $action): array
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

			return ['undo' => true, 'undoReverted' => true];
		}

		$this->restoreSubCategoryOrderSnapshot($compte, $action->getBeforeSnapshot());
		$action
			->setCancelled(true)
			->setUndoSnapshot($currentSnapshot)
		;
		$this->oar->add($action, true);

		return ['undo' => true, 'undoReverted' => false];
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
			'subcategoryId' => $operation->getSubcategory()->getId(),
			'assigneeId' => $operation->getAssignee()?->getId(),
			'number' => $operation->getNumber(),
			'anticipe' => $operation->isAnticipe(),
			'date' => $operation->getDate()->format(DATE_ATOM),
			'comment' => $operation->getComment(),
			'actif' => $operation->isActif(),
			'anomalyIgnored' => $operation->isAnomalyIgnored(),
			'anomalyIgnoredUntil' => $operation->getAnomalyIgnoredUntil()?->format(DATE_ATOM),
			'lastAction' => $operation->getLastAction(),
			'dateLastAction' => $operation->getDateLastAction()->format(DATE_ATOM),
		];
	}

	private function restoreOperationSnapshot(Operation $operation, array $snapshot): void
	{
		if (isset($snapshot['subcategoryId'])){
			$subCategory = $this->scr->find((int) $snapshot['subcategoryId']);
			if (null !== $subCategory){
				$operation->setSubcategory($subCategory);
			}
		}
		$operation->setAssignee(
			isset($snapshot['assigneeId']) ? $this->ur->find((int) $snapshot['assigneeId']) : null
		);

		$operation
			->setNumber((float) $snapshot['number'])
			->setAnticipe((bool) $snapshot['anticipe'])
			->setDate(new \DateTime($snapshot['date']))
			->setComment($snapshot['comment'])
			->setActif((bool) $snapshot['actif'])
			->setAnomalyIgnored((bool) ($snapshot['anomalyIgnored'] ?? false))
			->setAnomalyIgnoredUntil(isset($snapshot['anomalyIgnoredUntil']) ? new \DateTime($snapshot['anomalyIgnoredUntil']) : null)
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

	private function denyAccessUnlessAccountOwner(Compte $compte): void
	{
		if (!$this->getUser() instanceof User || !$compte->isUserOwner($this->getUser())){
			throw $this->createAccessDeniedException('Seul le propriétaire du compte peut gérer les personnes associées.');
		}
	}

	private function resolveSharedUserFromForm($form): ?User
	{
		if (!$form->isSubmitted()){
			return null;
		}

		$codeField = $form->get('users_code');
		$code = trim((string) $codeField->getData());
		if ('' === $code){
			return null;
		}

		$sharedUser = $this->ur->findOneBy(['code' => $code]);
		if (null === $sharedUser){
			$codeField->addError(new FormError('Aucune personne ne correspond à ce code utilisateur.'));
			return null;
		}
		if ($sharedUser->getId() === $this->getUser()->getId()){
			$codeField->addError(new FormError('Vous êtes déjà associé à ce compte.'));
			return null;
		}

		return $sharedUser;
	}

	/**
	 * Renvoie le render d'une nouvelle opération
	 */
	public function operation_add($month, $year, $daysInMonth, $sign, bool $canAssign = false, bool $canEdit = true)
	{
		return $this->render('compte/modal/operations/operation/_add.html.twig', [
			'sign' => $sign,
			'year' => $year,
			'month' => (int) $month,
			'daysInMonth' => $daysInMonth,
			'day' => date('n') == $month ? date('d') : 1,
			'canAssign' => $canAssign,
			'canEdit' => $canEdit,
		])->getContent();
	}

	/**
	 * Renvoie le render du tbody
	 */
	public function operation_tbody($operations, $month, $year, $daysInMonth, $sign, bool $canAssign = false, bool $canEdit = true)
	{
		return $this->render('compte/modal/operations/operation/_tbody.html.twig', [
			'operations' => $operations,
			'sign' => $sign,
			'year' => $year,
			'month' => (int) $month,
			'daysInMonth' => $daysInMonth,
			'day' => date('n') == $month ? date('d') : 1,
			'canAssign' => $canAssign,
			'canEdit' => $canEdit,
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
		$this->denyAccessUnlessGranted(CompteVoter::EDIT, $compte);

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
		$this->denyAccessUnlessGranted(CompteVoter::EDIT, $compte);
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
