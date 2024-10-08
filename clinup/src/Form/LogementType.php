<?php

namespace App\Form;

use App\Entity\Logement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
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
    ->add('airbnb', TextType::class, [
        'attr' => ['class' => 'form-control', 'placeholder' => 'Entrez votre lien Ical d\'airbnb'],
        'required' => false,
    ])
    ->add('booking', TextType::class, [
        'attr' => ['class' => 'form-control', 'placeholder' => 'Entrez votre lien Ical de booking'],
        'required' => false,
    ])
    ->add('completAdresse', TextType::class, [
        'attr' => ['class' => 'form-control', 'placeholder' => 'Appartement, étage, etc.'],
        'required' => false,
    ])
    ->add('acces', TextType::class, [
        'attr' => ['class' => 'form-control', 'placeholder' => 'Accès au logement pour le prestataire'],
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
        'attr' => ['class' => 'form-control', 'placeholder' => 'Décrivez le logement de manière pour que le prestataire de ménage puisse visualiser l’appartement'],
    ])
    ->add('img',FileType::class, [
        'attr' => ['class' => 'form-control'],
        "mapped" => false,
        "required" => false
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Logement::class,
        ]);
    }
}
