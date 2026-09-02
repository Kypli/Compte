<?php

namespace App\Form;

use App\Entity\Credit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\PositiveOrZero;
use Symfony\Component\Validator\Constraints\Range;

class CreditType extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$moneyOptions = [
			'scale' => 2,
			'html5' => true,
			'attr' => ['step' => '0.01', 'min' => '0'],
			'constraints' => [new PositiveOrZero()],
		];

		$builder
			->add('libelle', TextType::class, [
				'label' => 'Nom du crédit',
				'attr' => ['placeholder' => 'Prêt résidence principale...'],
				'constraints' => [new NotBlank(), new Length(max: 160)],
			])
			->add('organisme', TextType::class, [
				'label' => 'Organisme prêteur',
				'required' => false,
				'attr' => ['placeholder' => 'Banque ou organisme'],
				'constraints' => [new Length(max: 120)],
			])
			->add('type', ChoiceType::class, [
				'label' => 'Type de crédit',
				'choices' => [
					'Immobilier' => 'immobilier',
					'Automobile' => 'automobile',
					'Personnel' => 'personnel',
					'Travaux' => 'travaux',
					'Renouvelable' => 'renouvelable',
					'Étudiant' => 'etudiant',
					'Autre' => 'autre',
				],
			])
			->add('montantInitial', NumberType::class, array_replace_recursive($moneyOptions, [
				'label' => 'Montant emprunté',
				'attr' => ['placeholder' => '0,00'],
			]))
			->add('capitalRestant', NumberType::class, array_replace_recursive($moneyOptions, [
				'label' => 'Capital restant dû',
				'attr' => ['placeholder' => '0,00'],
			]))
			->add('mensualite', NumberType::class, array_replace_recursive($moneyOptions, [
				'label' => 'Mensualité hors assurance',
				'attr' => ['placeholder' => '0,00'],
			]))
			->add('assuranceMensuelle', NumberType::class, array_replace_recursive($moneyOptions, [
				'label' => 'Assurance mensuelle',
				'required' => false,
				'attr' => ['placeholder' => '0,00'],
			]))
			->add('tauxAnnuel', NumberType::class, [
				'label' => 'Taux annuel',
				'required' => false,
				'scale' => 3,
				'html5' => true,
				'attr' => ['step' => '0.001', 'min' => '0', 'max' => '100', 'placeholder' => '0,000'],
				'constraints' => [new Range(min: 0, max: 100)],
			])
			->add('dateDebut', DateType::class, [
				'label' => 'Date de début',
				'required' => false,
				'widget' => 'single_text',
			])
			->add('dateFin', DateType::class, [
				'label' => 'Date de fin prévue',
				'required' => false,
				'widget' => 'single_text',
			])
			->add('actif', CheckboxType::class, [
				'label' => false,
				'required' => false,
			])
			->add('notes', TextareaType::class, [
				'label' => 'Notes',
				'required' => false,
				'attr' => ['rows' => 4, 'placeholder' => 'Référence du contrat, modulation, remboursement anticipé...'],
			])
		;
	}

	public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => Credit::class,
		]);
	}
}
