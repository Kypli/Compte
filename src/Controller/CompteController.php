<?php

namespace App\Controller;

use App\Entity\Compte;
use App\Entity\Category;
use App\Entity\Operation;
use App\Entity\SubCategory;

use App\Form\CompteType;

use App\Repository\CompteRepository;
use App\Repository\CategoryRepository;
use App\Repository\OperationRepository;
use App\Repository\SubCategoryRepository;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

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

	private $cr;
	private $or;
	private $catr;
	private $scr;

	public function __construct(
		CompteRepository $cr,
		OperationRepository $or,
		CategoryRepository $catr,
		SubCategoryRepository $scr
	){
		$this->navigation_max_year = ((int)date('Y') + 40);
		$this->navigation_min_year = ((int)date('Y') - 40);
		$this->cr = $cr;
		$this->or = $or;
		$this->catr = $catr;
		$this->scr = $scr;
	}

	// ****************
	// COMPTE
	// ****************

	/**
	 * @Route("/", name="")
	 */
	public function index(): Response
	{
		return $this->render('compte/index.html.twig', [
			'comptes' => $this->cr->findAll(),
		]);
	}

	/**
	 * @Route("/new", name="_new", methods={"GET", "POST"})
	 */
	public function new(Request $request): Response
	{
		$compte = new Compte();
		$form = $this->createForm(CompteType::class, $compte);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()){

			$compte->addUser($this->getUser());
			$this->cr->add($compte, true);

			return $this->redirectToRoute('tableau_bord', [], Response::HTTP_SEE_OTHER);
		}

		return $this->renderForm('compte/new.html.twig', [
			'compte' => $compte,
			'form' => $form,
		]);
	}

	/**
	 * @Route("/{id}", name="_show", methods={"GET", "POST"})
	 * Montre un compte
	 */
	public function show(Compte $compte, Request $request): Response
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
		$current_solde = round(
			($this->or->CompteSoldeActuel($compte->getId(), true) - $this->or->CompteSoldeActuel($compte->getId(), false)),
			2
		);

		// Opérations
		$operations_pos = $this->or->OperationsByYearAndCompteAndSign($compte->getId(), $year);
		$operations_neg = $this->or->OperationsByYearAndCompteAndSign($compte->getId(), $year, false);

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

			'current_solde' => $current_solde, // Solde courant du compte
			'soldes_mensuels' => $this->soldesByMonth($operations_pos, $operations_neg),
		]);
	}

	/**
	 * @Route("/{id}/tables", name="_tables", methods={"GET", "POST"})
	 * Renvoie le render des tables
	 * Ajax only
	 */
	public function tables(Compte $compte, Request $request): Response
	{
		// Control request
		if (!$request->isXmlHttpRequest()){ throw new HttpException('500', 'Requête ajax uniquement'); }

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
		$solde = round(
			($this->or->CompteSoldeActuel($compte->getId(), true) - $this->or->CompteSoldeActuel($compte->getId(), false)),
			2
		);

		// Opérations
		$operations_pos = $this->or->OperationsByYearAndCompteAndSign($compte->getId(), $year);
		$operations_neg = $this->or->OperationsByYearAndCompteAndSign($compte->getId(), $year, false);

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

		$render = $this->render('compte/table/_tables.html.twig', [
			'compte' => $compte,

			'year' => $year,
			'months' => $months,

			'user' => $this->getUser(),
			'current_year' => $current_year,
			'current_month' => $current_month,

			'operations_pos' => $this->operations($operations_pos),
			'operations_neg' => $this->operations($operations_neg, false),

			'soldes' => $this->soldesByMonth($operations_pos, $operations_neg),
		])->getContent();

		return new JsonResponse([
			'render' => $render,
			'solde' => $solde,
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
	public function edit(Compte $compte, Request $request): Response
	{
		$form = $this->createForm(CompteType::class, $compte);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$this->cr->add($compte, true);

			return $this->redirectToRoute('compte', [], Response::HTTP_SEE_OTHER);
		}

		return $this->renderForm('compte/edit.html.twig', [
			'compte' => $compte,
			'form' => $form,
		]);
	}

	/**
	 * @Route("/{id}", name="_delete", methods={"GET", "POST"})
	 */
	public function delete(Compte $compte, Request $request): Response
	{
		if ($this->isCsrfTokenValid('delete'.$compte->getId(), $request->request->get('_token'))) {
			$this->cr->remove($compte, true);
		}

		return $this->redirectToRoute('compte', [], Response::HTTP_SEE_OTHER);
	}

	// ****************
	// MODAL OPERATIONS
	// ****************

	/**
	 * @Route("/operation/{sc}/{year}/{month}/{sign}", name="_operation", methods={"GET", "POST"})
	 * Renvoie les opérations selon la sc, l'année, le mois et le signe
	 * Ajax only
	 */
	public function operationDatas(SubCategory $sc, $year, $month, $sign, Request $request): Response
	{
		// Control request
		// if (!$request->isXmlHttpRequest()){ throw new HttpException('500', 'Requête ajax uniquement'); }

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
	 * @Route("/operation/save/{sc}/{year}/{month}/{sign}", name="_operation_save")
	 * Sauvegarde les opérations d'une sc
	 * Ajax only
	 */
	public function operation_save(SubCategory $sc, $year, $month, $sign, Request $request): Response
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
		$operations = $this->or->gestion($sc, $year, $month, $sign, $daysInMonth);

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
				$ope_ent = $this->or->find($id);

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
				// Nombre valide
				(
					(
						$ope['number'] != null &&
						$ope['number'] != 0 &&
						$ope['number'] != '0' &&
						$ope['number'] != '' &&
						$ope['number'] != 'NaN'
					) ||
					(
						$ope['number_anticipe'] != null &&
						$ope['number_anticipe'] != 0 &&
						$ope['number_anticipe'] != '0' &&
						$ope['number_anticipe'] != '' &&
						$ope['number_anticipe'] != 'NaN'
					)
				) &&
				(
					// Add
					$id == null ||

					// Edit
					$ope_ent->hasSubCategory($ope_ent, $sc)
				)
			){
				$date = new \Datetime($ope['year'].'/'.$ope['month'].'/'.$ope['day']);
				$number = 
					$ope['number'] == null ||
					$ope['number'] == 0 ||
					$ope['number'] == '0' ||
					$ope['number'] == '' ||
					$ope['number'] == 'Nan'
						? (float) $ope['number_anticipe']
						: (float) $ope['number']
				;
				$anticipe =
					$ope['number'] == null ||
					$ope['number'] == 0 ||
					$ope['number'] == '0' ||
					$ope['number'] == '' ||
					$ope['number'] == 'Nan'
						? true
						: false
				;
				$ope_ent
					->setNumber($number)
					->setDate($date)
					->setComment($ope['comment'])
					->setAnticipe($anticipe)
				;
				$this->or->add($ope_ent, true);
			}
		}

		// Delete
		foreach($operations as $operation){
			$del = $this->or->find($operation['id']);
			$this->or->remove($del, true);
		}

		return new JsonResponse(['save' => true]);
	}

	/**
	 * Renvoie le render d'une nouvelle opération
	 */
	public function operation_add($month, $year, $daysInMonth, $sign)
	{
		return $this->render('compte/modal/operation/table/_add.html.twig', [
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
		return $this->render('compte/modal/operation/table/_tbody.html.twig', [
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
	 * @Route("/cat/{id}/{cat}/{sign}", name="_category", methods={"GET", "POST"})
	 * Récupère datas d'une catégorie
	 * Ajax only
	 */
	public function category(Compte $compte, Category $cat, $sign, Request $request): Response
	{
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
	 * @Route("/cat/add/{id}/{sign}", name="_category_add", methods={"GET", "POST"})
	 * Renvoie le render d'une nouvelle catégorie
	 * URL: Caty au lieu de cat a cause d'un bug ParamConverter
	 * Ajax only
	 */
	public function category_add(Compte $compte, $sign, Request $request): Response
	{
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
	 * @Route("/{compte}/cat/save/{year}", name="_category_save", methods={"GET", "POST"})
	 * Edit tr_category / Edit tr_subcategories / Add tr_subcategories_add
	 * URL: Caty au lieu de cat a cause d'un bug ParamConverter
	 * Ajax only
	 */
	public function category_save(Compte $compte, $year, Request $request): Response
	{
		// Control request
		if (!$request->isXmlHttpRequest()){ throw new HttpException('500', 'Requête ajax uniquement'); }

		$datas = $request->request->get('datas');

		// Categorie
		$scs = [];
		$datas_cat = $datas[0];
		if ($datas_cat['type'] == 'cat'){

			// Edit
			if ($datas_cat['id'] != 'add'){
				$cat = $this->catr->find($datas_cat['id']);
				$scs = $this->scr->idsFromCat($cat->getId());

			// Add
			} else {
				$cat = new Category();
				$cat
					->setCompte($compte)
					->setSign($datas_cat['sign'])
					->setYear($year)
				;
			}

			// Commun Edit
			$cat
				->setLibelle($datas_cat['libelle'])
				->setPosition($datas_cat['position'])
			;

			// Save
			$this->catr->add($cat, true);

			// Corrige les autres positions
			$this->orderCatPosition($compte->getId(), $cat->getId(), $datas_cat['sign'], $year,  $datas_cat['position']);

		}
		unset($datas[0]);

		// Sub-catégories
		foreach ($datas as $key => $datas_sc){

			// Edit
			if ($datas_sc['id'] != ''){
				$sc = $this->scr->find($datas_sc['id']);
				unset($scs[$datas_sc['id']]);

			// Add
			} else {
				$sc = new SubCategory();
				$sc->setCategory($cat);
			}

			$sc
				->setPosition($key)
				->setLibelle($datas_sc['libelle'])
			;

			$this->scr->add($sc, true);
		}

		// Delete SubCategories
		foreach($scs as $key => $osef){
			$this->scr->remove($this->scr->find($key), true);
		}

		return new JsonResponse([
			'save' => true,
		]);
	}

	/**
	 * @Route("/cat/delete/{id}/{cat}", name="_category_delete", methods={"GET", "POST"})
	 * Delete category
	 * URL: Caty au lieu de cat a cause d'un bug ParamConverter
	 * Ajax only
	 */
	public function category_delete(Compte $compte, Category $cat, Request $request): Response
	{
		// Control request
		if (!$request->isXmlHttpRequest()){ throw new HttpException('500', 'Requête ajax uniquement'); }

		// Control Cat owner
		$user = $this->getUser();
		if (!$this->isGranted('ROLE_ADMIN') && !$user->hasCategory($user, $cat)){
			return new JsonResponse(['save' => "Pas propriétaire de la categorie."]);
		}

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
	 * @Route("/sc/{id}", name="_subcategory", methods={"GET", "POST"})
	 * Récupère render de tr_subcategorie_back
	 * Ajax only
	 */
	public function subcategory(SubCategory $sc, Request $request): Response
	{
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
	 * @Route("/sc/add/{addMod}", name="_subcategory_add", methods={"GET", "POST"})
	 * Récupère render de tr_subcategories_add
	 * Ajax only
	 */
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
