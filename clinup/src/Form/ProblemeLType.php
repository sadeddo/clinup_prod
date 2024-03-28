<?php

namespace App\Form;

use App\Entity\Logement;
use App\Entity\Probleme;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ProblemeLType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];

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
            ->add('criticiter', ChoiceType::class, [
                'choices' => [
                    'P1 - le logement est inutilisable' => 'P1',
                    'P2 - impacte la réservation client' => 'P2',
                    'P3 - pas d’incidence client' => 'P3',
                ],
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Probleme::class,
            'user' => null
        ]);
    }
}
