<?php

namespace App\Controller;

use App\Repository\QotdRepository;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class HallOfFameController extends AbstractController
{
    public function __construct(
        private readonly QotdRepository $qotdRepository,
    ) {
    }

    #[Route('/hall-of-fame', name: 'hall_of_fame', options: ['expose' => true])]
    #[Template('hall_of_fame/index.html.twig')]
    public function index(): array
    {
        $bestsOverWeeks = $this->qotdRepository->findBestsOver('week');
        $bestsOverMonths = $this->qotdRepository->findBestsOver('month');
        $bestsOverYears = $this->qotdRepository->findBestsOver('year');

        return [
            'bests_over_weeks' => $bestsOverWeeks,
            'bests_over_months' => $bestsOverMonths,
            'bests_over_years' => $bestsOverYears,
        ];
    }
}
