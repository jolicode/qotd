<?php

namespace App\Form\Type;

use App\Repository\QotdRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QotdFiltersType extends AbstractType
{
    public function __construct(
        private readonly QotdRepository $qotdRepository,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('author', ChoiceType::class, [
                'required' => false,
                'placeholder' => 'Any',
                'choices' => array_combine($authors = $this->qotdRepository->getAuthors(), $authors),
            ])
            ->add('withImage', ChoiceType::class, [
                'required' => false,
                'placeholder' => 'Any',
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
            ])
            ->add('withVideo', ChoiceType::class, [
                'required' => false,
                'placeholder' => 'Any',
                'choices' => [
                    'Yes' => true,
                    'No' => false,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'method' => 'GET',
            'csrf_protection' => false,
            'allow_extra_fields' => true,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
