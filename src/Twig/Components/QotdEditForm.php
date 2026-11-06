<?php

namespace App\Twig\Components;

use App\Entity\Qotd;
use App\Form\Type\QotdType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class QotdEditForm extends AbstractController
{
    use ComponentToolsTrait;
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    #[LiveProp]
    public Qotd $initialFormData;

    public function getRemainingContextLength(): int
    {
        return Qotd::CONTEXT_MAX_LENGTH - mb_strlen($this->getForm()->get('context')->getViewData());
    }

    #[LiveAction]
    public function save(EntityManagerInterface $em): void
    {
        $this->submitForm();

        $em->flush();

        $this->addFlash('success', 'Your changes have been saved!');

        $this->dispatchBrowserEvent('modal:visit', [
            'url' => $this->generateUrl('qotd_show', ['id' => $this->initialFormData->id]),
        ]);
    }

    /**
     * Without CSRF token, like any LiveAction: the stateless token only exists
     * once the browser submits a form, which never happens on a re-render.
     *
     * @return FormInterface<Qotd>
     */
    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(QotdType::class, $this->initialFormData, ['csrf_protection' => false]);
    }
}
