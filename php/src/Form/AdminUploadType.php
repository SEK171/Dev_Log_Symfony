<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

// Admin uplosd new log file form type
class AdminUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('logfile', FileType::class, [
                'required' => true,
                'label' => false,
                'attr' => [
                    'accept' => '.log',
                    'class' => 'form-control'
                ],
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        // Remove MIME type validation to avoid requiring symfony/mime
                        'maxSizeMessage' => 'The file is too large ({{ size }} {{ suffix }}). Allowed maximum size is {{ limit }} {{ suffix }}.',
                    ])
                ]
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Upload New Log',
                'attr' => ['class' => 'btn danger']
            ]);
    }

    // don't forget the token here for authentication
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_token_id' => 'upload',
        ]);
    }
}