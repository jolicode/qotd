<?php

namespace App\Controller;

use App\Entity\Qotd;
use App\Qotd\Model\MyTopMove;
use App\Repository\MyTopRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

class MyTopController extends AbstractController
{
    #[Route('/my-top', name: 'my_top', methods: ['GET'], options: ['expose' => true])]
    #[Template('my_top/index.html.twig')]
    public function index(): void
    {
    }

    #[Route('/my-top/{id}/position', name: 'my_top_move', methods: ['POST'])]
    #[IsCsrfTokenValid('my-top')]
    public function move(
        #[MapEntity()] Qotd $qotd,
        #[MapRequestPayload(validationFailedStatusCode: Response::HTTP_BAD_REQUEST)] MyTopMove $move,
        #[CurrentUser] UserInterface $user,
        MyTopRepository $myTopRepository,
        EntityManagerInterface $em,
    ): Response {
        $myTopRepository->getForUser($user)->move($qotd, $move->position);

        $em->flush();

        return new Response(status: Response::HTTP_NO_CONTENT);
    }
}
