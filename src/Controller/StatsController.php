<?php

namespace App\Controller;

use App\Stats\ChartBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StatsController extends AbstractController
{
    public function __construct(
        private readonly ChartBuilder $chartBuilder,
    ) {
    }

    #[Route('/stats', name: 'stats')]
    public function index(): Response
    {
        return $this->render('stats/index.html.twig', [
            'countOverWeek' => $this->chartBuilder->buildCountOverPeriod('week'),
            'countOverMonth' => $this->chartBuilder->buildCountOverPeriod('month'),
            'countOverYear' => $this->chartBuilder->buildCountOverPeriod('year'),
        ]);
    }
}
