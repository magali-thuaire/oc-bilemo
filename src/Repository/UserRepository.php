<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

use function get_class;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    private Security $security;

    public function __construct(ManagerRegistry $registry, Security $security)
    {
        parent::__construct($registry, User::class);
        $this->security = $security;
    }

    public function add(User $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function update(User $entity): void
    {
        $this->add($entity);
    }

    public function remove(User $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    public function findAllOwnedByClientQueryBuilder(string $order, ?string $filter = null): QueryBuilder
    {
        $client = $this->security->getUser();

        $qb = $this->findAllQueryBuilder($order)
                ->andWhere('u.id = :client OR u.client = :client')
                ->setParameter('client', $client)
        ;

        if ($filter) {
            $qb = $this->filterByEmail($qb, $filter);
        }

        return $qb;
    }

    public function filterByEmail(QueryBuilder $qb, string $filter): QueryBuilder
    {
        return $qb->andWhere('u.email LIKE :filter')
                  ->setParameter('filter', "%$filter%")
            ;
    }

    private function findAllQueryBuilder(string $order = 'DESC'): QueryBuilder
    {
        return $this->createQueryBuilder('u')
                    ->orderBy('u.createdAt', strtoupper($order));
    }
}
