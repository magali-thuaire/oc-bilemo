<?php

namespace App\Manager;

use App\Entity\User;
use App\Form\UserFormType;
use App\Repository\UserRepository;
use App\Service\FormService;
use App\Api\Pagination\PaginationFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Security;

final class UserManager
{
    private UserPasswordHasherInterface $userPasswordHasher;
    private UserRepository $userRepository;
    private FormService $formService;
    private PaginationFactory $paginationFactory;
    private Security $security;

    public function __construct(
        UserPasswordHasherInterface $userPasswordHasher,
        UserRepository $userRepository,
        FormService $formService,
        PaginationFactory $paginationFactory,
        Security $security
    ) {
        $this->userPasswordHasher = $userPasswordHasher;
        $this->userRepository = $userRepository;
        $this->formService = $formService;
        $this->paginationFactory = $paginationFactory;
        $this->security = $security;
    }

    public function list(Request $request): array
    {
        $filter = $request->query->get('filter');
        $order = $request->query->get('order', 'DESC');

        $qb = $this->userRepository->findAllOwnedByClientQueryBuilder($order, $filter);

        return $this->paginationFactory->createCollection($qb, $request);
    }

    public function create(Request $request, array $context = []): User
    {
        $form = $this->formService->processForm(
            $request,
            null,
            User::class,
            UserFormType::class,
            $context,
            ['plainPassword' => 'password']
        );

        $user = $form->getData();
        $this->setUserPassword($user);

        $this->userRepository->add($user);

        return $user;
    }

    public function update(User $user, Request $request, array $context = []): User
    {
        $form = $this->formService->processForm(
            $request,
            $user,
            User::class,
            UserFormType::class,
            $context,
            ['plainPassword' => 'password']
        );

        $user = $form->getData();
        $this->setUserPassword($user);

        $this->userRepository->update($user);

        return $user;
    }

    public function remove(User $user): void
    {
        $this->userRepository->remove($user);
    }

    private function setUserPassword(User $user): void
    {
        if ($user->getPlainPassword()) {
            $user
                ->setPassword($this->userPasswordHasher->hashPassword($user, $user->getPlainPassword()))
                ->eraseCredentials()
            ;
        }
    }
}
