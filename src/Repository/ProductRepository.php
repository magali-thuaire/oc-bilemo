<?php

namespace App\Repository;

use App\Api\Pagination\OrderAndFilterTrait;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @extends ServiceEntityRepository<Product>
 *
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    use OrderAndFilterTrait;

    private ParameterBagInterface $parameterBag;

    public function __construct(
        ManagerRegistry $registry,
        ParameterBagInterface $parameterBag
    )
    {
        parent::__construct($registry, Product::class);
        $this->parameterBag = $parameterBag;
    }

    /**
     */
    public function add(Product $entity, bool $flush = false): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     */
    public function remove(Product $entity, bool $flush = false): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findAllQueryBuilder(Request $request): QueryBuilder
    {
        $this->setOrderAndFilterAttributes(
            $request,
            $this->parameterBag,
            $this->getClassName(),
            'name',
            'p'
        );

        $qb = $this->createQueryBuilder('p')
                   ->orderBy('p.createdAt', strtoupper($this->order))
        ;

        if ($this->filter) {
            $qb = $this->filter($qb);
        }

        return $qb;
    }

//    /**
//     * @return Product[] Returns an array of Product objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Product
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
