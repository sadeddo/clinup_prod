<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\Invit;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class InvitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Adresse e-mail'],
            ])
            ->add('nom', TextType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Nom du prestataire'],
            ])
            ->add('message', TextareaType::class, [
                'attr' => ['class' => 'form-control', 'placeholder' => 'Message d\'invitation (facultatif)'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Invit::class,
        ]);
    }
}
