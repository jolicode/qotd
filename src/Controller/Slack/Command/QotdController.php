<?php

namespace App\Controller\Slack\Command;

use App\Repository\QotdRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QotdController extends AbstractController
{
    public function __construct(
        private readonly QotdRepository $qotdRepository,
    ) {
    }

    #[Route('/command/qotd')]
    public function __invoke(Request $request): Response
    {
        try {
            $date = new \DateTimeImmutable($request->request->get('text') ?: 'yesterday');
        } catch (\Exception) {
            return new Response('The date is not valid.');
        }

        $qotd = $this->qotdRepository->findOneBy(['date' => $date]);

        if (!$qotd) {
            return new Response(sprintf('There was not QOTD on %s.', $date->format('Y-m-d')));
        }

        return new Response(sprintf('%s\'s QOTD was: %s', $qotd->date->format('Y-m-d'), $qotd->permalink), 200);
    }
}
