<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    /**
     * @var EntityManagerInterface
    */
    private $manager;

    /**
     * @var UserPasswordEncoderInterface
    */
    private $encoder;

    /**
     * @param ManagerRegistry $registry
     * @param EntityManagerInterface $manager
     * @param UserPasswordEncoderInterface $encoder
    */
    public function __construct(
        ManagerRegistry $registry,
        EntityManagerInterface $manager,
        UserPasswordEncoderInterface $encoder
    ) {
        parent::__construct($registry, User::class);
        $this->manager = $manager;
        $this->encoder = $encoder;
    }

    /**
     * @param array $data
     * @return void
    */
    public function saveUser(array $data): void
    {
        $newUser = new User();

        $newUser
            ->setFirstName($data['firstName'])
            ->setLastName($data['lastName'])
            ->setEmail($data['email'])
            ->setRoles([])
            ->setPhone($data['phone'])
            ->setPassword($this->encoder->encodePassword($newUser, $data['password']));

        $this->manager->persist($newUser);
        $this->manager->flush();
    }

    /**
     * @param User $user
     * @return User
    */
    public function updateUser(User $user): User
    {
        $this->manager->persist($user);
        $this->manager->flush();

        return $user;
    }

    /**
     * @param User $user
     * @return void
    */
    public function removeUser(User $user): void
    {
        $this->manager->remove($user);
        $this->manager->flush();
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(UserInterface $user, string $newEncodedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newEncodedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    // /**
    //  * @return User[] Returns an array of User objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
