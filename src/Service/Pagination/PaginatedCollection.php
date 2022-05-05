<?php

namespace App\Service\Pagination;

class PaginatedCollection
{
    public const PAGINATION_LIMIT = 5;

    public array $items;
    public int $total;
    public int $count;
    public array $_links = [];

    public function __construct(iterable $items, int $total)
    {
        $this->items = (array) $items;
        $this->total = $total;
        $this->count = count($this->items);
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