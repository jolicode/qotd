<?php

namespace App\Twig\Components;

use App\Stats\ChartBuilder;
use App\Stats\UsersChartType;
use Symfony\UX\Chartjs\Model\Chart;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class UsersChart
{
    use DefaultActionTrait;

    #[LiveProp]
    public UsersChartType $type;

    public function __construct(
        private readonly ChartBuilder $chartBuilder,
    ) {
    }

    public function getChart(): Chart
    {
        return $this->chartBuilder->buildUsersChart($this->type);
    }
}
