<?php

namespace App\Form;

use App\Entity\Dispo;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TimeType;

class DispoType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('dtStart', TimeType::class,[
            'input' => 'string',
            'widget' => 'choice',
            'attr' => ['class' => 'form-control'],
        ])
        ->add('dtEnd', TimeType::class,[
            'input' => 'string',
            'widget' => 'choice',
                'attr' => ['class' => 'form-control'],
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Dispo::class,
        ]);
    }
}
