<?php

namespace App\Form\Type;

use App\Entity\Log;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

// The log type for the createLog form (don't forget constraints)
class LogType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('datetime', DateTimeType::class, [
                'widget' => 'single_text',
                'required' => true,
                'attr' => [
                    'class' => 'form-control'
                ],
                'label' => 'Date & Time',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a date and time'])
                ]
            ])
            ->add('channel', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'channel',
                    'list' => 'channel-list'
                ],
                'label' => 'Channel',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a channel']),
                    new Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Channel must be at least {{ limit }} characters long',
                        'maxMessage' => 'Channel cannot be longer than {{ limit }} characters'
                    ])
                ]
            ])
            ->add('type', TextType::class, [
                'required' => true,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'type',
                    'list' => 'type-list'
                ],
                'label' => 'Log Type',
                'constraints' => [
                    new NotBlank(['message' => 'Please enter a log type']),
                    new Length([
                        'min' => 2,
                        'max' => 50,
                        'minMessage' => 'Type must be at least {{ limit }} characters long',
                        'maxMessage' => 'Type cannot be longer than {{ limit }} characters'
                    ])
                ]
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Enter detailed description of the log entry...'
                ],
                'label' => 'Description',
                'constraints' => [
                    new Length([
                        'max' => 1000,
                        'maxMessage' => 'Description cannot be longer than {{ limit }} characters'
                    ])
                ]
            ]);
    }

    // don't forget the token here for authentication
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Log::class,
            'csrf_token_id' => 'api_logs_create',
        ]);
    }
}