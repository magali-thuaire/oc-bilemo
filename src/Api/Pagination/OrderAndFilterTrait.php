<?php

namespace App\Api\Pagination;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

trait OrderAndFilterTrait
{
    public string $order;
    public string $orderBy;
    public string $filter = '';
    public string $filterBy;
    private string $alias;

    private function setOrderAndFilterAttributes(
        Request $request,
        ParameterBagInterface $parameterBag,
        string $className,
        string $defaultFilterBy,
        string $alias
    )
    {
        $order = $request->query->get('order');
        $orderBy = $request->query->get('orderBy');
        $filter = $request->query->get('filter');
        $filterBy = $request->query->get('filterBy');

        $this->orderBy = $parameterBag->get('pagination.items_orderby');
        $this->order = $parameterBag->get('pagination.items_order');
        $this->filterBy = $defaultFilterBy;
        $this->alias = $alias;

        if (property_exists($className, $orderBy)) {
            $this->orderBy = $orderBy;
        }

        if (in_array($order = strtoupper($order), ['ASC', 'DESC'])) {
            $this->order = $order;
        }

        if (property_exists($className, $filterBy)) {
            $this->filterBy = $filterBy;
        }

        if ($filter) {
            $this->filter = $filter;
        }
    }

    private function filter(QueryBuilder $qb): QueryBuilder
    {
        $filter = sprintf('%s.%s LIKE :filter', $this->alias, $this->filterBy);

        return $qb->andWhere($filter)
                  ->setParameter('filter', "%$this->filter%")
            ;
    }
}
