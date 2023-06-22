<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Compte;
use App\Entity\CompteType as CompteTypeEnt;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompteType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add(
				'type',
				EntityType::class,
				[
					'class' => CompteTypeEnt::class,
					'choice_label' => 'libelle',
					'required' => true,
					'expanded' => false,
					'multiple' => false,
					'attr' => [
						'class' => 'form-control',
					],
					'label' => "Type de compte",
					// 'query_builder' => function(UserRepository $e){
					// 	return $e->createQueryBuilder('e')
					// 		->orderBy('e.id', 'ASC')
					// 		->where('e.roles LIKE :role')
					// 		->setParameter('role', '%ROLE_ADMIN%')
					// 	;
					// },
				]
			)
			->add(
				'libelle',
				TextType::class,
				[
					'label' => "Libellé du compte",
					'required' => true,
					'attr' => [
						'class' => 'form-control',
						'placeholder' => 'Nommer votre compte ici',
					],

				]
			)
			->add(
				'main',
				CheckboxType::class,
				[
					'label' => "S'agit-il de votre compte principal ?",
					'required' => false,
					'attr' => [
						'class' => 'form-check-input',
					],
					'label_attr' => [
						'class' => 'form-check-label',
					],

				]
			)
			->add(
				'decouvert',
				IntegerType::class,
				[
					'required' => false,
					'empty_data' => null,
					'label' => 'Montant du découvert autorisé',
					'attr' => [
						'class' => 'form-control',
						'min' => 0,
						'step'=> 1,
					],
				]
			)
			->add(
				'users_code',
				TextType::class,
				[
					'label' => "Ajouter d'autres gérants* pour ce compte",
					'required' => false,
					'mapped' => false,
					'attr' => [
						'class' => 'form-control',
						'placeholder' => 'Insérer le code utilisateur du gérant ici',
					],

				]
			)
		;
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => Compte::class,
		]);
	}
}
