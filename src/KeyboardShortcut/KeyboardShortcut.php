<?php

namespace App\KeyboardShortcut;

/**
 * Both the help (templates/keyboard_shortcuts/index.html.twig) and the
 * Stimulus controller (assets/controllers/keyboard-shortcuts-controller.js)
 * are built from this list.
 *
 * Avoid "u" and "x": "u x" opens UX Inspector in dev.
 */
enum KeyboardShortcut: string
{
    case Help = 'h';
    case Search = '/';
    case GoToLatest = 'g l';
    case GoToTop = 'g t';
    case GoToFlop = 'g f';
    case GoToNotVoted = 'g n';
    case GoToRandom = 'g r';
    case GoToHallOfFame = 'g h';
    case GoToAwards = 'g a';
    case GoToStats = 'g s';
    case SelectNext = 'j';
    case SelectPrevious = 'k';
    case OpenSelected = 'o';
    case ShowSelectedDetails = 'i';
    case EditSelected = 'e';
    case VoteUpSelected = '+';
    case VoteDownSelected = '-';

    public function getDescription(): string
    {
        return match ($this) {
            self::Help => 'Show this help',
            self::Search => 'Search a QOTD',
            self::GoToLatest => 'Go to the latest QOTD',
            self::GoToTop => 'Go to the top QOTD',
            self::GoToFlop => 'Go to the worst QOTD',
            self::GoToNotVoted => 'Go to the QOTD not voted yet',
            self::GoToRandom => 'Go to a random QOTD',
            self::GoToHallOfFame => 'Go to the Hall of Fame',
            self::GoToAwards => 'Go to the awards',
            self::GoToStats => 'Go to the stats',
            self::SelectNext => 'Select the next QOTD',
            self::SelectPrevious => 'Select the previous QOTD',
            self::OpenSelected => 'Open the selected QOTD',
            self::ShowSelectedDetails => 'Show the details of the selected QOTD',
            self::EditSelected => 'Edit the selected QOTD',
            self::VoteUpSelected => 'Vote 👍 for the selected QOTD',
            self::VoteDownSelected => 'Vote 👎 for the selected QOTD',
        };
    }

    /**
     * The route the Stimulus controller visits (with the selected QOTD as "id"
     * when "withSelectedQotd"), in the "frame" Turbo Frame if any. Otherwise,
     * the controller "method" to call.
     *
     * @return array{keys: string, route?: string, withSelectedQotd?: true, frame?: string, method?: string}
     */
    public function toStimulus(): array
    {
        return ['keys' => $this->value] + match ($this) {
            self::Help => ['route' => 'keyboard_shortcuts', 'frame' => 'modal'],
            self::Search => ['route' => 'qotd_search'],
            self::GoToLatest => ['route' => 'qotd_index'],
            self::GoToTop => ['route' => 'qotd_index_top'],
            self::GoToFlop => ['route' => 'qotd_index_flop'],
            self::GoToNotVoted => ['route' => 'qotd_index_not_voted'],
            self::GoToRandom => ['route' => 'random'],
            self::GoToHallOfFame => ['route' => 'hall_of_fame'],
            self::GoToAwards => ['route' => 'awards'],
            self::GoToStats => ['route' => 'stats'],
            self::SelectNext => ['method' => 'selectNext'],
            self::SelectPrevious => ['method' => 'selectPrevious'],
            self::OpenSelected => ['route' => 'qotd_show', 'withSelectedQotd' => true],
            self::ShowSelectedDetails => ['route' => 'qotd_show_details', 'withSelectedQotd' => true, 'frame' => 'modal'],
            self::EditSelected => ['route' => 'qotd_show_edit', 'withSelectedQotd' => true, 'frame' => 'modal'],
            self::VoteUpSelected => ['method' => 'voteUp'],
            self::VoteDownSelected => ['method' => 'voteDown'],
        };
    }
}
