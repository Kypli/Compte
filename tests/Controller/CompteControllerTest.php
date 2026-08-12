<?php

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Compte;
use App\Entity\CompteType;
use App\Entity\Operation;
use App\Entity\SubCategory;
use App\Entity\User;
use App\Entity\UserPreference;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;

class CompteControllerTest extends WebTestCase
{
	private KernelBrowser $client;
	private User $owner;
	private User $intruder;
	private Compte $compte;
	private Category $positiveCategory;
	private Category $negativeCategory;
	private SubCategory $positiveSubCategory;
	private array $createdIds = [];

	protected function setUp(): void
	{
		$this->client = static::createClient();
		$entityManager = static::getContainer()->get(EntityManagerInterface::class);
		$suffix = bin2hex(random_bytes(6));

		$this->owner = $this->createUser('owner_'.$suffix);
		$this->intruder = $this->createUser('intruder_'.$suffix);
		$type = (new CompteType())
			->setLibelle('Compte courant test '.$suffix)
			->setLibelleShort('T'.$suffix)
			->setDecouvert(true)
			->setTauxInteret(0)
			->setPlancher(0)
			->setPlafond(null)
		;
		$this->compte = (new Compte())
			->setLibelle('Compte test '.$suffix)
			->setMain(true)
			->setDecouvert(0)
			->setType($type)
			->addUser($this->owner)
		;

		$currentYear = (int) date('Y');
		$this->positiveCategory = $this->createCategory($this->compte, 'Revenus', true, $currentYear);
		$this->negativeCategory = $this->createCategory($this->compte, 'Depenses', false, $currentYear);
		$this->positiveSubCategory = $this->createSubCategory($this->positiveCategory, 'Salaire');
		$negativeSubCategory = $this->createSubCategory($this->negativeCategory, 'Factures');
		$realIncome = $this->createOperation($this->positiveSubCategory, 100, false);
		$anticipatedExpense = $this->createOperation($negativeSubCategory, 150, true);

		foreach ([
			$this->owner,
			$this->intruder,
			$type,
			$this->compte,
			$this->positiveCategory,
			$this->negativeCategory,
			$this->positiveSubCategory,
			$negativeSubCategory,
			$realIncome,
			$anticipatedExpense,
		] as $entity){
			$entityManager->persist($entity);
		}
		$entityManager->flush();

		$this->createdIds = [
			Operation::class => [$realIncome->getId(), $anticipatedExpense->getId()],
			SubCategory::class => [$this->positiveSubCategory->getId(), $negativeSubCategory->getId()],
			Category::class => [$this->positiveCategory->getId(), $this->negativeCategory->getId()],
			Compte::class => [$this->compte->getId()],
			User::class => [$this->owner->getId(), $this->intruder->getId()],
			CompteType::class => [$type->getId()],
		];
	}

	protected function tearDown(): void
	{
		$entityManager = static::getContainer()->get(EntityManagerInterface::class);
		$entityManager->clear();

		foreach ($this->createdIds as $class => $ids){
			foreach ($ids as $id){
				$entity = $entityManager->find($class, $id);
				if (null !== $entity){
					$entityManager->remove($entity);
				}
			}
			$entityManager->flush();
		}

		parent::tearDown();
	}

	public function testAnonymousUserIsRedirectedToLogin(): void
	{
		$this->client->request('GET', '/compte/'.$this->compte->getId());

		self::assertResponseRedirects('http://localhost/login');
	}

