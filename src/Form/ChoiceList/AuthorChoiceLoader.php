<?php

namespace App\Form\ChoiceList;

use App\Repository\QotdRepository;
use Symfony\Component\Form\ChoiceList\Loader\AbstractChoiceLoader;

/**
 * Meant for a "choice_lazy" field: only the submitted authors are loaded,
 * the others are searched by App\Autocomplete\AuthorAutocompleter.
 */
final class AuthorChoiceLoader extends AbstractChoiceLoader
{
    public function __construct(
        private readonly QotdRepository $qotdRepository,
    ) {
    }

    protected function loadChoices(): iterable
    {
        return $this->qotdRepository->getAuthors();
    }

    protected function doLoadChoicesForValues(array $values, ?callable $value): array
    {
        return $this->qotdRepository->filterAuthors($values);
    }
}
