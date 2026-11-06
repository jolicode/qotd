<?php

namespace App\Twig\Components;

use App\Stats\ChartBuilder;
use App\Stats\StatsPeriod;
use Symfony\UX\Chartjs\Model\Chart;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class CountOverPeriodChart
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: true)]
    public StatsPeriod $period = StatsPeriod::Month;

    public function __construct(
        private readonly ChartBuilder $chartBuilder,
    ) {
    }

    #[LiveAction]
    public function showPeriod(#[LiveArg] StatsPeriod $period): void
    {
        $this->period = $period;
    }

    public function getChart(): Chart
    {
        return $this->chartBuilder->buildCountOverPeriod($this->period);
    }
}
