<?php

namespace App\Controller;

use App\Repository\QotdRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class RandomController extends AbstractController
{
    public function __construct(
        private readonly QotdRepository $qotdRepository,
    ) {
    }

    #[Route('/random', name: 'random')]
    public function index(): Response
    {
        $qotd = $this->qotdRepository->findRandom();

        if (null === $qotd) {
            throw $this->createNotFoundException('No QOTD found.');
        }

        return $this->render('random/index.html.twig', [
            'qotd' => $qotd,
        ]);
    }
}
