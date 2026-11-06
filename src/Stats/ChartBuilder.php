<?php

namespace App\Stats;

use App\Repository\QotdRepository;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class ChartBuilder
{
    private const string GRID_COLOR = 'rgba(0, 0, 0, 0.08)';
    private const string SERIES_AREA_COLOR = 'rgba(42, 120, 214, 0.12)';
    private const string SERIES_COLOR = '#2a78d6';
    private const string SERIES_HOVER_COLOR = '#1f5fb3';
    private const string TEXT_COLOR = '#52514e';

    private const array TOOLTIP = [
        'backgroundColor' => '#212529',
        'cornerRadius' => 4,
        'displayColors' => false,
        'padding' => 8,
    ];

    public function __construct(
        private readonly QotdRepository $qotdRepository,
        private readonly ChartBuilderInterface $chartBuilder,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function buildUsersChart(UsersChartType $type): Chart
    {
        return match ($type) {
            UsersChartType::MostQuoted => $this->createRankingChart($this->qotdRepository->countMostQuotedUsers(), 'count'),
            UsersChartType::MostUpVoted => $this->createRankingChart($this->qotdRepository->countMostUpVotedUsers(), 'vote'),
            UsersChartType::BiggestVoting => $this->createRankingChart($this->qotdRepository->countBiggestVotingUsers(), 'vote'),
        };
    }

    /**
     * One chart by measure: on two axes, the alignment of the scales would be arbitrary.
     *
     * @return list<Chart>
     */
    public function buildCountOverPeriod(StatsPeriod $period): array
    {
        $counts = $this->qotdRepository->countOver($period->value);
        $formatter = new \IntlDateFormatter($this->translator->getLocale(), pattern: $period->getDatePattern());
        $labels = array_map(static fn (array $item): string => (string) $formatter->format($item['period']), $counts);

        return [
            $this->createTrendChart($this->translator->trans('Number of QOTD'), $labels, array_column($counts, 'count')),
            $this->createTrendChart($this->translator->trans('Number of votes'), $labels, array_column($counts, 'vote')),
        ];
    }

    /**
     * @param list<array<string, mixed>> $counts
     */
    private function createRankingChart(array $counts, string $measure): Chart
    {
        $usernames = array_column($counts, 'username');

        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);
        $chart->setData([
            'labels' => array_map(static fn (string $username): string => strstr($username, '@', true) ?: $username, $usernames),
            'datasets' => [
                [
                    'backgroundColor' => self::SERIES_COLOR,
                    'borderRadius' => 4,
                    'borderSkipped' => 'start',
                    'data' => array_column($counts, $measure),
                    'hoverBackgroundColor' => self::SERIES_HOVER_COLOR,
                    'maxBarThickness' => 20,
                    // The labels are shortened: see assets/controllers/chart-author-filter-controller.js
                    'usernames' => $usernames,
                ],
            ],
        ]);
        $chart->setOptions([
            'aspectRatio' => 1.2,
            'indexAxis' => 'y',
            'interaction' => ['axis' => 'y', 'intersect' => false, 'mode' => 'nearest'],
            'locale' => $this->translator->getLocale(),
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => self::TOOLTIP,
            ],
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                    'border' => ['display' => false],
                    'grid' => ['color' => self::GRID_COLOR],
                    'ticks' => ['color' => self::TEXT_COLOR, 'precision' => 0],
                ],
                'y' => [
                    'border' => ['display' => false],
                    'grid' => ['display' => false],
                    'ticks' => ['autoSkip' => false, 'color' => self::TEXT_COLOR],
                ],
            ],
        ]);

        return $chart;
    }

    /**
     * @param list<string> $labels
     * @param list<int>    $values
     */
    private function createTrendChart(string $title, array $labels, array $values): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'backgroundColor' => self::SERIES_AREA_COLOR,
                    'borderColor' => self::SERIES_COLOR,
                    'borderWidth' => 2,
                    // Unlike "tension", it never draws a curve above the real values
                    'cubicInterpolationMode' => 'monotone',
                    'data' => $values,
                    'fill' => true,
                    'label' => $title,
                    'pointHoverRadius' => 4,
                    'pointRadius' => 0,
                ],
            ],
        ]);
        $chart->setOptions([
            'aspectRatio' => 1.8,
            'interaction' => ['intersect' => false, 'mode' => 'index'],
            'locale' => $this->translator->getLocale(),
            'plugins' => [
                'legend' => ['display' => false],
                'title' => [
                    'align' => 'start',
                    'color' => self::TEXT_COLOR,
                    'display' => true,
                    'font' => ['size' => 14, 'weight' => 'normal'],
                    'text' => $title,
                ],
                'tooltip' => self::TOOLTIP,
            ],
            'scales' => [
                'x' => [
                    'border' => ['display' => false],
                    'grid' => ['display' => false],
                    'ticks' => ['autoSkipPadding' => 24, 'color' => self::TEXT_COLOR, 'maxRotation' => 0],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'border' => ['display' => false],
                    'grid' => ['color' => self::GRID_COLOR],
                    'ticks' => ['color' => self::TEXT_COLOR, 'precision' => 0],
                ],
            ],
        ]);

        return $chart;
    }
}
