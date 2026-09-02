<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Compte;
use App\Entity\CompteType as CompteTypeEnt;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;

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
						'class' => 'form-control form-select',
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
					'empty_data' => '',
					'constraints' => [
						new NotBlank(message: 'Le libellé du compte est obligatoire.'),
						new Length(
							max: 35,
							maxMessage: 'Le libellé ne peut pas dépasser {{ limit }} caractères.',
						),
					],
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
					'empty_data' => '0',
					'label' => 'Montant du découvert autorisé',
					'constraints' => [
						new PositiveOrZero(message: 'Le découvert autorisé doit être positif ou nul.'),
					],
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
					'label' => "Ajouter une personne au compte",
					'required' => false,
					'mapped' => false,
					'attr' => [
						'class' => 'form-control',
						'placeholder' => 'Code utilisateur',
						'maxlength' => 8,
						'autocomplete' => 'off',
						'spellcheck' => 'false',
					],

				]
			)
			->add(
				'users_access',
				ChoiceType::class,
				[
					'label' => "Accès au compte",
					'required' => false,
					'mapped' => false,
					'placeholder' => false,
					'data' => 'observer',
					'expanded' => true,
					'multiple' => false,
					'choices' => [
						'Observateur' => 'observer',
						'Éditeur' => 'editor',
						'Aucun' => 'none',
					],
					'attr' => [
						'class' => 'account-sharing-access-options',
					],
				]
			)
			->add(
				'users_participant',
				CheckboxType::class,
				[
					'label' => 'Participant',
					'required' => false,
					'mapped' => false,
					'attr' => [
						'class' => 'form-check-input',
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
