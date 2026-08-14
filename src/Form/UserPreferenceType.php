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
