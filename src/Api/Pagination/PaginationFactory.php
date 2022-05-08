<?php

namespace App\Api\Pagination;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaginationFactory
{

    private UrlGeneratorInterface $urlGenerator;
    private ParameterBagInterface $parameterBag;

    public function __construct(UrlGeneratorInterface $urlGenerator, ParameterBagInterface $parameterBag)
    {
        $this->urlGenerator = $urlGenerator;
        $this->parameterBag = $parameterBag;
    }

    public function createCollection(QueryBuilder $qb, Request $request, ServiceEntityRepository $repository): array
    {
        $currentPage = $request->query->get('page', 1);
        $maxItemsPerPage = $request->query->get('count') ?? $this->parameterBag->get('pagination.items_per_page');

        $adapter = new QueryAdapter($qb);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($maxItemsPerPage);
        $pagerfanta->setCurrentPage($currentPage);

        $itemsPaginated = new PaginatedCollection(
            $pagerfanta->getCurrentPageResults(),
            $pagerfanta->getNbResults(),
            $repository
        );

        $route = $request->get('_route');
        $routeParams = $request->query->all();
        $createLinkUrl = function($targetPage) use ($route, $routeParams) {
            return $this->urlGenerator->generate($route, array_merge(
                $routeParams,
                ['page' => $targetPage]
            ));
        };

        $itemsPaginated->addLink('self', $createLinkUrl($currentPage));
        $itemsPaginated->addLink('first', $createLinkUrl(1));
        $itemsPaginated->addLink('last', $createLinkUrl($pagerfanta->getNbPages()));

        if ($pagerfanta->hasNextPage()) {
            $itemsPaginated->addLink('next', $createLinkUrl($pagerfanta->getNextPage()));
        }

        if ($pagerfanta->hasPreviousPage()) {
            $itemsPaginated->addLink('prev', $createLinkUrl($pagerfanta->getPreviousPage()));
        }

        return $itemsPaginated->toArray();
    }
}