	public function testOwnerCanDisplayAccountAndUseReadOnlyAjaxActions(): void
	{
		$this->client->loginUser($this->owner);

		$this->client->request('GET', '/compte/'.$this->compte->getId());
		self::assertResponseIsSuccessful();
		self::assertSelectorTextContains('h1', $this->compte->getLibelle());
		self::assertSelectorTextSame('#soldeActuelNb', '100,00');
		self::assertSelectorExists('#soldeActuel.total_month_full_pos');
		self::assertSelectorTextSame('#soldeFinMoisNb', '-50,00');
		self::assertSelectorExists('#soldeFinMois.total_month_full_neg');
		self::assertSelectorExists('#legendButton[data-target="#modalLegend"]');
		self::assertSelectorTextContains('#modalLegendTitle', 'Légende des couleurs');
		self::assertSelectorTextContains('#legendBackgroundTitle', 'Couleurs de fond');
		self::assertSelectorTextContains('#legendTextTitle', 'Couleurs du texte');
		self::assertSelectorExists('#modalLegend .legend-close-button[aria-label="Fermer"] .fa-xmark');
		self::assertSelectorExists('#modalLegend .legend-background-sample.bck_pos');
		self::assertSelectorExists('#modalLegend .legend-background-sample.bck_neg');
		self::assertSelectorExists('#modalLegend .legend-text-sample.total_month_full_dec');
		self::assertSelectorExists('#modalLegend .legend-text-sample.anticipe');
		self::assertSelectorExists('#modalLegend .legend-alert-sample .fa-exclamation');
		self::assertSelectorTextContains('#modalLegend', 'Découvert autorisé dépassé');

		$this->client->xmlHttpRequest('POST', '/compte/'.$this->compte->getId().'/tables?year='.date('Y'));
		self::assertResponseIsSuccessful();
		$response = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
		self::assertArrayHasKey('render', $response);
		self::assertSame(100.0, (float) $response['solde']);
		self::assertSame(-50.0, (float) $response['soldeFinMensuel']);

		$this->client->xmlHttpRequest('POST', sprintf(
			'/compte/cat/%d/%d/1',
			$this->compte->getId(),
			$this->positiveCategory->getId()
		));
		self::assertResponseIsSuccessful();

		$this->client->xmlHttpRequest('POST', '/compte/sc/'.$this->positiveSubCategory->getId());
		self::assertResponseIsSuccessful();

		$this->client->xmlHttpRequest('POST', sprintf(
			'/compte/operation/%d/%s/%s/1',
			$this->positiveSubCategory->getId(),
			date('Y'),
			date('n')
		));
		self::assertResponseIsSuccessful();
	}

	public function testMonthDisplayModesAlwaysIncludeCurrentMonth(): void
	{
		$this->client->loginUser($this->owner);
		$currentMonth = (int) date('n');
		$expectedMonthCounts = [
			'year' => 12,
			'three' => min(12, $currentMonth + 3) - max(1, $currentMonth - 3) + 1,
			'one' => min(12, $currentMonth + 1) - max(1, $currentMonth - 1) + 1,
			'current' => 1,
		];
		foreach ($expectedMonthCounts as $mode => $expectedMonthCount){
			$crawler = $this->client->request(
				'GET',
				'/compte/'.$this->compte->getId().'?months='.$mode
			);

			self::assertResponseIsSuccessful();
			self::assertCount($expectedMonthCount, $crawler->filter('.bck_pos th[id^="month_"]'));
			self::assertCount(0, $crawler->filter('.month-placeholder'));
			self::assertCount(3, $crawler->filter('.compte-table-viewport'));
			self::assertCount(1, $crawler->filter('.bck_pos #month_'.$currentMonth));
			self::assertSame($mode, $crawler->filter('#monthDisplay option[selected]')->attr('value'));
			self::assertCount(
				in_array($mode, ['one', 'current'], true) ? 1 : 0,
				$crawler->filter('.bck_pos table.month-display-'.$mode.'.month-display-compact')
			);
		}

		$crawler = $this->client->request(
			'GET',
			'/compte/'.$this->compte->getId().'?months=unknown'
		);
		self::assertResponseIsSuccessful();
		self::assertCount(12, $crawler->filter('.bck_pos th[id^="month_"]'));
		self::assertSame('year', $crawler->filter('#monthDisplay option[selected]')->attr('value'));
	}

	public function testMonthDisplayModeIsAppliedToAjaxTableRefresh(): void
	{
		$this->client->loginUser($this->owner);
		$currentMonth = (int) date('n');

		$this->client->xmlHttpRequest(
			'POST',
			'/compte/'.$this->compte->getId().'/tables?year='.date('Y').'&months=current'
		);

		self::assertResponseIsSuccessful();
		$response = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
		$crawler = new Crawler($response['render']);
		self::assertCount(1, $crawler->filter('.bck_pos th[id^="month_"]'));
		self::assertCount(0, $crawler->filter('.month-placeholder'));
		self::assertCount(3, $crawler->filter('.compte-table-viewport'));
		self::assertCount(1, $crawler->filter('.bck_pos #month_'.$currentMonth));
		self::assertCount(1, $crawler->filter('.bck_pos table.month-display-current.month-display-compact'));
	}

