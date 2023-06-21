<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Compte;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
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
				'libelle',
				TextType::class,
				[
					'label' => "Libellé du compte",
					'attr' => [
						'class' => 'form-control',
					],

				]
			)
			->add(
				'users',
				EntityType::class,
				[
					'class' => User::class,
					'choice_label' => 'userName',
					'required' => true,
					'expanded' => true,
					'multiple' => true,
					'attr' => [
						'class' => 'form-control',
					],
					'label' => "Ajouter d'autres gérants pour ce compte",
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
				'main',
				CheckboxType::class,
				[
					'label' => "S'agit-il de votre compte principal ?",
					'attr' => [
						'class' => 'form-check-input',
					],
					'label_attr' => [
						'class' => 'form-check-label',
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
