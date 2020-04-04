<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {
        $users = $this->getUsersData();

        foreach ($users as $data) {
            $user = new User();
            $user
                ->setFirstName($data['firstName'])
                ->setLastName($data['lastName'])
                ->setEmail($data['email'])
                ->setRoles($data['roles'])
                ->setPhone($data['phone'])
                ->setPassword($this->encoder->encodePassword($user, $data['password']));

            $manager->persist($user);
            $manager->flush();
        }
    }

    protected function getUsersData(): array
    {
        return [
            [
                'firstName' => 'Roman',
                'lastName' => 'Zagday',
                'email' => 'roman.zagday@email.com',
                'roles' => ['ROLE_ADMIN'],
                'phone' => '+375333739844',
                'password' => 'zagday',
            ],
            [
                'firstName' => 'James',
                'lastName' => 'Milner',
                'email' => 'james.milner@email.com',
                'roles' => [],
                'phone' => '+375331234567',
                'password' => 'milner',
            ]
        ];
    }
}
