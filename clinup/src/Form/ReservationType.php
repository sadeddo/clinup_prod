<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Logement;
use App\Entity\Reservation;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ReservationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];

        $builder
        ->add('date', DateType::class, [
            'widget' => 'single_text', // Utilisez un widget de type 'single_text' pour une saisie facile
            'attr' => ['class' => 'form-control'],
        ])
        ->add('heure', TimeType::class, [
            'widget' => 'single_text',
            'attr' => ['class' => 'form-control'],
        ])
        ->add('nbrHeure', ChoiceType::class, [
            'choices' => [
                '1h30' => '1h30',
                '1h30' => '1h30',
                '2h00' => '2h00',
                '2h30' => '2h30',
                '2h30' => '2h30',
            ],
            'attr' => ['class' => 'form-control',],
        ])
        ->add('logement', EntityType::class, [
            'class' => Logement::class,
            'query_builder' => function (EntityRepository $er) use ($user) {
                return $er->createQueryBuilder('l')
                    ->where('l.hote = :hote')
                    ->setParameter('hote', $user);
            },
            'choice_label' => 'nom',
            'attr' => ['class' => 'form-control',
            'placeholder' => 'Sélectionner un logement'
        ],
        ])
        ->add('description', TextareaType::class, [
            'attr' => ['class' => 'form-control',
            'placeholder' => 'Des détails particuliers à partager etc...'],
            'required' => false

        ]);
}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Reservation::class,
            'user' => null
        ]);
    }
}
