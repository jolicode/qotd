<?php

namespace App\Stats;

use App\Repository\QotdRepository;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class ChartBuilder
{
    // from: https://github.com/chartjs/Chart.js/blob/c0bf05f87df431202c991cfb5f3ee34106d9b6f4/src/plugins/plugin.colors.ts#L14-L22
    // see also https://github.com/symfony/ux/issues/763
    private const COLORS = [
        'rgb(54, 162, 235)', // blue
        'rgb(255, 99, 132)', // red
        'rgb(255, 159, 64)', // orange
        'rgb(255, 205, 86)', // yellow
        'rgb(75, 192, 192)', // green
        'rgb(153, 102, 255)', // purple
        'rgb(201, 203, 207)', // grey
    ];

    private const DIMMED_COLORS = [
        'rgb(54, 162, 235, 0.4)', // blue
        'rgb(255, 99, 132, 0.4)', // red
        'rgb(255, 159, 64, 0.4)', // orange
        'rgb(255, 205, 86, 0.4)', // yellow
        'rgb(75, 192, 192, 0.4)', // green
        'rgb(153, 102, 255, 0.4)', // purple
        'rgb(201, 203, 207, 0.4)', // grey
    ];

    public function __construct(
        private readonly QotdRepository $qotdRepository,
        private readonly ChartBuilderInterface $chartBuilder,
    ) {
    }

    public function buildMostQuotedUsers(): Chart
    {
        $counts = $this->qotdRepository->countMostQuotedUsers();
        $chart = $this->chartBuilder->createChart(Chart::TYPE_DOUGHNUT);

        $chart->setData([
            'labels' => array_column($counts, 'username'),
            'datasets' => [
                [
                    'data' => array_column($counts, 'count'),
                    'backgroundColor' => $this->getColors($counts),
                ],
            ],
        ]);

        return $chart;
    }

    public function buildMostUpVotedUsers(): Chart
    {
        $counts = $this->qotdRepository->countMostUpVotedUsers();
        $chart = $this->chartBuilder->createChart(Chart::TYPE_DOUGHNUT);

        $chart->setData([
            'labels' => array_column($counts, 'username'),
            'datasets' => [
                [
                    'data' => array_column($counts, 'vote'),
                    'backgroundColor' => $this->getColors($counts),
                ],
            ],
        ]);

        return $chart;
    }

    public function buildBiggestVotingUsers(): Chart
    {
        $counts = $this->qotdRepository->countBiggestVotingUsers();
        $chart = $this->chartBuilder->createChart(Chart::TYPE_DOUGHNUT);

        $labels = [];
        foreach ($counts as ['username' => $username]) {
            $p = strpos($username, '@');
            if ($p) {
                $labels[] = substr($username, 0, $p);
            } else {
                $labels[] = $username;
            }
        }

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'data' => array_column($counts, 'vote'),
                    'backgroundColor' => $this->getColors($counts),
                ],
            ],
        ]);

        return $chart;
    }

    public function buildCountOverPeriod(string $period): Chart
    {
        $counts = $this->qotdRepository->countOver($period);
        $chart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $labels = array_map(fn (array $item): string => $item['period']->format('y-m-d'), $counts);

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => '# Qotd',
                    'data' => array_column($counts, 'count'),
                    'backgroundColor' => self::DIMMED_COLORS[1],
                    'borderColor' => self::DIMMED_COLORS[1],
                    'tension' => 0.4,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => '# Votes',
                    'data' => array_column($counts, 'vote'),
                    'backgroundColor' => self::DIMMED_COLORS[0],
                    'borderColor' => self::DIMMED_COLORS[0],
                    'tension' => 0.4,
                    'yAxisID' => 'y1',
                ],
            ],
        ]);
        $chart->setOptions([
            'scales' => [
                'y1' => [
                    'position' => 'right',
                ],
            ],
        ]);

        return $chart;
    }

    // Keeps an affinity between a username and a color
    private function getColors(array $counts): array
    {
        static $savedColors = [];

        $i = 0;
        $newColor = [];
        foreach ($counts as ['username' => $username]) {
            $newColor[] = $savedColors[$username] ??= self::COLORS[$i++ % \count(self::COLORS)];
        }

        return $newColor;
    }
}
