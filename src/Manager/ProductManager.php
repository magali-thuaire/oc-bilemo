<?php

namespace App\Manager;

use App\Repository\ProductRepository;
use App\Api\Pagination\PaginationFactory;
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
        $qb = $this->productRepository->findAllQueryBuilder($request);

        return $this->paginationFactory->createCollection(
            $qb,
            $request,
            $this->productRepository
        );
    }

}
