<?php

namespace App\Form;

use App\Entity\EventSeminar;
use App\Entity\Seminario;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventSeminarType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('location', TextType::class, [
                'attr' =>['class' => 'form-control']
            ])
            ->add('start_date', DateType::class, [
                'widget' => 'single_text',
                'mapped' => false,
                'input' => 'datetime',
                'data' => $options['default_date'],
                'attr' => ['class' => 'form-control'],
                'html5' => true,
            ])
            ->add('start_time', TimeType::class, [
                'widget' => 'single_text',
                'mapped' => false,
                'data' => $options['default_time'], // Default value from controller
                'attr' => ['class' => 'form-control'],
                'html5' => true,
            ])
            ->add('event_duration', ChoiceType::class, [
                'mapped' => false, // Not stored directly in the entity
                'data' => $options['default_duration'], // Default value from controller
                'choices' => [
                    '1 hour' => 1,
                    '1 hour 30 minutes' => 1.5,
                    '2 hours' => 2,
                    '2 hours 30 minutes' => 2.5,
                    '3 hours' => 3,
                    '3 hours 30 minutes' => 3.5,
                    '4 hours' => 4,
                ],
                'attr' => ['class' => 'form-control'],
                'required' => true,
                //'placeholder' => 'Select duration',
            ])
            ->add('speaker', TextType::class, [
                'attr' =>['class' => 'form-control']
            ])
            ->add('institution', TextType::class, [
                'attr' =>['class' => 'form-control']
            ])
            ->add('title', TextType::class, [
                'attr' =>['class' => 'form-control']
            ])
            ->add('abstract', TextareaType::class, [
                'attr' =>['class' => 'form-control', 'rows' => 5]
            ])
            ->add('url', TextType::class, [
                'attr' =>['class' => 'form-control']
            ])
            ->add('notes', TextareaType::class, [
                'attr' =>['class' => 'form-control', 'rows' => 5]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventSeminar::class,
            'default_date' => new \DateTime('2025-04-04'),
            'default_time' => new \DateTime('12:34'), // Set default value
            'default_duration' => '1',
        ]);
    }
}
