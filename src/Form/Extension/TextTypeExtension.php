<?php

namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TextTypeExtension extends AbstractTypeExtension
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'empty_data' => '',
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        yield TextType::class;
    }
}
