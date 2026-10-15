<?php

namespace App\Autocomplete;

use App\Repository\QotdRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\UX\Autocomplete\AutocompleteResults;
use Symfony\UX\Autocomplete\AutocompleterInterface;

#[AutoconfigureTag('ux.autocompleter', ['alias' => self::ALIAS])]
final readonly class AuthorAutocompleter implements AutocompleterInterface
{
    public const string ALIAS = 'qotd_author';

    private const int MAX_RESULTS = 10;

    public function __construct(
        private QotdRepository $qotdRepository,
    ) {
    }

    public function fetchResults(string $query, int $page): AutocompleteResults
    {
        $authors = $this->qotdRepository->searchAuthors(
            $query,
            limit: self::MAX_RESULTS + 1,
            offset: (max(1, $page) - 1) * self::MAX_RESULTS,
        );

        return new AutocompleteResults(
            array_map(
                static fn (string $author): array => ['value' => $author, 'text' => $author],
                \array_slice($authors, 0, self::MAX_RESULTS),
            ),
            hasNextPage: \count($authors) > self::MAX_RESULTS,
        );
    }

    public function isGranted(Security $security): bool
    {
        return $security->isGranted('ROLE_USER');
    }
}
