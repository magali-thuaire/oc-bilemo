<?php

namespace App\Api\Pagination;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class PaginatedCollection
{
    public array $items;
    public int $total;
    public int $count;
    public array $_links = [];
    public string $orderBy;
    public string $order;
    public ?string $filter;
    public string $filterBy;

    public function __construct(iterable $items, int $total, ServiceEntityRepository $repository)
    {
        $this->items = (array) $items;
        $this->total = $total;
        $this->count = count($this->items);
        $this->order = property_exists($repository, 'order') ? $repository->order : '';
        $this->orderBy = property_exists($repository, 'orderBy') ? $repository->orderBy : '';
        $this->filter = property_exists($repository, 'filter') ? $repository->filter : '';
        $this->filterBy = property_exists($repository, 'filterBy') ? $repository->filterBy : '';

    }

    public function addLink($rel, $url)
    {
        $this->_links[$rel] = $url;
    }

    public function toArray(): array
    {
        return (array) $this;
    }

}