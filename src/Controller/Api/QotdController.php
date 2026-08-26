<?php

namespace App\Controller\Api;

use App\Repository\QotdRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class QotdController extends AbstractController
{
    public function __construct(
        private readonly QotdRepository $qotdRepository,
    ) {
    }

    #[Route('/api/qotds', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->json(
            $this->qotdRepository->findLast(),
            context: ['groups' => ['qotd:read']],
        );
    }
}
