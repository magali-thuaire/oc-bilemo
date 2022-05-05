<?php

namespace App\Controller;

use LogicException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/tokens', name: 'api_tokens_')]
class TokenController extends AbstractController
{
    #[Route('', name: 'new', methods: ['POST'])]
    public function new(): JsonResponse
    {
        throw new LogicException('This method can be blank - it will be intercepted by the token firewall.');
    }
}