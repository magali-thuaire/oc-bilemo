<?php

namespace App\Repository;

use App\Api\Pagination\OrderAndFilterTrait;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

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
    use OrderAndFilterTrait;

    private Security $security;
    private ParameterBagInterface $parameterBag;

    public function __construct(
        ManagerRegistry $registry,
        Security $security,
        ParameterBagInterface $parameterBag
    )
    {
        parent::__construct($registry, User::class);
        $this->security = $security;
        $this->parameterBag = $parameterBag;
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

    public function findAllOwnedByClientQueryBuilder(Request $request): QueryBuilder
    {
        $client = $this->security->getUser();
        $this->setOrderAndFilterAttributes(
            $request,
            $this->parameterBag,
            $this->getClassName(),
            'email',
            'u'
        );

        $qb = $this->findAllQueryBuilder()
                ->andWhere('u.id = :client OR u.client = :client')
                ->setParameter('client', $client)
        ;

        if ($this->filter) {
            $qb = $this->filter($qb);
        }

        return $qb;
    }

    private function findAllQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('u')
                    ->orderBy(sprintf('u.%s', $this->orderBy), strtoupper($this->order));
    }
}
