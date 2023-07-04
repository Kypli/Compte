<?php

namespace App\Form;

use App\Entity\User;

use App\Form\UserProfilType;
use App\Form\UserPreferenceType;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder
			->add(
				'userName',
				TextType::class,
				[
					'required' => true,
					'label' => 'Pseudo',
					'attr' => [
						'class' => 'form-control',
						'autocomplete' => 'off',
					],
				]
			)
			->add(
				'password',
				PasswordType::class,
				[
					'required' => $options['data']->getId() == null ? true : false,
					'label' => 'Mot de passe',
					'attr' => [
						'class' => 'form-control',
					],
				]
			)
			->add(
				'profil',
				UserProfilType::class
			)
			->add(
				'admin',
				CheckboxType::class,
				[
					'label' => 'Admin',
					'required' => false,
					'data'   => $options['data']->isAdmin(),
					'attr' => [
						'class' => 'checkType',
					],
					'mapped' => false,
				]
			)
			->add(
				'anonyme',
				CheckboxType::class,
				[
					'label' => 'Anonyme',
					'required' => false,
					'attr' => [
						'class' => 'checkType',
					],
				]
			)
			->add(
				'ip',
				TextType::class,
				[
					'label' => 'Adresse IP',
					'required' => false,
					'empty_data' => '',
					'attr' => [
						'class' => 'form-control',
					],
				]
			)
			->add(
				'preferences',
				UserPreferenceType::class
			)
		;
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => User::class,
		]);
	}
}
