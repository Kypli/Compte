<?php

namespace App\Form;

use App\Entity\Immobilier;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImmobilierType extends AbstractType
{
	private const LIBELLE_CHOICES = [
		'Résidentiel' => [
			'Résidence principale' => 'Résidence principale',
			'Résidence secondaire' => 'Résidence secondaire',
			'Maison' => 'Maison',
			'Appartement' => 'Appartement',
			'Studio' => 'Studio',
		],
		'Investissement locatif' => [
			'Appartement locatif' => 'Appartement locatif',
			'Maison locative' => 'Maison locative',
			'Immeuble de rapport' => 'Immeuble de rapport',
			'Local commercial loué' => 'Local commercial loué',
			'Location saisonnière' => 'Location saisonnière',
		],
		'Terrains' => [
			'Terrain constructible' => 'Terrain constructible',
			'Terrain agricole' => 'Terrain agricole',
			'Terrain de loisir' => 'Terrain de loisir',
			'Parcelle boisée' => 'Parcelle boisée',
		],
		'Annexes' => [
			'Garage' => 'Garage',
			'Parking' => 'Parking',
			'Cave' => 'Cave',
			'Dépendance' => 'Dépendance',
		],
		'Professionnel' => [
			'Bureau' => 'Bureau',
			'Local professionnel' => 'Local professionnel',
			'Local commercial' => 'Local commercial',
			'Entrepôt' => 'Entrepôt',
		],
	];

	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$immobilier = $builder->getData();
		$libelleChoices = self::LIBELLE_CHOICES;
		$currentLibelle = $immobilier instanceof Immobilier ? $immobilier->getLibelle() : null;

		if ($currentLibelle !== null && $currentLibelle !== '' && !in_array($currentLibelle, $this->flattenChoices($libelleChoices), true)) {
			$libelleChoices = ['Valeur actuelle' => [$currentLibelle => $currentLibelle]] + $libelleChoices;
		}

		$builder
			->add('libelle', ChoiceType::class, [
				'label' => 'Nom',
				'placeholder' => 'Choisir un type de bien',
				'choices' => $libelleChoices,
				'choice_translation_domain' => false,
			])
			->add('valeur', NumberType::class, [
				'label' => 'Valeur estimée',
				'scale' => 2,
				'html5' => true,
				'attr' => ['step' => '0.01', 'min' => '0', 'placeholder' => '0'],
			])
			->add('adresse', TextType::class, [
				'label' => 'Adresse',
				'required' => false,
				'attr' => ['placeholder' => 'Adresse ou ville'],
			])
			->add('surface', NumberType::class, [
				'label' => 'Surface',
				'required' => false,
				'scale' => 2,
				'html5' => true,
				'attr' => ['step' => '0.01', 'min' => '0', 'placeholder' => 'm²'],
			])
			->add('description', TextareaType::class, [
				'label' => 'Notes',
				'required' => false,
				'attr' => ['rows' => 3, 'placeholder' => 'Etat, estimation, détails utiles...'],
			])
		;
	}

	private function flattenChoices(array $choices): array
	{
		$values = [];

		foreach ($choices as $choice) {
			if (is_array($choice)) {
				$values = array_merge($values, $this->flattenChoices($choice));
				continue;
			}

			$values[] = $choice;
		}

		return $values;
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => Immobilier::class,
		]);
	}
}
