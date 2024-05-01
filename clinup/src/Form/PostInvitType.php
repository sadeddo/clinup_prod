<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Postuler;
use App\Entity\Reservation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class PostInvitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('comment', ChoiceType::class, [
                'choices' => [
                    'Je suis disponible' => 'Je suis disponible',
                    'Je ne suis pas disponible' => 'Je ne suis pas disponible',
                ],
                'attr' => ['class' => 'form-control', 'placeholder' => 'Sélectionnez une réponse'],
                'required' => false,
                'placeholder' => 'Sélectionnez', // Placeholder pour la sélection
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
