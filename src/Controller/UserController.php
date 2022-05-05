<?php

namespace App\Controller;

use App\Entity\User;
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
            ['Location' => $this->generateUrl('api_users_show', ['id' => $user->getId()])],
            ['groups' => ['user:read']]
        );
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(User $user): JsonResponse
    {
        return $this->json(
            $user,
            Response::HTTP_OK,
            ['Location' => $this->generateUrl('api_users_show', ['id' => $user->getId()])],
            ['groups' => ['user:read']]
        );
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PUT', 'PATCH'])]
    public function update(User $user, Request $request): JsonResponse
    {
        $user = $this->userManager->update($user, $request, ['groups' => ['user:write']]);

        return $this->json(
            $user,
            Response::HTTP_OK,
            ['Location' => $this->generateUrl('api_users_show', ['id' => $user->getId()])],
            ['groups' => ['user:read']]
        );
    }

    #[Route('/{id}', name: 'remove', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function remove(User $user): JsonResponse
    {
        $this->userManager->remove($user);

        return $this->json(
            [],
            Response::HTTP_NO_CONTENT
        );
    }
}
