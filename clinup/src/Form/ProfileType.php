<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
    ->add('email', EmailType::class, [
        'attr' => ['class' => 'form-control', 'placeholder' => 'Votre email'],
    ])
    ->add('picture',FileType::class, [
        "data_class" => null,
        ])
    ->add('firstname', TextType::class, [
        'attr' => ['class' => 'form-control', 'placeholder' => 'Votre prénom'],
    ])
    ->add('lastname', TextType::class, [
        'attr' => ['class' => 'form-control', 'placeholder' => 'Votre nom'],
    ])
    ->add('birthday', DateType::class, [
        'widget' => 'single_text',
        'attr' => ['class' => 'form-control', 'placeholder' => 'Date de naissance'],
        'required' => false,
    ])
    ->add('gender', ChoiceType::class, [
        'choices' => [
            'Homme' => 'male',
            'Femme' => 'female',
        ],
        'attr' => ['class' => 'form-control', 'placeholder' => 'Sélectionnez votre sexe'],
        'required' => false,
        'placeholder' => 'Sélectionnez', // Placeholder pour la sélection
    ])
    ->add('phoneNumber', TelType::class, [
        'attr' => ['class' => 'form-control', 'placeholder' => 'Numéro de téléphone'],
        'required' => false,
    ])
    ->add('adresse', TextType::class, [
        'attr' => ['class' => 'form-control', 'placeholder' => 'Adresse'],
        'required' => false,
    ])
    ->add('description', TextareaType::class, [
        'attr' => ['class' => 'form-control', 'placeholder' => 'Description'],
        'required' => false,
    ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
