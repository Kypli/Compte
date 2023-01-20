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

		if ($form->isSubmitted() && $form->isValid()) {
			$this->cr->add($compte, true);

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
	public function edit(Request $request, Compte $compte): Response
	{
		$form = $this->createForm(CompteType::class, $compte);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid()) {
			$this->cr->add($compte, true);

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
	public function delete(Request $request, Compte $compte): Response
	{
		if ($this->isCsrfTokenValid('delete'.$compte->getId(), $request->request->get('_token'))) {
			$this->cr->remove($compte, true);
		}

		return $this->redirectToRoute('compte_index', [], Response::HTTP_SEE_OTHER);
	}

	// ****************
	// MODAL GESTION OPERATIONS
	// ****************

	/**
	 * @Route("/gestion/{sc}/{year}/{month}/{sign}", name="_gestion")
	 * Ajax only
	 */
	public function gestion(SubCategory $sc, $year, $month, $sign, Request $request): Response
	{
		// Control request
		if (!$request->isXmlHttpRequest()){ throw new HttpException('500', 'Requête ajax uniquement'); }

		$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

		$datas['days_in_month'] = $daysInMonth;
		$datas['subcategory_libelle'] = $sc->getLibelle();
		$datas['category_libelle'] = $sc->getCategory()->getLibelle();
		$datas['operations'] = $this->or->gestion($sc, $year, $month, $sign, $daysInMonth);

		return new JsonResponse($datas);
	}

	/**
	 * @Route("/gestion/save/{sc}/{year}/{month}/{sign}", name="_gestion_save", methods={"GET", "POST"}, options={"expose"=true})
	 * Ajax only
	 */
	public function gestion_save(SubCategory $sc, $year, $month, $sign, Request $request): Response
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
				$this->or->add($ope_ent, true);
			}
		}

		// Delete
		foreach($operations as $operation){
			$del = $this->or->find($operation['id']);
			$this->or->remove($del, true);
		}

		$operations = $this->or->gestion($sc, $year, $month, $sign, $daysInMonth);

		return new JsonResponse(['save' => true, 'operations' => $operations]);
	}

	/**
	 * @Route("/gestion/add/{month}/{year}/{daysInMonth}/{sign}", name="_gestion_add")
	 * Ajax only
	 */
	public function gestionAdd($month, $year, $daysInMonth, $sign, Request $request): Response
	{
		// Control request
		if (!$request->isXmlHttpRequest()){ throw new HttpException('500', 'Requête ajax uniquement'); }

		$render = $this->render('compte/modal/gestion/_add.html.twig', [
			'sign' => $sign,
			'year' => $year,
			'month' => (int) $month,
			'daysInMonth' => $daysInMonth,
		])->getContent();

		return new JsonResponse([
			'render' => $render,
		]);
	}

	// ****************
	// MODAL CATEGORY
	// ****************

	/**
	 * @Route("/categorie/{id}/{sign}", name="_categorie")
	 * Récupère datas d'une catégorie
	 * Ajax only
	 */
	public function categorie(Compte $compte, $sign, Request $request): Response
	{
		// Control request
		if (!$request->isXmlHttpRequest()){ throw new HttpException('500', 'Requête ajax uniquement'); }

		$render = $this->render('compte/modal/category/_tbody_form.html.twig', [
			'categories' => $this->catr->mycategories($compte->getId(), $sign)
		])->getContent();

		return new JsonResponse([
			'render' => $render,
		]);
	}

	/**
	 * @Route("/scategory/add", name="_scategory_add")
	 * Récupère tr_subcategories_add
	 * Ajax only
	 */
	public function scategory(Request $request): Response
	{
		// Control request
		if (!$request->isXmlHttpRequest()){ throw new HttpException('500', 'Requête ajax uniquement'); }

		$render = $this->render('compte/modal/category/_sc_add.html.twig')->getContent();

		return new JsonResponse([
			'render' => $render,
		]);
	}

	/**
	 * @Route("/categorie/edit/{id}/{year}", name="_categorie_edit")
	 * Edit tr_categorie / Edit tr_subcategories / Add tr_subcategories_add
	 * Ajax only
	 */
	public function categorieEdit(Compte $compte, $year, Request $request): Response
	{
		// Control request
		if (!$request->isXmlHttpRequest()){ throw new HttpException('500', 'Requête ajax uniquement'); }

		$datas = $request->request->get('datas');

		// Categorie
		$datas_cat = $datas[0];
		if ($datas_cat['type'] == 'cat'){

			if ($datas_cat['id'] != ''){
				$cat = $this->catr->find($datas_cat['id']);
				$scs = $this->scr->idsFromCat($cat->getId());
			} else {
				$cat = new Category();
				$cat
					->setCompte($compte)
					->setPosition($this->catr->lastPos($compte->getId())['position'] + 1)
				;
				$scs = [];
			}

			$cat->setLibelle($datas_cat['libelle']);
			// TODO position

			$this->catr->add($cat, true);
		}
		unset($datas[0]);

		// Sub-catégories
		foreach ($datas as $key => $datas_sc){
			if ($datas_sc['id'] != ''){
				$sc = $this->scr->find($datas_sc['id']);
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
			$this->scr->remove($this->scr->find($key));
		}

		return new JsonResponse([
			'save' => true,
		]);
	}
}
