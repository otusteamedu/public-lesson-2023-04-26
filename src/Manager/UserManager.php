<?php

namespace App\Manager;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class UserManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function createUser(string $login): User
    {
        $user = new User();
        $user->setLogin($login);
        $user->setCreatedAt();
        $user->setUpdatedAt();
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function updateUser(int $userId, string $login): ?User
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        /** @var User $user */
        $user = $userRepository->find($userId);
        if ($user === null) {
            return null;
        }
        $user->setLogin($login);
        $user->setUpdatedAt();
        $this->entityManager->flush();

        return $user;
    }

    public function deleteUser(int $userId): bool
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        /** @var User $user */
        $user = $userRepository->find($userId);
        if ($user === null) {
            return false;
        }
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return true;
    }

    public function getUser(int $userId): ?User
    {
        $userRepository = $this->entityManager->getRepository(User::class);

        return $userRepository->find($userId);
    }

    /**
     * @return User[]
     */
    public function getUsers(): array
    {
        $userRepository = $this->entityManager->getRepository(User::class);

        return $userRepository->findAll();
    }
}
