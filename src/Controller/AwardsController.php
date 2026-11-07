<?php

namespace App\Controller;

use App\Repository\QotdRepository;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class AwardsController extends AbstractController
{
    public function __construct(
        private readonly QotdRepository $qotdRepository,
    ) {
    }

    #[Route('/awards', name: 'awards', options: ['expose' => true])]
    #[Template('awards/index.html.twig')]
    public function index(): array
    {
        return [
            'awards' => $this->qotdRepository->computeAwards(),
        ];
    }
}
