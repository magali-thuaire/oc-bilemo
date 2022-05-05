<?php

namespace App\Manager;

use App\Entity\User;
use App\Form\UserFormType;
use App\Repository\UserRepository;
use App\Service\FormService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class UserManager
{
    private UserPasswordHasherInterface $userPasswordHasher;
    private UserRepository $userRepository;
    private FormService $formService;

    public function __construct(
        UserPasswordHasherInterface $userPasswordHasher,
        UserRepository $userRepository,
        FormService $formService
    ) {
        $this->userPasswordHasher = $userPasswordHasher;
        $this->userRepository = $userRepository;
        $this->formService = $formService;
    }

    public function create(Request $request, array $context = []): User
    {
        $form = $this->formService->processForm($request, User::class, UserFormType::class, $context);

        if (!$form->isValid()) {
            $this->formService->throwApiProblemValidationException($form, ['plainPassword' => 'password']);
        }

        $user = $form->getData();
        $user
            ->setPassword($this->userPasswordHasher->hashPassword($user, $user->getPlainPassword()))
            ->eraseCredentials()
        ;

        $this->userRepository->add($user);

        return $user;
    }
}
