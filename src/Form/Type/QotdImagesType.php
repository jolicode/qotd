<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\All;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\UX\Dropzone\Form\DropzoneType;

class QotdImagesType extends AbstractType
{
    private const int MAX_FILES = 5;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('images', DropzoneType::class, [
                'attr' => ['accept' => 'image/*'],
                'constraints' => [new All([new Image(maxSize: '8M')])],
                'label' => false,
                'max_files' => self::MAX_FILES,
                'multiple' => true,
                'placeholder' => \sprintf('Drop up to %d images here, or browse', self::MAX_FILES),
            ])
        ;
    }
}