	public function testAnotherUserCannotReadOrMutateAccountResources(): void
	{
		$this->client->loginUser($this->intruder);

		$this->client->request('GET', '/compte/'.$this->compte->getId());
		self::assertResponseStatusCodeSame(403);

		$this->client->request('GET', '/compte/'.$this->compte->getId().'/edit');
		self::assertResponseStatusCodeSame(403);

		$this->client->request('POST', '/compte/'.$this->compte->getId());
		self::assertResponseStatusCodeSame(403);

		$this->client->xmlHttpRequest('POST', '/compte/'.$this->compte->getId().'/tables');
		self::assertResponseStatusCodeSame(403);

		$this->client->xmlHttpRequest('POST', sprintf(
			'/compte/cat/%d/%d/1',
			$this->compte->getId(),
			$this->positiveCategory->getId()
		));
		self::assertResponseStatusCodeSame(403);

		$this->client->xmlHttpRequest('POST', '/compte/caty/add/'.$this->compte->getId().'/1');
		self::assertResponseStatusCodeSame(403);

		$this->client->xmlHttpRequest('POST', '/compte/'.$this->compte->getId().'/cat/save/'.date('Y'));
		self::assertResponseStatusCodeSame(403);

		$this->client->xmlHttpRequest('POST', sprintf(
			'/compte/cat/delete/%d/%d',
			$this->compte->getId(),
			$this->positiveCategory->getId()
		));
		self::assertResponseStatusCodeSame(403);

		$this->client->xmlHttpRequest('POST', '/compte/sc/'.$this->positiveSubCategory->getId());
		self::assertResponseStatusCodeSame(403);

		$this->client->xmlHttpRequest('POST', sprintf(
			'/compte/operation/%d/%s/%s/1',
			$this->positiveSubCategory->getId(),
			date('Y'),
			date('n')
		));
		self::assertResponseStatusCodeSame(403);
	}

	public function testAjaxEndpointsRejectGetRequests(): void
	{
		$this->client->loginUser($this->owner);

		$this->client->request('GET', '/compte/'.$this->compte->getId().'/tables');
		self::assertResponseStatusCodeSame(405);

		$this->client->request('GET', sprintf(
			'/compte/operation/%d/%s/%s/1',
			$this->positiveSubCategory->getId(),
			date('Y'),
			date('n')
		));
		self::assertResponseStatusCodeSame(405);
	}

	public function testResourcesCannotBeMixedAcrossAccounts(): void
	{
		$entityManager = static::getContainer()->get(EntityManagerInterface::class);
		$foreignCompte = (new Compte())
			->setLibelle('Compte etranger')
			->setMain(true)
			->setDecouvert(0)
			->setType($this->compte->getType())
			->addUser($this->intruder)
		;
		$foreignCategory = $this->createCategory($foreignCompte, 'Categorie etrangere', true, (int) date('Y'));
		$foreignSubCategory = $this->createSubCategory($foreignCategory, 'Sous-categorie etrangere');
		$foreignOperation = $this->createOperation($foreignSubCategory, 25, false);

		foreach ([$foreignCompte, $foreignCategory, $foreignSubCategory, $foreignOperation] as $entity){
			$entityManager->persist($entity);
		}
		$entityManager->flush();
		$this->createdIds[Operation::class][] = $foreignOperation->getId();
		$this->createdIds[SubCategory::class][] = $foreignSubCategory->getId();
		$this->createdIds[Category::class][] = $foreignCategory->getId();
		$this->createdIds[Compte::class][] = $foreignCompte->getId();

		$this->client->loginUser($this->owner);
		$this->client->xmlHttpRequest('POST', sprintf(
			'/compte/cat/%d/%d/1',
			$this->compte->getId(),
			$foreignCategory->getId()
		));
		self::assertResponseStatusCodeSame(404);

		$this->client->xmlHttpRequest(
			'POST',
			sprintf(
				'/compte/operation/save/%d/%s/%s/1',
				$this->positiveSubCategory->getId(),
				date('Y'),
				date('n')
			),
			['datas' => [['id' => $foreignOperation->getId(), 'delete' => 1]]]
		);
		self::assertResponseStatusCodeSame(404);

		$entityManager = static::getContainer()->get(EntityManagerInterface::class);
		$entityManager->clear();
		$operation = $entityManager->find(Operation::class, $foreignOperation->getId());
		self::assertNotNull($operation);
		self::assertTrue($operation->isActif());
	}

	private function createUser(string $userName): User
	{
		$user = (new User())
			->setUserName($userName)
			->setPassword('test-password')
			->setRoles(['ROLE_USER'])
		;
		$user->setPreferences((new UserPreference())->setCompteGenreShow(true));

		return $user;
	}

	private function createCategory(Compte $compte, string $label, bool $sign, int $year): Category
	{
		return (new Category())
			->setCompte($compte)
			->setLibelle($label)
			->setSign($sign)
			->setPosition(1)
			->setYear($year)
		;
	}

	private function createSubCategory(Category $category, string $label): SubCategory
	{
		return (new SubCategory())
			->setCategory($category)
			->setLibelle($label)
			->setPosition(1)
		;
	}

	private function createOperation(SubCategory $subCategory, float $number, bool $anticipated): Operation
	{
		$now = new \DateTime();

		return (new Operation())
			->setSubcategory($subCategory)
			->setNumber($number)
			->setAnticipe($anticipated)
			->setDate($now)
			->setLastAction('create')
			->setDateLastAction($now)
			->setActif(true)
		;
	}
}
