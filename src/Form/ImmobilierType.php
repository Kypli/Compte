<?php

namespace App\Form;

use App\Entity\Immobilier;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImmobilierType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add('libelle', TextType::class, [
				'label' => 'Nom',
				'attr' => ['placeholder' => 'Maison, appartement, terrain...'],
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

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => Immobilier::class,
		]);
	}
}
