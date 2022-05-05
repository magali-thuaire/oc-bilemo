<?php

namespace App\Manager;

use App\Repository\ProductRepository;
use App\Service\Pagination\PaginationFactory;
use Symfony\Component\HttpFoundation\Request;

final class ProductManager
{
    private PaginationFactory $paginationFactory;
    private ProductRepository $productRepository;

    public function __construct(
        PaginationFactory $paginationFactory,
        ProductRepository $productRepository
    ) {
        $this->paginationFactory = $paginationFactory;
        $this->productRepository = $productRepository;
    }

    public function list(Request $request): array
    {
        $filter = $request->query->get('filter');
        $order = $request->query->get('order', 'DESC');

        $qb = $this->productRepository->findAllQueryBuilder($order, $filter);

        return $this->paginationFactory->createCollection($qb, $request);
    }

}
