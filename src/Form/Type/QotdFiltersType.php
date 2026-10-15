<?php

namespace App\Form\Type;

use App\Autocomplete\AuthorAutocompleter;
use App\Form\ChoiceList\AuthorChoiceLoader;
use App\Repository\QotdRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class QotdFiltersType extends AbstractType
{
    public function __construct(
        private readonly QotdRepository $qotdRepository,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('author', ChoiceType::class, [
                'required' => false,
                'placeholder' => 'Any',
                'choice_loader' => new AuthorChoiceLoader($this->qotdRepository),
                'choice_lazy' => true,
                'choice_label' => static fn (string $author): string => $author,
                'choice_value' => static fn (?string $author): ?string => $author,
                'autocomplete' => true,
                'autocomplete_url' => $this->urlGenerator->generate('ux_autocomplete', ['alias' => AuthorAutocompleter::ALIAS]),
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
