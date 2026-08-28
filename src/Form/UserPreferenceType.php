<?php

namespace App\Form;

use App\Entity\UserPreference;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserPreferenceType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add(
				'dashboardBackground',
				ChoiceType::class,
				[
					'label' => 'Fond du tableau de bord',
					'expanded' => true,
					'multiple' => false,
					'choices' => $this->backgroundChoices(),
				]
			)
			->add(
				'accountBackground',
				ChoiceType::class,
				[
					'label' => 'Fond de la page Comptes',
					'expanded' => true,
					'multiple' => false,
					'choices' => $this->backgroundChoices(),
				]
			)

			->add(
				'compteGenreShow',
				CheckboxType::class,
				[
					'required' => false,
					'label' => 'Afficher la ligne Fait / A venir ?',
					'attr' => [
						'class' => 'checkType',
					],
				]
			)
			->add(
				'showEditableBorder',
				CheckboxType::class,
				[
					'required' => false,
					'label' => 'Afficher la bordure jaune des zones modifiables ?',
					'attr' => [
						'class' => 'checkType',
					],
				]
			)
			->add(
				'anchorTableTotals',
				CheckboxType::class,
				[
					'required' => false,
					'label' => 'Garder les totaux visibles en bas de page',
					'attr' => [
						'class' => 'checkType',
					],
				]
			)
			->add(
				'showTableTotals',
				CheckboxType::class,
				[
					'required' => false,
					'label' => 'Total',
				]
			)
			->add(
				'showTableMonthlyAverage',
				CheckboxType::class,
				[
					'required' => false,
					'label' => 'X devise / mois',
				]
			)
			->add(
				'showTablePercentage',
				CheckboxType::class,
				[
					'required' => false,
					'label' => '%',
				]
			)
			->add(
				'showBalanceTable',
				CheckboxType::class,
				[
					'required' => false,
					'label' => 'Gain mensuel',
				]
			)
			->add(
				'showBalanceCumulative',
				CheckboxType::class,
				[
					'required' => false,
					'label' => 'Solde mensuel cumulé',
				]
			)
			->add(
				'showAnnualGain',
				CheckboxType::class,
				[
					'required' => false,
					'label' => 'Gain annuel',
				]
			)
			->add(
				'showSubCategories',
				CheckboxType::class,
				[
					'required' => false,
					'label' => 'Afficher les sous-catégories',
				]
			)
			->add(
				'mergeIncomeExpenseTables',
				CheckboxType::class,
				[
					'required' => false,
					'label' => 'Fusionner les tableaux recettes et dépenses',
				]
			)
			->add(
				'tablePalette',
				ChoiceType::class,
				[
					'label' => 'Couleurs du tableau',
					'expanded' => true,
					'multiple' => false,
					'choices' => [
						'Classique' => 'classic',
						'Douce' => 'soft',
						'Contraste' => 'contrast',
						'Lagon' => 'lagoon',
						'Fruits rouges' => 'berry',
						'Cuivre' => 'copper',
					],
				]
			)
			->add(
				'moneyDisplayFormat',
				ChoiceType::class,
				[
					'label' => 'Format monétaire',
					'expanded' => true,
					'multiple' => false,
					'choices' => [
						'1 234.56' => 'dot',
						'1 234,56' => 'comma',
						'1 234€56' => 'euro_cents',
						'1.234,56' => 'german',
					],
				]
			)
			->add(
				'moneyCurrency',
				ChoiceType::class,
				[
					'label' => 'Monnaie utilisée',
					'expanded' => true,
					'multiple' => false,
					'choices' => [
						'Euro (€)' => 'EUR',
						'Dollar ($)' => 'USD',
						'Livre sterling (£)' => 'GBP',
						'Franc suisse (CHF)' => 'CHF',
						'Yen (¥)' => 'JPY',
						'Dollar canadien (CA$)' => 'CAD',
					],
				]
			)
			->add(
				'moneyTrimZeros',
				CheckboxType::class,
				[
					'required' => false,
					'label' => 'Retirer les 0 inutiles',
					'attr' => [
						'class' => 'checkType',
					],
				]
			)
			->add(
				'moneyShowZeroDecimals',
				ChoiceType::class,
				[
					'label' => 'Afficher 0 ou 0.00',
					'expanded' => true,
					'multiple' => false,
					'choices' => [
						'0' => false,
						'0.00' => true,
					],
				]
			)
		;
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => UserPreference::class,
		]);
	}

	private function backgroundChoices(): array
	{
		return [
			'Vert doux' => 'green',
			'Clair' => 'light',
			'Gris doux' => 'grey',
		];
	}
}
