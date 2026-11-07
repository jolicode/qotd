<?php

namespace App\Controller;

use App\Entity\Qotd;
use App\Repository\QotdRepository;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class RandomController extends AbstractController
{
    public function __construct(
        private readonly QotdRepository $qotdRepository,
    ) {
    }

    #[Route('/random', name: 'random', options: ['expose' => true])]
    #[Template('random/index.html.twig')]
    public function index(): array
    {
        $qotd = $this->qotdRepository->findRandom();

        if (!$qotd instanceof Qotd) {
            throw $this->createNotFoundException('No QOTD found.');
        }

        return [
            'qotd' => $qotd,
        ];
    }
}
