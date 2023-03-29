<?php

namespace App\Stats;

use App\Repository\QotdRepository;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class ChartBuilder
{
    public function __construct(
        private readonly QotdRepository $qotdRepository,
        private readonly ChartBuilderInterface $chartBuilder,
    ) {
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
                    'backgroundColor' => 'rgb(255, 99, 132, .4)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'tension' => 0.4,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => '# Votes',
                    'data' => array_column($counts, 'vote'),
                    'backgroundColor' => 'rgba(45, 220, 126, .4)',
                    'borderColor' => 'rgba(45, 220, 126)',
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
}
