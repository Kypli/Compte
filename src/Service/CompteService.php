<?php 

namespace App\Service;

use App\Entity\User;
use App\Entity\Compte;
use App\Entity\Category;
use App\Entity\SubCategory;

use App\Repository\CompteRepository;
use App\Repository\CompteTypeRepository;
use App\Repository\CategoryRepository;
use App\Repository\SubCategoryRepository;

class CompteService
{
	// Repository
	private $cr;
	private $ctr;
	private $catr;
	private $scatr;

	public function __construct(
		CompteRepository $cr,
		CompteTypeRepository $ctr,
		CategoryRepository $catr,
		SubCategoryRepository $scatr
	){
		$this->cr = $cr;
		$this->ctr = $ctr;
		$this->catr = $catr;
		$this->scatr = $scatr;
	}

	public function addModele(User $user): void
	{
		$compte = new Compte();
		$compte
			->setLibelle('Compte principal')
			->setMain(true)
			->setType($this->ctr->findOneByLibelle('Compte courant'))
			->addUser($user)
		;

		$categoryPlus = new Category();
		$categoryPlus
			->setLibelle('travail')
			->setPosition(1)
			->setYear(date('Y'))
			->setCompte($compte)
		;

		$subCategoryPlus1 = new SubCategory();
		$subCategoryPlus1
			->setLibelle('salaire')
			->setPosition(1)
			->setCategory($categoryPlus)
		;

		$subCategoryPlus2 = new SubCategory();
		$subCategoryPlus2
			->setLibelle('prime')
			->setPosition(2)
			->setCategory($categoryPlus)
		;

		$categoryMinus = new Category();
		$categoryMinus
			->setSign(false)
			->setLibelle('course')
			->setPosition(1)
			->setYear(date('Y'))
			->setCompte($compte)
		;

		$subCategoryMinus1 = new SubCategory();
		$subCategoryMinus1
			->setLibelle('consommables')
			->setPosition(1)
			->setCategory($categoryMinus)
		;

		$subCategoryMinus2 = new SubCategory();
		$subCategoryMinus2
			->setLibelle('vêtements')
			->setPosition(2)
			->setCategory($categoryMinus)
		;

		$this->cr->add($compte, true);
		$this->catr->add($categoryPlus, true);
		$this->catr->add($categoryMinus, true);
		$this->scatr->add($subCategoryPlus1, true);
		$this->scatr->add($subCategoryPlus2, true);
		$this->scatr->add($subCategoryMinus1, true);
		$this->scatr->add($subCategoryMinus2, true);
	}
}