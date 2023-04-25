<?php

namespace App\Manager;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\NonUniqueResultException;

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

    public function getUserIgnoreDeleted(int $userId): ?User
    {
        $filters = $this->entityManager->getFilters();
        $filters->disable('softdeleteable');
        $userRepository = $this->entityManager->getRepository(User::class);

        return $userRepository->find($userId);
    }

    public function getUserWithQueryBuilder(int $userId): ?User
    {
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('u')
            ->from(User::class, 'u')
            ->where($qb->expr()->eq('u.id', ':id'))
            ->setParameter('id', $userId);

        return $qb->getQuery()->getOneOrNullResult();
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
