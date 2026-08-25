<?php

namespace App\Controller;

use App\Repository\QotdRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HallOfFameController extends AbstractController
{
    public function __construct(
        private readonly QotdRepository $qotdRepository,
    ) {
    }

    #[Route('/hall-of-fame', name: 'hall_of_fame')]
    public function index(): Response
    {
        $bestsOverWeeks = $this->qotdRepository->findBestsOver('week');
        $bestsOverMonths = $this->qotdRepository->findBestsOver('month');
        $bestsOverYears = $this->qotdRepository->findBestsOver('year');

        return $this->render('hall_of_fame/index.html.twig', [
            'bests_over_weeks' => $bestsOverWeeks,
            'bests_over_months' => $bestsOverMonths,
            'bests_over_years' => $bestsOverYears,
        ]);
    }
}
