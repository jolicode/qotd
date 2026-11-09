<?php

namespace App\Controller;

use App\Entity\Qotd;
use App\Form\Model\QotdFilters;
use App\Form\Type\QotdFiltersType;
use App\Form\Type\QotdImagesType;
use App\Qotd\QotdImageUploader;
use App\Repository\Model\QotdDirection;
use App\Repository\QotdRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class QotdController extends AbstractController
{
    public function __construct(
        private readonly QotdRepository $qotdRepository,
    ) {
    }

    #[Route('/', name: 'qotd_index', defaults: ['direction' => QotdDirection::Latest->value], options: ['expose' => true])]
    #[Route('/top', name: 'qotd_index_top', defaults: ['direction' => QotdDirection::Top->value], options: ['expose' => true])]
    #[Route('/flop', name: 'qotd_index_flop', defaults: ['direction' => QotdDirection::Flop->value], options: ['expose' => true])]
    #[Template('qotd/index.html.twig')]
    public function index(
        Request $request,
        QotdDirection $direction,
    ): array {
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

        $pagination = $this
            ->qotdRepository
            ->findForHomepage($direction, $filters)
        ;

        return [
            'pagination' => $pagination,
            'direction' => $direction,
            'form' => $form,
            'valid' => $valid,
        ];
    }

    #[Route('/not-voted', name: 'qotd_index_not_voted', options: ['expose' => true])]
    #[Template('qotd/index.html.twig')]
    public function notVoted(#[CurrentUser] UserInterface $user): array|Response
    {
        $pagination = $this
            ->qotdRepository
            ->findForHomepageNotVoted($user)
        ;

        // The QOTD on the other side of the cursor may all have been voted since the link was rendered
        if ($pagination->isEmpty() && null !== $pagination->getCursor()) {
            return $this->redirectToRoute('qotd_index_not_voted');
        }

        return [
            'pagination' => $pagination,
            'direction' => QotdDirection::NotVoted,
        ];
    }

    #[Route('/search', name: 'qotd_search', methods: ['GET'], options: ['expose' => true])]
    #[Template('qotd/search.html.twig')]
    public function search(): void
    {
    }

    #[Route('/qotd/{id}/edit', name: 'qotd_show_edit', methods: ['GET'], options: ['expose' => true])]
    #[Template('qotd/edit.html.twig')]
    public function edit(#[MapEntity()] Qotd $qotd): void
    {
    }

    #[Route('/qotd/{id}/images', name: 'qotd_show_images', methods: ['GET', 'POST'], options: ['expose' => true])]
    #[Template('qotd/images.html.twig')]
    public function images(
        Request $request,
        #[MapEntity()] Qotd $qotd,
        QotdImageUploader $imageUploader,
        EntityManagerInterface $em,
    ): array|Response {
        $form = $this->createForm(QotdImagesType::class);

        if ($form->handleRequest($request)->isSubmitted() && $form->isValid()) {
            foreach ($form->get('images')->getData() as $image) {
                $imageUploader->upload($qotd, $image);
            }

            $em->flush();

            $this->addFlash('success', 'Your images have been added!');

            return $this->redirectToRoute('qotd_show', ['id' => $qotd->id]);
        }

        return [
            'form' => $form,
            'qotd' => $qotd,
        ];
    }

    #[Route('/qotd/{id}', name: 'qotd_show', methods: ['GET'], options: ['expose' => true])]
    #[Template('qotd/show.html.twig')]
    public function show(#[MapEntity()] Qotd $qotd): void
    {
    }

    #[Route('/qotd/{id}/details', name: 'qotd_show_details', methods: ['GET'], options: ['expose' => true])]
    #[Template('qotd/show_details.html.twig')]
    public function showDetails(#[MapEntity()] Qotd $qotd): void
    {
    }
}
