<?php

namespace App\Form;

use App\Entity\Icalres;
use App\Entity\Logement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class IcalresType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('nbrHeure', ChoiceType::class, [
            'choices' => [
                '1h00' => '1h00',
                '1h30' => '1h30',
                '2h00' => '2h00',
                '2h30' => '2h30',
                '3h00' => '3h00',
                '3h30' => '3h30',
                '4h00' => '4h00',
                '4h30' => '4h30',
                '5h00' => '5h00',
            ],
            'placeholder' => 'Choisir la durée',
            'attr' => ['class' => 'form-control'],
        ])
            ->add('prix',TextType::class,[
                'attr' => ['class' => 'form-control'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Icalres::class,
        ]);
    }
}
