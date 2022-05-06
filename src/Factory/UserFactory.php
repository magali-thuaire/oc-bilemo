<?php

namespace App\Factory;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<User>
 *
 * @method static User|Proxy createOne(array $attributes = [])
 * @method static User[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static User|Proxy find(object|array|mixed $criteria)
 * @method static User|Proxy findOrCreate(array $attributes)
 * @method static User|Proxy first(string $sortedField = 'id')
 * @method static User|Proxy last(string $sortedField = 'id')
 * @method static User|Proxy random(array $attributes = [])
 * @method static User|Proxy randomOrCreate(array $attributes = [])
 * @method static User[]|Proxy[] all()
 * @method static User[]|Proxy[] findBy(array $attributes)
 * @method static User[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static User[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static UserRepository|RepositoryProxy repository()
 * @method User|Proxy create(array|callable $attributes = [])
 */
final class UserFactory extends ModelFactory
{
    private UserPasswordHasherInterface $userPasswordHasher;

    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        parent::__construct();

        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function createdNow(): self
    {
        return  $this->addState([
            'createdAt' => self::faker()->dateTimeBetween('now'),
            'updatedAt' => self::faker()->dateTimeBetween('now'),
        ]);
    }

    public function promoteRole(string $role): self
    {
        $defaults = $this->getDefaults();

        $roles = array_merge($defaults['roles'], [
            $role
        ]);

        return $this->addState([
            'roles' => $roles,
        ]);
    }

    public function setClient(Proxy $client): self
    {
        return $this->addState([
            'client' => $client,
        ]);
    }

    protected function getDefaults(): array
    {
        return [
            'email' => self::faker()->email(),
            'plainPassword' => 'bilemo',
            'roles' => [],
            'createdAt' => self::faker()->dateTimeBetween('-30 days', '-15 days'),
            'updatedAt' => self::faker()->dateTimeBetween('-7 days'),
        ];
    }

    protected function initialize(): self
    {
        return $this
            ->afterInstantiate(function (User $user): void {
                if ($plainPassword = $user->getPlainPassword()) {
                    $user->setPassword($this->userPasswordHasher->hashPassword($user, $plainPassword));
                    $user->eraseCredentials();
                }
            });
    }

    protected static function getClass(): string
    {
        return User::class;
    }
}
