<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Compte;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompteType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
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
					'label' => "Utilisateur",
					// 'query_builder' => function(UserRepository $e){
					// 	return $e->createQueryBuilder('e')
					// 		->orderBy('e.id', 'ASC')
					// 		->where('e.roles LIKE :role')
					// 		->setParameter('role', '%ROLE_ADMIN%')
					// 	;
					// },
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
