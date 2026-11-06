<?php

namespace App\Form\Type;

use App\Repository\QotdRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Form\AsAutocompleteField;
use Symfony\UX\Autocomplete\Form\AutocompleteChoiceType;

#[AsAutocompleteField(alias: 'qotd_author')]
final class AuthorAutocompleteField extends AbstractType
{
    public function __construct(
        private readonly QotdRepository $qotdRepository,
        private readonly Security $security,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $authors = $this->qotdRepository->getAuthors();
        $user = $this->security->getUser() ?? throw new \LogicException('The favorite authors require a user.');

        $resolver->setDefaults([
            'attr' => ['placeholder' => 'Any'],
            'choices' => array_combine($authors, $authors),
            'multiple' => true,
            'preferred_choices' => $this->qotdRepository->getFavoriteAuthors($user),
            'required' => false,
            'security' => 'ROLE_USER',
        ]);
    }

    public function getParent(): string
    {
        return AutocompleteChoiceType::class;
    }
}
