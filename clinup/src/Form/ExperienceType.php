<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Experience;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ExperienceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('experience', TextType::class, [
                'attr' => [
                    'class' => 'form-control', 
                    'placeholder' => 'Ton Poste'
                ]
            ])
            ->add('dtStart', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control', 
                    'placeholder' => 'Date de début'
                ]
            ])
            ->add('dtEnd', DateType::class, [
                'widget' => 'single_text',
                'attr' => [
                    'class' => 'form-control', 
                    'placeholder' => 'Date de fin'
                ]
            ])
            ->add('description', TextareaType::class, [
                'attr' => [
                    'class' => 'form-control', 
                    'placeholder' => 'Description du poste'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Experience::class,
        ]);
    }
}
