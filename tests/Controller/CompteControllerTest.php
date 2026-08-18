<?php

namespace App\Tests\Controller;

use App\Entity\Category;
use App\Entity\Compte;
use App\Entity\CompteType;
use App\Entity\Operation;
use App\Entity\OperationAction;
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
	private SubCategory $negativeSubCategory;
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
		$this->negativeSubCategory = $this->createSubCategory($this->negativeCategory, 'Factures');
		$realIncome = $this->createOperation($this->positiveSubCategory, 100, false);
		$anticipatedExpense = $this->createOperation($this->negativeSubCategory, 150, true);
		$realIncomeAction = $this->createOperationAction($realIncome);
		$anticipatedExpenseAction = $this->createOperationAction($anticipatedExpense);

		foreach ([
			$this->owner,
			$this->intruder,
			$type,
			$this->compte,
			$this->positiveCategory,
			$this->negativeCategory,
			$this->positiveSubCategory,
			$this->negativeSubCategory,
			$realIncome,
			$anticipatedExpense,
			$realIncomeAction,
			$anticipatedExpenseAction,
		] as $entity){
			$entityManager->persist($entity);
		}
		$entityManager->flush();

		$this->createdIds = [
			Operation::class => [$realIncome->getId(), $anticipatedExpense->getId()],
			SubCategory::class => [$this->positiveSubCategory->getId(), $this->negativeSubCategory->getId()],
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
		self::assertSelectorExists('meta[name="viewport"][content="width=device-width, initial-scale=1"]');
		self::assertSelectorTextContains('h1', $this->compte->getLibelle());
		self::assertSelectorTextSame('#soldeActuelNb', '100,00');
		self::assertSelectorExists('#soldeActuel.total_month_full_pos');
		self::assertSelectorTextSame('#soldeFinMoisNb', '-50,00');
		self::assertSelectorExists('#soldeFinMois.total_month_full_neg');
		self::assertSelectorExists('#legendButton[data-target="#modalLegend"]');
		self::assertSelectorExists('#legendButton.header-action-button');
		self::assertSelectorExists('#tutorialButton.header-action-button');
		self::assertSelectorExists('#legendButton + #tutorialButton');
		self::assertSame('0', $this->client->getCrawler()->filter('#datas')->attr('data-account-tutorial-seen'));
		self::assertStringContainsString('/user/preference/'.$this->owner->getId().'/account-tutorial-seen', $this->client->getCrawler()->filter('#datas')->attr('data-account-tutorial-seen-url'));
		self::assertNotSame('', $this->client->getCrawler()->filter('#datas')->attr('data-account-tutorial-seen-token'));
		self::assertSelectorExists('#modalAccountTutorial.account-tutorial-modal');
		self::assertSelectorExists('#startAccountTourButton.account-tutorial-tour-start');
		self::assertSelectorTextContains('#modalAccountTutorialTitle', 'Prendre en main la page compte');
		self::assertSelectorTextContains('#modalAccountTutorial', 'le bouton Tutoriel permet de revoir cette aide à tout moment.');
		self::assertSelectorExists('.balance-summary #soldeActuel');
		self::assertSelectorExists('.balance-summary #soldeFinMois');
		self::assertSelectorTextSame('#overdraftAuthorization', '0,00 €');
		self::assertSelectorExists('#soldeActuelAlert.balance-alert-current');
		self::assertSelectorExists('#soldeFinMoisAlert.balance-alert-projected');
		self::assertStringContainsString('fa-exclamation-triangle', $this->client->getCrawler()->filter('#soldeFinMoisAlert')->html());
		self::assertSelectorNotExists('#budgetSwitcherButton');
		self::assertSelectorTextSame('.anomalies-preview > .control-label', 'Anomalies');
		self::assertSelectorExists('#anomaliesButton[data-target="#modalAnomalies"]');
		self::assertSelectorExists('#anomaliesButton[aria-label="Ouvrir les anomalies"]');
		self::assertSelectorTextContains('#anomaliesButton .anomalies-count', '0');
		self::assertSelectorTextContains('#modalAnomaliesTitle', 'Correction des anomalies');
		self::assertSelectorTextContains('#modalAnomalies', 'Aucune anomalie détectée');
		self::assertSelectorExists('.account-view-controls .year-navigation');
		self::assertSelectorExists('#yearNavigationForm #yearPicker[role="combobox"][pattern="[0-9]{4}"][aria-controls="yearPickerOptions"]');
		self::assertSelectorExists('.account-view-controls .month-display-field #monthDisplay');
		self::assertSelectorNotExists('.last-action-current .undo-last-action');
		self::assertSelectorExists('.last-actions-popover .undo-last-action');
		self::assertSelectorExists('.last-actions-popover .undo-action .fa-rotate-left');
		self::assertSelectorExists('.last-actions-popover .action-zone-pos');
		self::assertSelectorExists('.last-actions-popover .action-zone-neg');
		self::assertSelectorTextSame('.last-action-preview > .control-label', 'Dernières actions');
		self::assertSelectorTextContains('.last-actions-popover-title', '15 dernières actions');
		self::assertSelectorTextContains('#modalLegendTitle', 'Légende des couleurs');
		self::assertSelectorTextContains('#legendBackgroundTitle', 'Couleurs de fond');
		self::assertSelectorTextContains('#legendTextTitle', 'Couleurs du texte');
		self::assertSelectorExists('#modalLegend .legend-close-button[aria-label="Fermer"] .fa-xmark');
		self::assertSelectorExists('#modalLegend .legend-background-sample.bck_pos');
		self::assertSelectorExists('#modalLegend .legend-background-sample.bck_neg');
		self::assertSelectorTextContains('#modalLegend', "Bordure jaune d'une zone modifiable");
		self::assertSelectorExists('#modalLegend .legend-text-sample.total_month_full_dec');
		self::assertSelectorExists('#modalLegend .legend-text-sample.anticipe');
		self::assertSelectorExists('#modalLegend .legend-alert-sample.balance-alert-current .fa-exclamation');
		self::assertSelectorExists('#modalLegend .legend-alert-sample.balance-alert-projected .fa-exclamation-triangle');
		self::assertSelectorTextContains('#modalLegend', 'Le solde actuel');
		self::assertSelectorTextContains('#modalLegend', 'Triangle blanc avec un ! rouge');

		$this->client->xmlHttpRequest(
			'POST',
			$this->client->getCrawler()->filter('#datas')->attr('data-account-tutorial-seen-url'),
			['_token' => $this->client->getCrawler()->filter('#datas')->attr('data-account-tutorial-seen-token')]
		);
		self::assertResponseIsSuccessful();
		$tutorialResponse = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
		self::assertTrue($tutorialResponse['saved']);
		$entityManager = static::getContainer()->get(EntityManagerInterface::class);
		$entityManager->clear();
		self::assertTrue($entityManager->find(User::class, $this->owner->getId())->getPreferences()->isAccountTutorialSeen());

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

	public function testMonthEndBalanceIncludesOverdueAnticipatedOperations(): void
	{
		if ((int) date('n') === 1){
			self::markTestSkipped("Ce test a besoin d'un mois precedent dans la meme annee.");
		}

		$entityManager = static::getContainer()->get(EntityManagerInterface::class);
		$overdueAnticipatedExpense = $this->createOperation($this->negativeSubCategory, 25, true)
			->setDate(new \DateTime(date('Y').'/'.((int) date('n') - 1).'/01'))
		;
		$entityManager->persist($overdueAnticipatedExpense);
		$entityManager->flush();
		$this->createdIds[Operation::class][] = $overdueAnticipatedExpense->getId();

		$this->client->loginUser($this->owner);
		$crawler = $this->client->request('GET', '/compte/'.$this->compte->getId());

		self::assertResponseIsSuccessful();
		self::assertSelectorTextSame('#soldeFinMoisNb', '-75,00');
		self::assertSelectorExists('#soldeFinMois.total_month_full_neg');

		$currentMonth = (string) (int) date('n');
		$monthEndBalance = $crawler->filter('.gains-table tr:nth-child(2) .month-cell-span[data-month="'.$currentMonth.'"]');
		self::assertCount(1, $monthEndBalance);
		self::assertSame('-75,00', trim($monthEndBalance->text()));

		$this->client->xmlHttpRequest('POST', '/compte/'.$this->compte->getId().'/tables?year='.date('Y'));
		self::assertResponseIsSuccessful();
		$response = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
		self::assertSame(100.0, (float) $response['solde']);
		self::assertSame(-75.0, (float) $response['soldeFinMensuel']);

		$tablesCrawler = new Crawler($response['render']);
		$ajaxMonthEndBalance = $tablesCrawler->filter('.gains-table tr:nth-child(2) .month-cell-span[data-month="'.$currentMonth.'"]');
		self::assertCount(1, $ajaxMonthEndBalance);
		self::assertSame('-75,00', trim($ajaxMonthEndBalance->text()));
	}

	public function testBudgetSwitcherIsOnlyDisplayedWhenAnotherBudgetIsAvailable(): void
	{
		$entityManager = static::getContainer()->get(EntityManagerInterface::class);
		$otherCompte = (new Compte())
			->setLibelle('Budget secondaire')
			->setMain(false)
			->setDecouvert(0)
			->setType($this->compte->getType())
			->addUser($this->owner)
		;
		$entityManager->persist($otherCompte);
		$entityManager->flush();
		$this->createdIds[Compte::class][] = $otherCompte->getId();

		$this->client->loginUser($this->owner);
		$crawler = $this->client->request('GET', '/compte/'.$this->compte->getId().'?months=one');

		self::assertResponseIsSuccessful();
		self::assertCount(1, $crawler->filter('#budgetSwitcherButton[aria-controls="budgetSwitcherMenu"]'));
		$link = $crawler->filter('#budgetSwitcherMenu .budget-switcher-option')->first();
		self::assertStringContainsString('/compte/'.$otherCompte->getId(), (string) $link->attr('href'));
		self::assertStringContainsString('months=one', (string) $link->attr('href'));
		self::assertStringContainsString('Budget secondaire', $link->text());
	}

	public function testTableDisplayPreferencesCanBeSavedAndApplied(): void
	{
		$this->client->loginUser($this->owner);
		$crawler = $this->client->request('GET', '/user/preference/'.$this->owner->getId());

		self::assertResponseIsSuccessful();
		self::assertCount(6, $crawler->filter('input[name="user_preference[tablePalette]"]'));
		self::assertSelectorExists('input[name="user_preference[showEditableBorder]"]:checked');
		self::assertSelectorNotExists('.preference-submit-button');
		self::assertSelectorExists('form[data-preference-autosave="true"]');
		self::assertSelectorExists('.preference-save-mascot');
		self::assertSelectorExists('.preference-page-link[data-preference-section="credit"]');
		self::assertSelectorExists('.preference-page-link[data-preference-section="immobilier"]');
		self::assertSelectorExists('.preference-page-link[data-preference-section="mobilier"]');
		self::assertSelectorExists('.preference-page-link[data-preference-section="investissement"]');
		self::assertSelectorExists('.preference-page-link[data-preference-section="total"]');
		$this->client->xmlHttpRequest('POST', '/user/preference/'.$this->owner->getId(), [
			'user_preference' => [
				'dashboardBackground' => 'green',
				'accountBackground' => 'green',
				'compteGenreShow' => '1',
				'tablePalette' => 'soft',
				'_token' => $crawler->filter('input[name="user_preference[_token]"]')->attr('value'),
			],
		]);

		self::assertResponseIsSuccessful();
		$response = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
		self::assertTrue($response['saved']);
		$entityManager = static::getContainer()->get(EntityManagerInterface::class);
		$entityManager->clear();
		$user = $entityManager->find(User::class, $this->owner->getId());
		self::assertNotNull($user);
		self::assertSame('soft', $user->getPreferences()->getTablePalette());
		self::assertFalse($user->getPreferences()->isShowEditableBorder());

		$this->client->loginUser($user);
		$this->client->request('GET', '/compte/'.$this->compte->getId());
		self::assertResponseIsSuccessful();
		self::assertSame(
			'table-palette-soft hide-editable-border',
			$this->client->getCrawler()->filter('#datas')->attr('class')
		);
	}

	public function testCategoriesCanBeReorderedAndTheMoveCanBeUndoneAndRestored(): void
	{
		$entityManager = static::getContainer()->get(EntityManagerInterface::class);
		$secondCategory = $this->createCategory(
			$this->compte,
			'Primes',
			true,
			(int) date('Y')
		)->setPosition(2);
		$this->compte->addCategory($secondCategory);
		$secondSubCategory = $this->createSubCategory($secondCategory, 'Prime annuelle');
		$secondCategory->addSubCategory($secondSubCategory);
		$entityManager->persist($secondCategory);
		$entityManager->persist($secondSubCategory);
		$entityManager->flush();
		$secondCategoryId = $secondCategory->getId();
		$this->createdIds[SubCategory::class][] = $secondSubCategory->getId();
		$this->createdIds[Category::class][] = $secondCategoryId;

		$this->client->loginUser($this->owner);
		$crawler = $this->client->request('GET', '/compte/'.$this->compte->getId());
		$table = $crawler->filter('.compteTable[data-sign="1"]');

		self::assertResponseIsSuccessful();
		self::assertCount(2, $table->filter('.category-drag-handle'));
		self::assertCount(1, $table->filter('[data-category-drop-end="true"]'));
		self::assertCount(1, $table->filter('[data-category-drop-before="'.$secondCategoryId.'"]'));
		$this->client->xmlHttpRequest('POST', $table->attr('data-category-reorder-url'), [
			'_token' => $table->attr('data-category-reorder-token'),
			'category' => $secondCategoryId,
			'before' => $secondCategoryId,
		]);
		self::assertResponseIsSuccessful();
		$unchangedResponse = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
		self::assertTrue($unchangedResponse['unchanged']);
		self::assertCount(0, $entityManager->getRepository(OperationAction::class)->findBy(['actionType' => 'move']));

		$this->client->xmlHttpRequest('POST', $table->attr('data-category-reorder-url'), [
			'_token' => $table->attr('data-category-reorder-token'),
			'category' => $secondCategoryId,
			'before' => $this->positiveCategory->getId(),
		]);

		self::assertResponseIsSuccessful();
		$response = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
		self::assertTrue($response['moved']);
		$entityManager->clear();
		self::assertSame(1, $entityManager->find(Category::class, $secondCategoryId)->getPosition());
		self::assertSame(2, $entityManager->find(Category::class, $this->positiveCategory->getId())->getPosition());
		$moveAction = $entityManager->getRepository(OperationAction::class)->findOneBy([
			'category' => $secondCategoryId,
			'actionType' => 'move',
		]);
		self::assertNotNull($moveAction);
		$moveActionId = $moveAction->getId();
		self::assertSame(2, $moveAction->getBeforeSnapshot()['position']);
		self::assertSame(1, $moveAction->getAfterSnapshot()['position']);

		$crawler = $this->client->request('GET', '/compte/'.$this->compte->getId());
		self::assertSelectorTextContains('.last-action-trigger', 'Déplacement');
		self::assertSelectorTextContains('.last-action-trigger', 'Primes');
		self::assertSelectorTextContains('.last-actions-item:first-child', 'Position 2 → 1');
		$undoButton = $crawler->filter('.undo-last-action[data-action-id="'.$moveActionId.'"]');
		$this->client->xmlHttpRequest('POST', $undoButton->attr('data-url'), [
			'_token' => $undoButton->attr('data-token'),
		]);

		self::assertResponseIsSuccessful();
		$entityManager->clear();
		self::assertSame(1, $entityManager->find(Category::class, $this->positiveCategory->getId())->getPosition());
		self::assertSame(2, $entityManager->find(Category::class, $secondCategoryId)->getPosition());
		self::assertTrue($entityManager->find(OperationAction::class, $moveActionId)->isCancelled());
		self::assertCount(1, $entityManager->getRepository(OperationAction::class)->findBy(['actionType' => 'move']));

		$crawler = $this->client->request('GET', '/compte/'.$this->compte->getId());
		$restoreButton = $crawler->filter('.undo-last-action[data-action-id="'.$moveActionId.'"]');
		self::assertSame('Restaurer cette action', $restoreButton->attr('title'));
		$this->client->xmlHttpRequest('POST', $restoreButton->attr('data-url'), [
			'_token' => $restoreButton->attr('data-token'),
		]);

		self::assertResponseIsSuccessful();
		$entityManager->clear();
		self::assertSame(2, $entityManager->find(Category::class, $this->positiveCategory->getId())->getPosition());
		self::assertSame(1, $entityManager->find(Category::class, $secondCategoryId)->getPosition());
		self::assertFalse($entityManager->find(OperationAction::class, $moveActionId)->isCancelled());
		self::assertCount(1, $entityManager->getRepository(OperationAction::class)->findBy(['actionType' => 'move']));
	}

	public function testSubCategoriesCanBeReorderedAndTheMoveCanBeUndoneAndRestored(): void
	{
		$entityManager = static::getContainer()->get(EntityManagerInterface::class);
		$secondSubCategory = $this->createSubCategory($this->positiveCategory, 'Prime mensuelle')
			->setPosition(2)
		;
		$this->positiveCategory->addSubCategory($secondSubCategory);
		$entityManager->persist($secondSubCategory);
		$entityManager->flush();
		$secondSubCategoryId = $secondSubCategory->getId();
		$this->createdIds[SubCategory::class][] = $secondSubCategoryId;

		$this->client->loginUser($this->owner);
		$crawler = $this->client->request('GET', '/compte/'.$this->compte->getId());
		$table = $crawler->filter('.compteTable[data-sign="1"]');

		self::assertResponseIsSuccessful();
		self::assertSame('#accountPreferencesPanel', $crawler->filter('#accountPreferencesButton')->attr('href'));
		self::assertCount(1, $crawler->filter('#accountPreferencesPanel form[data-account-preference-autosave="true"]'));
		self::assertCount(2, $table->filter('.subcategory-drag-handle'));
		self::assertCount(1, $table->filter('[data-subcategory-drop-before="'.$secondSubCategoryId.'"]'));
		self::assertCount(1, $table->filter('[data-subcategory-drop-end="'.$this->positiveCategory->getId().'"]'));
		self::assertCount(0, $table->filter('.td_category_libelle[data-subcategory-drop-end="'.$this->positiveCategory->getId().'"]'));

		$this->client->xmlHttpRequest('POST', $table->attr('data-subcategory-reorder-url'), [
			'_token' => $table->attr('data-subcategory-reorder-token'),
			'subcategory' => $secondSubCategoryId,
			'category' => $this->positiveCategory->getId(),
			'before' => $secondSubCategoryId,
		]);
		self::assertResponseIsSuccessful();
		$unchangedResponse = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
		self::assertTrue($unchangedResponse['unchanged']);
		self::assertCount(0, $entityManager->getRepository(OperationAction::class)->findBy(['category' => $this->positiveCategory, 'actionType' => 'move']));

		$this->client->xmlHttpRequest('POST', $table->attr('data-subcategory-reorder-url'), [
			'_token' => $table->attr('data-subcategory-reorder-token'),
			'subcategory' => $secondSubCategoryId,
			'category' => $this->positiveCategory->getId(),
			'before' => $this->positiveSubCategory->getId(),
		]);

		self::assertResponseIsSuccessful();
		$response = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
		self::assertTrue($response['moved']);
		$entityManager->clear();
		self::assertSame(1, $entityManager->find(SubCategory::class, $secondSubCategoryId)->getPosition());
		self::assertSame(2, $entityManager->find(SubCategory::class, $this->positiveSubCategory->getId())->getPosition());
		$moveAction = $entityManager->getRepository(OperationAction::class)->findOneBy([
			'category' => $this->positiveCategory->getId(),
			'actionType' => 'move',
		]);
		self::assertNotNull($moveAction);
		self::assertTrue($moveAction->isSubCategoryMove());
		$moveActionId = $moveAction->getId();
		self::assertSame('subcategory', $moveAction->getBeforeSnapshot()['scope']);
		self::assertSame(2, $moveAction->getBeforeSnapshot()['position']);
		self::assertSame(1, $moveAction->getAfterSnapshot()['position']);

		$crawler = $this->client->request('GET', '/compte/'.$this->compte->getId());
		self::assertSelectorTextContains('.last-action-trigger', 'Déplacement');
		self::assertSelectorTextContains('.last-action-trigger', 'Revenus / Prime mensuelle');
		self::assertSelectorTextContains('.last-actions-item:first-child', 'Position 2 → 1');
		$undoButton = $crawler->filter('.undo-last-action[data-action-id="'.$moveActionId.'"]');
		$this->client->xmlHttpRequest('POST', $undoButton->attr('data-url'), [
			'_token' => $undoButton->attr('data-token'),
		]);

		self::assertResponseIsSuccessful();
		$entityManager->clear();
		self::assertSame(1, $entityManager->find(SubCategory::class, $this->positiveSubCategory->getId())->getPosition());
		self::assertSame(2, $entityManager->find(SubCategory::class, $secondSubCategoryId)->getPosition());
		self::assertTrue($entityManager->find(OperationAction::class, $moveActionId)->isCancelled());

		$crawler = $this->client->request('GET', '/compte/'.$this->compte->getId());
		$restoreButton = $crawler->filter('.undo-last-action[data-action-id="'.$moveActionId.'"]');
		self::assertSame('Restaurer cette action', $restoreButton->attr('title'));
		$this->client->xmlHttpRequest('POST', $restoreButton->attr('data-url'), [
			'_token' => $restoreButton->attr('data-token'),
		]);

		self::assertResponseIsSuccessful();
		$entityManager->clear();
		self::assertSame(2, $entityManager->find(SubCategory::class, $this->positiveSubCategory->getId())->getPosition());
		self::assertSame(1, $entityManager->find(SubCategory::class, $secondSubCategoryId)->getPosition());
		self::assertFalse($entityManager->find(OperationAction::class, $moveActionId)->isCancelled());
	}

	public function testOverdueAnticipatedOperationCanBeResolvedFromAnomaliesModal(): void
	{
		$entityManager = static::getContainer()->get(EntityManagerInterface::class);
		$operation = $this->createOperation($this->positiveSubCategory, 20, true)
			->setDate(new \DateTime('yesterday'))
		;
		$entityManager->persist($operation);
		$entityManager->flush();
		$operationId = $operation->getId();
		$this->createdIds[Operation::class][] = $operationId;
		$actionCount = $entityManager->getRepository(OperationAction::class)->count([]);

		$this->client->loginUser($this->owner);
		$crawler = $this->client->request('GET', '/compte/'.$this->compte->getId());

		self::assertResponseIsSuccessful();
		self::assertSelectorExists('#anomaliesButton.has-anomalies');
		self::assertSelectorTextContains('#anomaliesButton .anomalies-count', '1');
		self::assertSelectorTextContains('#modalAnomalies .anomaly-item', 'Date anticipée dépassée');
		self::assertSelectorTextContains('#modalAnomalies .resolve-anomaly-realize', 'Marquer comme réalisée');
		self::assertSelectorTextContains('#modalAnomalies .resolve-anomaly-delete', 'Supprimer la ligne');
		$button = $crawler->filter('#modalAnomalies .resolve-anomaly-realize')->first();

		$this->client->xmlHttpRequest('POST', $button->attr('data-url'), [
			'_token' => $button->attr('data-token'),
			'resolution' => $button->attr('data-resolution'),
		]);

		self::assertResponseIsSuccessful();
		$resolveResponse = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
		self::assertTrue($resolveResponse['resolved']);
		self::assertSame('realize', $resolveResponse['resolution']);
		self::assertFalse($resolveResponse['reusedAction']);
		$entityManager->clear();
		$resolvedOperation = $entityManager->find(Operation::class, $operationId);
		self::assertNotNull($resolvedOperation);
		self::assertFalse($resolvedOperation->isAnticipe());
		self::assertSame('edit', $resolvedOperation->getLastAction());
		self::assertSame($actionCount + 1, $entityManager->getRepository(OperationAction::class)->count([]));
		$correctionAction = $entityManager->getRepository(OperationAction::class)->findOneBy([
			'operation' => $resolvedOperation,
			'actionType' => 'edit',
		], ['actionAt' => 'DESC']);
		self::assertNotNull($correctionAction);
		$correctionActionId = $correctionAction->getId();

		$crawler = $this->client->request('GET', '/compte/'.$this->compte->getId());
		$undoButton = $crawler->filter('.undo-last-action[data-action-id="'.$correctionActionId.'"]');
		$this->client->xmlHttpRequest('POST', $undoButton->attr('data-url'), [
			'_token' => $undoButton->attr('data-token'),
		]);
		self::assertResponseIsSuccessful();
		$entityManager->clear();
		self::assertTrue($entityManager->find(Operation::class, $operationId)->isAnticipe());
		self::assertTrue($entityManager->find(OperationAction::class, $correctionActionId)->isCancelled());
		self::assertSame($actionCount + 1, $entityManager->getRepository(OperationAction::class)->count([]));

		$crawler = $this->client->request('GET', '/compte/'.$this->compte->getId());
		self::assertSelectorExists('#anomaliesButton.has-anomalies');
		$button = $crawler->filter('#modalAnomalies .resolve-anomaly-realize')->first();
		$this->client->xmlHttpRequest('POST', $button->attr('data-url'), [
			'_token' => $button->attr('data-token'),
			'resolution' => $button->attr('data-resolution'),
		]);
		self::assertResponseIsSuccessful();
		$resolveResponse = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
		self::assertTrue($resolveResponse['reusedAction']);
		$entityManager->clear();
		self::assertFalse($entityManager->find(Operation::class, $operationId)->isAnticipe());
		self::assertFalse($entityManager->find(OperationAction::class, $correctionActionId)->isCancelled());
		self::assertSame($actionCount + 1, $entityManager->getRepository(OperationAction::class)->count([]));

		$this->client->xmlHttpRequest('POST', '/compte/'.$this->compte->getId().'/tables?year='.date('Y'));
		self::assertResponseIsSuccessful();
		$response = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
		self::assertSame(120.0, (float) $response['solde']);
		self::assertStringContainsString('Aucune anomalie détectée', $response['render_anomalies_modal']);
		self::assertStringContainsString('>0</span>', $response['render_anomalies']);
	}

	public function testOverdueAnticipatedOperationCanBeDeletedAndDeletionCanBeUndone(): void
	{
		$entityManager = static::getContainer()->get(EntityManagerInterface::class);
		$operation = $this->createOperation($this->positiveSubCategory, 30, true)
			->setDate(new \DateTime('yesterday'))
		;
		$entityManager->persist($operation);
		$entityManager->flush();
		$operationId = $operation->getId();
		$this->createdIds[Operation::class][] = $operationId;

		$this->client->loginUser($this->owner);
		$crawler = $this->client->request('GET', '/compte/'.$this->compte->getId());
		$button = $crawler->filter('#modalAnomalies .resolve-anomaly-delete')->first();
		$this->client->xmlHttpRequest('POST', $button->attr('data-url'), [
			'_token' => $button->attr('data-token'),
			'resolution' => $button->attr('data-resolution'),
		]);

		self::assertResponseIsSuccessful();
		$response = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
		self::assertSame('delete', $response['resolution']);
		$entityManager->clear();
		$deletedOperation = $entityManager->find(Operation::class, $operationId);
		self::assertFalse($deletedOperation->isActif());
		self::assertSame('del', $deletedOperation->getLastAction());
		$deleteAction = $entityManager->getRepository(OperationAction::class)->findOneBy([
			'operation' => $deletedOperation,
			'actionType' => 'del',
		], ['actionAt' => 'DESC']);
		self::assertNotNull($deleteAction);

		$crawler = $this->client->request('GET', '/compte/'.$this->compte->getId());
		$undoButton = $crawler->filter('.undo-last-action[data-action-id="'.$deleteAction->getId().'"]');
		$this->client->xmlHttpRequest('POST', $undoButton->attr('data-url'), [
			'_token' => $undoButton->attr('data-token'),
		]);

		self::assertResponseIsSuccessful();
		$entityManager->clear();
		$restoredOperation = $entityManager->find(Operation::class, $operationId);
		self::assertTrue($restoredOperation->isActif());
		self::assertTrue($restoredOperation->isAnticipe());
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
			self::assertCount(1, $crawler->filter('.bck_pos #month_'.$currentMonth.'[data-month="'.$currentMonth.'"][role="button"]'));
			self::assertCount(1, $crawler->filter('.bck_pos #month_'.$currentMonth.'.month-cell-span'));
			self::assertGreaterThan(0, $crawler->filter('.bck_pos td[data-month="'.$currentMonth.'"]')->count());
			self::assertGreaterThan(0, $crawler->filter('.bck_pos td.month-cell-start[data-month="'.$currentMonth.'"]')->count());
			self::assertGreaterThan(0, $crawler->filter('.bck_pos td.month-cell-end[data-month="'.$currentMonth.'"]')->count());
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

	public function testYearPickerOffersFutureYearsAndPastYearsWithABudget(): void
	{
		$currentYear = (int) date('Y');
		$pastBudgetYear = $currentYear - 3;
		$pastCategory = $this->createCategory($this->compte, 'Ancien budget', true, $pastBudgetYear);
		$entityManager = static::getContainer()->get(EntityManagerInterface::class);
		$entityManager->persist($pastCategory);
		$entityManager->flush();
		$this->createdIds[Category::class][] = $pastCategory->getId();

		$this->client->loginUser($this->owner);
		$crawler = $this->client->request('GET', '/compte/'.$this->compte->getId().'?months=one');

		self::assertResponseIsSuccessful();
		self::assertSame((string) $currentYear, $crawler->filter('#yearPicker')->attr('value'));
		self::assertSame('one', $crawler->filter('#yearNavigationForm input[name="months"]')->attr('value'));
		for ($year = $currentYear; $year <= $currentYear + 10; ++$year){
			self::assertCount(1, $crawler->filter('#yearPickerOptions .year-picker-option[data-year="'.$year.'"]'));
		}
		self::assertCount(1, $crawler->filter('#yearPickerOptions .year-picker-option[data-year="'.$pastBudgetYear.'"]'));
		self::assertCount(0, $crawler->filter('#yearPickerOptions .year-picker-option[data-year="'.($currentYear - 4).'"]'));
		self::assertCount(0, $crawler->filter('#yearPickerOptions .year-picker-option[data-year="'.($currentYear + 11).'"]'));

		$customYear = $currentYear + 20;
		$crawler = $this->client->request('GET', '/compte/'.$this->compte->getId().'?year='.$customYear);
		self::assertResponseIsSuccessful();
		self::assertSame((string) $customYear, $crawler->filter('#yearPicker')->attr('value'));
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

	public function testLastActionsPopoverIsLimitedToFifteenEntries(): void
	{
		$entityManager = static::getContainer()->get(EntityManagerInterface::class);
		for ($index = 1; $index <= 16; ++$index){
			$operation = $this->createOperation($this->positiveSubCategory, 10 + $index, false)
				->setDateLastAction((new \DateTime())->modify('+'.$index.' seconds'))
			;
			$action = $this->createOperationAction($operation);
			$entityManager->persist($operation);
			$entityManager->persist($action);
			$entityManager->flush();
			$this->createdIds[Operation::class][] = $operation->getId();
		}

		$this->client->loginUser($this->owner);
		$crawler = $this->client->request('GET', '/compte/'.$this->compte->getId());

		self::assertResponseIsSuccessful();
		self::assertCount(15, $crawler->filter('.last-actions-popover .last-actions-item'));
		self::assertCount(15, $crawler->filter('.last-actions-popover .undo-last-action'));
		self::assertSelectorTextContains('.last-action-summary', 'Revenus / Salaire');
		self::assertSelectorExists('.last-action-summary.action-zone-pos');
	}

	public function testLastActionLabelUsesSingularWithOneAction(): void
	{
		$entityManager = static::getContainer()->get(EntityManagerInterface::class);
		$action = $entityManager->getRepository(OperationAction::class)->findOneBy([
			'operation' => $this->createdIds[Operation::class][0],
		]);
		self::assertNotNull($action);
		$entityManager->remove($action);
		$entityManager->flush();

		$this->client->loginUser($this->owner);
		$this->client->request('GET', '/compte/'.$this->compte->getId());

		self::assertResponseIsSuccessful();
		self::assertSelectorTextSame('.last-action-preview > .control-label', 'Dernière action');
	}

	public function testAnyCreatedOperationCanBeUndoneAndItsUndoCanBeReverted(): void
	{
		$entityManager = static::getContainer()->get(EntityManagerInterface::class);
		$operation = $this->createOperation($this->positiveSubCategory, 75, false)
			->setDateLastAction((new \DateTime())->modify('+2 minutes'))
		;
		$action = $this->createOperationAction($operation);
		$entityManager->persist($operation);
		$entityManager->persist($action);
		$entityManager->flush();
		$operationId = $operation->getId();
		$actionId = $action->getId();
		$this->createdIds[Operation::class][] = $operationId;
		$newerOperation = $this->createOperation($this->positiveSubCategory, 25, false)
			->setDateLastAction((new \DateTime())->modify('+3 minutes'))
		;
		$newerAction = $this->createOperationAction($newerOperation);
		$entityManager->persist($newerOperation);
		$entityManager->persist($newerAction);
		$entityManager->flush();
		$this->createdIds[Operation::class][] = $newerOperation->getId();
		$actionCount = $entityManager->getRepository(OperationAction::class)->count([]);

		$this->client->loginUser($this->owner);
		$crawler = $this->client->request('GET', '/compte/'.$this->compte->getId());
		$button = $crawler->filter('.undo-last-action[data-action-id="'.$actionId.'"]');

		$this->client->xmlHttpRequest(
			'POST',
			$button->attr('data-url'),
			['_token' => $button->attr('data-token')]
		);

		self::assertResponseIsSuccessful();
		$entityManager->clear();
		$operation = $entityManager->find(Operation::class, $operationId);
		self::assertNotNull($operation);
		self::assertFalse($operation->isActif());
		$cancelledOperationAction = $entityManager->find(OperationAction::class, $actionId);
		self::assertTrue($cancelledOperationAction->isCancelled());
		self::assertNotNull($cancelledOperationAction->getUndoSnapshot());
		self::assertSame($actionCount, $entityManager->getRepository(OperationAction::class)->count([]));
		self::assertCount(0, $entityManager->getRepository(OperationAction::class)->findBy(['actionType' => 'undo']));
		$this->client->xmlHttpRequest('POST', '/compte/'.$this->compte->getId().'/tables?year='.date('Y'));
		$tablesResponse = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
		self::assertSame(125.0, (float) $tablesResponse['solde']);

		$crawler = $this->client->request('GET', '/compte/'.$this->compte->getId());
		self::assertSelectorNotExists('.last-actions-popover .action-kind-undo');
		self::assertCount($actionCount, $crawler->filter('.last-actions-popover .last-actions-item'));
		self::assertSelectorNotExists('.action-cancelled-status');
		$undoButton = $crawler->filter('.undo-last-action[data-action-id="'.$actionId.'"]');
		self::assertSame('Cette action est annulée', $crawler->filter('.last-actions-item.is-cancelled')->attr('title'));
		self::assertSame('Restaurer cette action', $undoButton->attr('title'));
		self::assertSame('Restaurer cette action', $undoButton->attr('aria-label'));
		self::assertCount(1, $undoButton->filter('.fa-rotate-left.action-restore-icon'));
		self::assertTrue($undoButton->matches('.undo-revert-action'));
		self::assertNull($undoButton->attr('disabled'));
		$this->client->xmlHttpRequest(
			'POST',
			$undoButton->attr('data-url'),
			['_token' => $undoButton->attr('data-token')]
		);

		self::assertResponseIsSuccessful();
		$response = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
		self::assertTrue($response['undoReverted']);
		$entityManager->clear();
		self::assertTrue($entityManager->find(Operation::class, $operationId)->isActif());
		$restoredOperationAction = $entityManager->find(OperationAction::class, $actionId);
		self::assertFalse($restoredOperationAction->isCancelled());
		self::assertNull($restoredOperationAction->getUndoSnapshot());
		self::assertSame($actionCount, $entityManager->getRepository(OperationAction::class)->count([]));
		self::assertCount(0, $entityManager->getRepository(OperationAction::class)->findBy(['actionType' => 'undo']));
	}

	public function testLatestEditedOperationCanBeRestored(): void
	{
		$operationId = $this->createdIds[Operation::class][0];
		$this->client->loginUser($this->owner);
		$this->client->xmlHttpRequest(
			'POST',
			sprintf(
				'/compte/operation/save/%d/%s/%s/1',
				$this->positiveSubCategory->getId(),
				date('Y'),
				date('n')
			),
			['datas' => [[
				'id' => $operationId,
				'delete' => 0,
				'number' => '250',
				'anticipe' => '',
				'day' => date('j'),
				'month' => date('n'),
				'year' => date('Y'),
				'comment' => 'Montant modifie',
			]]]
		);
		self::assertResponseIsSuccessful();

		$crawler = $this->client->request('GET', '/compte/'.$this->compte->getId());
		$button = $crawler->filter('.last-actions-item')->first()->filter('.undo-last-action');
		$this->client->xmlHttpRequest(
			'POST',
			$button->attr('data-url'),
			['_token' => $button->attr('data-token')]
		);

		self::assertResponseIsSuccessful();
		$entityManager = static::getContainer()->get(EntityManagerInterface::class);
		$entityManager->clear();
		$operation = $entityManager->find(Operation::class, $operationId);
		self::assertNotNull($operation);
		self::assertSame(100.0, $operation->getNumber());
		self::assertNull($operation->getComment());
		self::assertSame('undo', $operation->getLastAction());

		$this->client->xmlHttpRequest('POST', '/compte/'.$this->compte->getId().'/tables?year='.date('Y'));
		$response = json_decode((string) $this->client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);
		self::assertSame(100.0, (float) $response['solde']);
		self::assertSame(-50.0, (float) $response['soldeFinMensuel']);
		self::assertStringContainsString('100', $response['render']);
	}

	public function testLatestDeletedOperationCanBeRestored(): void
	{
		$operationId = $this->createdIds[Operation::class][0];
		$this->client->loginUser($this->owner);
		$this->client->xmlHttpRequest(
			'POST',
			sprintf(
				'/compte/operation/save/%d/%s/%s/1',
				$this->positiveSubCategory->getId(),
				date('Y'),
				date('n')
			),
			['datas' => [['id' => $operationId, 'delete' => 1]]]
		);
		self::assertResponseIsSuccessful();

		$crawler = $this->client->request('GET', '/compte/'.$this->compte->getId());
		$button = $crawler->filter('.last-actions-item')->first()->filter('.undo-last-action');
		$this->client->xmlHttpRequest(
			'POST',
			$button->attr('data-url'),
			['_token' => $button->attr('data-token')]
		);

		self::assertResponseIsSuccessful();
		$entityManager = static::getContainer()->get(EntityManagerInterface::class);
		$entityManager->clear();
		$operation = $entityManager->find(Operation::class, $operationId);
		self::assertNotNull($operation);
		self::assertTrue($operation->isActif());
		self::assertSame('undo', $operation->getLastAction());
	}

	public function testUndoRejectsInvalidCsrfToken(): void
	{
		$this->client->loginUser($this->owner);
		$crawler = $this->client->request('GET', '/compte/'.$this->compte->getId());
		$button = $crawler->filter('.undo-last-action')->first();
		$this->client->xmlHttpRequest(
			'POST',
			$button->attr('data-url'),
			['_token' => 'invalid']
		);

		self::assertResponseStatusCodeSame(403);

		$this->client->xmlHttpRequest(
			'POST',
			'/compte/'.$this->compte->getId().'/anomaly/'.$this->createdIds[Operation::class][1].'/resolve',
			['_token' => 'invalid']
		);
		self::assertResponseStatusCodeSame(403);

		$this->client->xmlHttpRequest(
			'POST',
			'/user/preference/'.$this->owner->getId().'/account-tutorial-seen',
			['_token' => 'invalid']
		);
		self::assertResponseStatusCodeSame(403);
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

		$this->client->xmlHttpRequest('POST', '/compte/'.$this->compte->getId().'/operation/action/1/undo');
		self::assertResponseStatusCodeSame(403);

		$this->client->xmlHttpRequest('POST', '/compte/'.$this->compte->getId().'/anomaly/'.$this->createdIds[Operation::class][0].'/resolve');
		self::assertResponseStatusCodeSame(403);

		$this->client->xmlHttpRequest('POST', '/compte/'.$this->compte->getId().'/categories/reorder');
		self::assertResponseStatusCodeSame(403);

		$this->client->xmlHttpRequest('POST', '/compte/'.$this->compte->getId().'/subcategories/reorder');
		self::assertResponseStatusCodeSame(403);

		$this->client->xmlHttpRequest('POST', '/user/preference/'.$this->owner->getId().'/account-tutorial-seen');
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

		$this->client->request('GET', '/compte/'.$this->compte->getId().'/operation/action/1/undo');
		self::assertResponseStatusCodeSame(405);

		$this->client->request('GET', '/compte/'.$this->compte->getId().'/anomaly/1/resolve');
		self::assertResponseStatusCodeSame(405);

		$this->client->request('GET', '/compte/'.$this->compte->getId().'/categories/reorder');
		self::assertResponseStatusCodeSame(405);

		$this->client->request('GET', '/compte/'.$this->compte->getId().'/subcategories/reorder');
		self::assertResponseStatusCodeSame(405);

		$this->client->request('GET', '/user/preference/'.$this->owner->getId().'/account-tutorial-seen');
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

		$this->client->xmlHttpRequest(
			'POST',
			'/compte/'.$this->compte->getId().'/anomaly/'.$foreignOperation->getId().'/resolve',
			['_token' => 'not-needed-for-a-foreign-operation']
		);
		self::assertResponseStatusCodeSame(404);
	}

	private function createUser(string $userName): User
	{
		$user = (new User())
			->setUserName($userName)
			->setPassword('test-password')
			->setRoles(['ROLE_USER'])
		;
		$user->setPreferences(
			(new UserPreference())
				->setCompteGenreShow(true)
				->setTablePalette('classic')
				->setShowEditableBorder(true)
		);

		return $user;
	}

	private function createCategory(Compte $compte, string $label, bool $sign, int $year): Category
	{
		$category = (new Category())
			->setLibelle($label)
			->setSign($sign)
			->setPosition(1)
			->setYear($year)
		;
		$compte->addCategory($category);

		return $category;
	}

	private function createSubCategory(Category $category, string $label): SubCategory
	{
		$subCategory = (new SubCategory())
			->setLibelle($label)
			->setPosition(1)
		;
		$category->addSubCategory($subCategory);

		return $subCategory;
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

	private function createOperationAction(Operation $operation): OperationAction
	{
		$snapshot = [
			'number' => $operation->getNumber(),
			'anticipe' => $operation->isAnticipe(),
			'date' => $operation->getDate()->format(DATE_ATOM),
			'comment' => $operation->getComment(),
			'actif' => $operation->isActif(),
			'lastAction' => $operation->getLastAction(),
			'dateLastAction' => $operation->getDateLastAction()->format(DATE_ATOM),
		];

		return (new OperationAction())
			->setOperation($operation)
			->setActionType($operation->getLastAction())
			->setActionAt(clone $operation->getDateLastAction())
			->setAfterSnapshot($snapshot)
		;
	}
}
