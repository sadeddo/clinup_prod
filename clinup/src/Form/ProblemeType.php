<?php

namespace App\Form;

use App\Entity\Logement;
use App\Entity\Probleme;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ProblemeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('titre', TextType::class, [
            'attr' => [
                'class' => 'form-control',
                'placeholder' => 'Entrez le titre du problème'
            ],
            'label' => 'Titre',
        ])
        ->add('description', TextareaType::class, [
            'attr' => [
                'class' => 'form-control',
                'placeholder' => 'Décrivez le problème en détail'
            ],
            'label' => 'Description',
        ])
        ->add('criticiter', ChoiceType::class, [
            'choices' => [
                'P1 - le logement est inutilisable' => 'P1',
                'P2 - impacte la réservation client' => 'P2',
                'P3 - pas d’incidence client' => 'P3',
            ],
            'attr' => ['class' => 'form-control'],
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Probleme::class,
        ]);
    }
}
