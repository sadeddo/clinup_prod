<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Postuler;
use App\Entity\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class PostulerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('comment',TextareaType::class,[
                'attr' => ['class' => 'form-control', 'placeholder' => 'Décrivez brièvement pourquoi vous êtes le candidat idéal.'],
            ])
            ->add('proposition',IntegerType::class,[
                'attr' => ['class' => 'form-control', 'placeholder' => 'Proposez un autre prix'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Postuler::class,
        ]);
    }
}
