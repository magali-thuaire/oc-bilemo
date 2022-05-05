<?php

namespace App\Manager;

use App\Entity\User;
use App\Form\UserFormType;
use App\Repository\UserRepository;
use App\Service\FormService;
use App\Service\Pagination\PaginationFactory;
use App\Service\PaginatorService;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Routing\RouterInterface;

final class UserManager
{
    private UserPasswordHasherInterface $userPasswordHasher;
    private UserRepository $userRepository;
    private FormService $formService;
    private PaginationFactory $paginationFactory;

    public function __construct(
        UserPasswordHasherInterface $userPasswordHasher,
        UserRepository $userRepository,
        FormService $formService,
        PaginationFactory $paginationFactory,
    ) {
        $this->userPasswordHasher = $userPasswordHasher;
        $this->userRepository = $userRepository;
        $this->formService = $formService;
        $this->paginationFactory = $paginationFactory;
    }

    public function list(Request $request): array
    {
        $filter = $request->query->get('filter', null);
        $order = $request->query->get('order', 'DESC');

        $qb = $this->userRepository->findAllQueryBuilder($order, $filter);

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
