<?php

namespace App\Controller;

use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;

class KeyboardShortcutsController extends AbstractController
{
    #[Route('/keyboard-shortcuts', name: 'keyboard_shortcuts', options: ['expose' => true])]
    #[Template('keyboard_shortcuts/index.html.twig')]
    public function index(): void
    {
    }
}
