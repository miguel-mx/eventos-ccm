<?php

namespace App\Form;

use App\Entity\Organizer;
use App\Entity\Seminario;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrganizerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('email')
            ->add('institution')
            ->add('seminario', EntityType::class, [
                'class' => Seminario::class,
                'choice_label' => 'name', // 👈 Displays seminar name instead of ID
                'placeholder' => 'Select a Seminar', // 👈 Optional: Default dropdown text
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Organizer::class,
        ]);
    }
}
