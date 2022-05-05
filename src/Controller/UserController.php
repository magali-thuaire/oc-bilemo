<?php

namespace App\Controller;

use App\Manager\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/users', name: 'api_users_')]
final class UserController extends AbstractController
{
    private UserManager $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    #[Route('', name: 'new', methods: ['POST'])]
    public function new(Request $request): JsonResponse
    {
        $user = $this->userManager->create($request, ['groups' => ['user:write']]);

        return $this->json(
            $user,
            Response::HTTP_CREATED,
            ['Location' => '/api/users/' . $user->getId()],
            ['groups' => ['user:read']]
        );
    }
}
