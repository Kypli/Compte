<?php

namespace App\Form;

use App\Entity\User;

use App\Form\UserProfilType;
use App\Form\UserPreferenceType;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
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
		$isRegistration = null === $options['data']->getId() || $options['data']->getAnonyme();
		$isAnonymousRegistration = $options['data']->getAnonyme();
		$userNameAttr = [
			'class' => 'form-control',
			'autocomplete' => 'off',
		];
		$passwordAttr = [
			'class' => 'form-control',
		];

		if ($isRegistration){
			$userNameAttr['minlength'] = 5;
			$passwordAttr['minlength'] = 6;
			$passwordAttr['pattern'] = '(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{6,}';
			$passwordAttr['title'] = 'Minimum 6 caractères, avec au moins 1 majuscule, 1 minuscule et 1 chiffre.';
		}

		$builder
			->add(
				'profil',
				UserProfilType::class
			)
			->add(
				'userName',
				TextType::class,
				[
					'required' => true,
					'label' => 'Pseudo',
					'data' => $options['data']->getAnonyme() ? '' : $options['data']->getUserName(),
					'attr' => $userNameAttr,
				]
			)
			->add(
				'password',
				PasswordType::class,
				[
					'required' => $options['data']->getId() == null || $options['data']->getAnonyme(),
					'label' => 'Mot de passe',
					'attr' => $passwordAttr,
				]
			)
		;

		if ($isAnonymousRegistration){
			$builder
				->add(
					'email',
					EmailType::class,
					[
						'required' => true,
						'label' => 'Email',
						'attr' => [
							'class' => 'form-control',
							'autocomplete' => 'email',
						],
					]
				)
				->add(
					'passwordConfirm',
					PasswordType::class,
					[
						'required' => true,
						'label' => 'Confirmer le mot de passe',
						'mapped' => false,
						'attr' => $passwordAttr,
					]
				)
			;
		}

		$builder
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
				'commentaire',
				TextType::class,
				[
					'required' => false,
					'label' => 'Commentaire',
					'attr' => [
						'class' => 'form-control',
					],
				]
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
