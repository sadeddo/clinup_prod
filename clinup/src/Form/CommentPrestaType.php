<?php

namespace App\Form;

use App\Entity\CommentPresta;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class CommentPrestaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('comment', TextareaType::class,[
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('recommandation', ChoiceType::class, [
                'choices' => [
                    'Oui' => 'Oui',
                    'Non' => 'Non',
                ],
                'attr' => ['class' => 'form-control'],
                'required' => false,
            ])
            ->add('evaluation')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CommentPresta::class,
        ]);
    }
}
