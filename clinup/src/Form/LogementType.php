<?php

namespace App\Form;

use App\Entity\Logement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class LogementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
    ->add('nom', TextType::class, [
        'attr' => ['class' => 'form-control', 'placeholder' => 'Entrez le nom du logement'],
    ])
    ->add('adresse', TextType::class, [
        'attr' => ['class' => 'form-control', 'placeholder' => 'Numéro et rue'],
    ])
    ->add('completAdresse', TextType::class, [
        'attr' => ['class' => 'form-control', 'placeholder' => 'Appartement, étage, etc.'],
        'required' => false,
    ])
    ->add('surface', TextType::class, [
        'attr' => ['class' => 'form-control', 'placeholder' => 'Surface en mètres carrés'],
    ])
    ->add('nbrChambre', NumberType::class, [
        'attr' => ['class' => 'form-control', 'placeholder' => 'Nombre de chambres'],
    ])
    ->add('nbrBain', NumberType::class, [
        'attr' => ['class' => 'form-control', 'placeholder' => 'Nombre de salles de bain'],
    ])
    ->add('description', TextareaType::class, [
        'attr' => ['class' => 'form-control', 'placeholder' => 'Décrivez le logement, ses atouts, etc.'],
        'required' => false,
    ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Logement::class,
        ]);
    }
}
