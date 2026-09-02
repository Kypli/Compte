<?php

namespace App\Form;

use App\Entity\Mobilier;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MobilierType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('libelle', TextType::class, [
				'label' => 'Nom',
				'attr' => ['placeholder' => 'Voiture, meuble, bijou, matériel...'],
			])
			->add('valeur', NumberType::class, [
				'label' => 'Valeur estimée',
				'scale' => 2,
				'html5' => true,
				'attr' => ['step' => '0.01', 'min' => '0', 'placeholder' => '0'],
			])
			->add('categorie', TextType::class, [
				'label' => 'Catégorie',
				'required' => false,
				'attr' => ['placeholder' => 'Véhicule, mobilier, objet de valeur...'],
			])
			->add('description', TextareaType::class, [
				'label' => 'Notes',
				'required' => false,
				'attr' => ['rows' => 3, 'placeholder' => 'Etat, estimation, détails utiles...'],
			])
		;
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => Mobilier::class,
		]);
	}
}
