<?php

namespace App\Api\Pagination;

use Doctrine\ORM\QueryBuilder;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PaginationFactory
{

    private UrlGeneratorInterface $urlGenerator;

    public function __construct(UrlGeneratorInterface $urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
    }

    public function createCollection(QueryBuilder $qb, Request $request): array
    {
        $currentPage = $request->query->get('page', 1);
//        $filter = $request->query->get('filter', null);
//        $order = $request->query->get('order', 'DESC');

        $adapter = new QueryAdapter($qb);
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage(PaginatedCollection::PAGINATION_LIMIT);
        $pagerfanta->setCurrentPage($currentPage);

        $usersPaginated = new PaginatedCollection(
            $pagerfanta->getCurrentPageResults(),
            $pagerfanta->getNbResults()
        );

        $route = $request->get('_route');
        $routeParams = $request->query->all();
        $createLinkUrl = function($targetPage) use ($route, $routeParams) {
            return $this->urlGenerator->generate($route, array_merge(
                $routeParams,
                ['page' => $targetPage]
            ));
        };

        $usersPaginated->addLink('self', $createLinkUrl($currentPage));
        $usersPaginated->addLink('first', $createLinkUrl(1));
        $usersPaginated->addLink('last', $createLinkUrl($pagerfanta->getNbPages()));

        if ($pagerfanta->hasNextPage()) {
            $usersPaginated->addLink('next', $createLinkUrl($pagerfanta->getNextPage()));
        }

        if ($pagerfanta->hasPreviousPage()) {
            $usersPaginated->addLink('prev', $createLinkUrl($pagerfanta->getPreviousPage()));
        }

        return $usersPaginated->toArray();
    }
}