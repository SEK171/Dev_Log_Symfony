<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $userChoices = [];
        foreach ($options['users'] as $user) {
            $userChoices[$user->getUsername()] = $user->getId();
        }

        $builder
            ->add('log_id', HiddenType::class, [
                'attr' => ['id' => 'logId']
            ])
            ->add('assigned_to', ChoiceType::class, [
                'choices' => $userChoices,
                'placeholder' => 'Select User',
                'required' => true,
                'label' => 'Assign to:',
                'attr' => ['class' => 'form-select']
            ])
            ->add('description', TextareaType::class, [
                'required' => true,
                'label' => 'Task Description:',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Assign Task',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    // don't forget the token here for authentication
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'users' => [],
            'csrf_token_id' => 'task_assign',
        ]);

        $resolver->setAllowedTypes('users', 'array');
    }
}