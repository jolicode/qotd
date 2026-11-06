<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class QotdFiltersType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('authors', AuthorAutocompleteField::class)
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

    /**
     * Tom Select searches the authors with Ajax: only the selected ones belong
     * to the HTML. Not in AuthorAutocompleteField: the Ajax endpoint reads the
     * preferred choices from the view of that field.
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $authors = $view['authors'];

        $authors->vars['choices'] = array_filter(
            $authors->vars['choices'],
            static fn (ChoiceView $choice): bool => \in_array($choice->value, $authors->vars['value'], true),
        );
        $authors->vars['preferred_choices'] = [];
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
