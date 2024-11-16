<?php

namespace App\Controller;

use App\Repository\QotdRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AwardsController extends AbstractController
{
    public function __construct(
        private readonly QotdRepository $qotdRepository,
    ) {
    }

    #[Route('/awards', name: 'awards')]
    public function index(): Response
    {
        return $this->render('awards/index.html.twig', [
            'awards' => $this->qotdRepository->computeAwards(),
        ]);
    }
}
