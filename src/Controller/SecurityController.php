<?php

namespace App\Controller;

use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SecurityController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google')]
    public function connectAction(ClientRegistry $clientRegistry): Response
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect([], [])
        ;
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(): never
    {
        throw new \LogicException('This method must be blank - it will be intercepted by the Security layer.');
    }
}
