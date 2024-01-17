<?php

namespace App\Controller;

use App\Entity\Qotd;
use App\Form\Model\QotdFilters;
use App\Form\Type\QotdFiltersType;
use App\Form\Type\QotdType;
use App\Repository\Model\QotdDirection;
use App\Repository\Model\QotdVote;
use App\Repository\QotdRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class QotdController extends AbstractController
{
    public function __construct(
        private readonly QotdRepository $qotdRepository,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/', name: 'qotd_index', defaults: ['direction' => QotdDirection::Latest->value])]
    #[Route('/top', name: 'qotd_index_top', defaults: ['direction' => QotdDirection::Top->value])]
    #[Route('/flop', name: 'qotd_index_flop', defaults: ['direction' => QotdDirection::Flop->value])]
    public function index(
        Request $request,
        QotdDirection $direction,
    ): Response {
        $valid = false;
        $filters = new QotdFilters();
        $form = $this->createForm(QotdFiltersType::class, $filters);

        if ($form->handleRequest($request)->isSubmitted()) {
            if ($form->isValid()) {
                $valid = true;
            } else {
                $filters = null;
            }
        }

        $page = max(1, $request->query->getInt('page', 1));
        $pagination = $this
            ->qotdRepository
            ->findForHomepage($page, $direction, $filters)
        ;

        return $this->render('qotd/index.html.twig', [
            'pagination' => $pagination,
            'direction' => $direction,
            'form' => $form,
            'valid' => $valid,
        ]);
    }

    #[Route('/not-voted', name: 'qotd_index_not_voted')]
    public function notVoted(Request $request, #[CurrentUser] UserInterface $user): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $pagination = $this
            ->qotdRepository
            ->findForHomepageNotVoted($page, $user)
        ;

        return $this->render('qotd/index.html.twig', [
            'pagination' => $pagination,
            'direction' => QotdDirection::NotVoted,
        ]);
    }

    #[Route('/qotd/{id}/vote/up', name: 'qotd_vote_up', methods: ['POST'], defaults: ['vote' => QotdVote::Up->value])]
    #[Route('/qotd/{id}/vote/down', name: 'qotd_vote_down', methods: ['POST'], defaults: ['vote' => QotdVote::Down->value])]
    #[Route('/qotd/{id}/vote/null', name: 'qotd_vote_null', methods: ['POST'], defaults: ['vote' => QotdVote::Null->value])]
    public function vote(Request $request, Qotd $qotd, QotdVote $vote, #[CurrentUser] UserInterface $user): Response
    {
        if (!$this->isCsrfTokenValid('vote', (string) $request->request->get('token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $qotd->applyVote($vote, $user);

        $this->em->flush();

        $this->addFlash('success', 'Thanks for your vote!');

        return $this->redirectToRoute('qotd_show', ['id' => $qotd->id]);
    }

    #[Route('/search', name: 'qotd_search', methods: ['GET'])]
    public function search(): Response
    {
        return $this->render('qotd/search.html.twig');
    }

    #[Route('/qotd/{id}/edit', name: 'qotd_show_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        #[MapEntity()] Qotd $qotd,
    ): Response {
        $form = $this->createForm(QotdType::class, $qotd, [
            'action' => $this->generateUrl('qotd_show_edit', ['id' => $qotd->id]),
        ]);

        if ($form->handleRequest($request)->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Your changes have been saved!');

            return $this->redirectToRoute('qotd_show', ['id' => $qotd->id]);
        }

        return $this->render('qotd/edit.html.twig', [
            'form' => $form,
            'qotd' => $qotd,
        ]);
    }

    #[Route('/qotd/{id}', name: 'qotd_show', methods: ['GET'])]
    #[Template('qotd/show.html.twig')]
    public function show(#[MapEntity()] Qotd $qotd): void
    {
    }

    #[Route('/qotd/{id}/details', name: 'qotd_show_details', methods: ['GET'])]
    #[Template('qotd/show_details.html.twig')]
    public function showDetails(#[MapEntity()] Qotd $qotd): void
    {
    }
}
