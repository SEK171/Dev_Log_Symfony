<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

// Form type for the filters in the home page
class FilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $channelChoices = [];
        foreach ($options['channels'] as $channel) {
            $channelChoices[ucfirst($channel)] = $channel;
        }

        $typeChoices = [];
        foreach ($options['types'] as $type) {
            $typeChoices[ucfirst($type)] = $type;
        }

        $builder
            ->add('channel', ChoiceType::class, [
                'choices' => $channelChoices,
                'placeholder' => 'All Channels',
                'required' => false,
                'attr' => [
                    'class' => 'form-select',
                    'id' => 'channelFilter'
                ],
                'label' => 'Channel',
                'label_attr' => ['class' => 'filter-label']
            ])
            ->add('type', ChoiceType::class, [
                'choices' => $typeChoices,
                'placeholder' => 'All Types',
                'required' => false,
                'attr' => [
                    'class' => 'form-select',
                    'id' => 'typeFilter'
                ],
                'label' => 'Type',
                'label_attr' => ['class' => 'filter-label']
            ])
            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'id' => 'startDate'
                ],
                'label' => 'Start Date',
                'label_attr' => ['class' => 'filter-label']
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'attr' => [
                    'id' => 'endDate'
                ],
                'label' => 'End Date',
                'label_attr' => ['class' => 'filter-label']
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Apply Filters',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'channels' => [],
            'types' => [],
            'method' => 'GET',
            'csrf_protection' => false, // Usually disabled for filter forms
        ]);

        $resolver->setAllowedTypes('channels', 'array');
        $resolver->setAllowedTypes('types', 'array');
    }
}