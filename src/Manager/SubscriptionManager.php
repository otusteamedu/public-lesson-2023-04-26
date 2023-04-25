<?php

namespace App\Manager;

use App\Entity\Subscription;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class SubscriptionManager
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function createSubscription(User $author, User $follower, ?int $order): Subscription
    {
        $subscription = new Subscription();
        $subscription->setAuthor($author);
        $subscription->setFollower($follower);
        $subscription->setCreatedAt();
        $subscription->setUpdatedAt();
        $subscription->setOrder($order);
        $author->addFollower($subscription);
        $follower->addAuthor($subscription);
        $this->entityManager->persist($subscription);
        $this->entityManager->flush();

        return $subscription;
    }

    public function deleteSubscription(User $author, User $follower): bool
    {
        $subscriptionRepository = $this->entityManager->getRepository(Subscription::class);
        $subscription = $subscriptionRepository->findOneBy(['author' => $author, 'follower' => $follower]);
        if ($subscription === null) {
            return false;
        }
        $author->removeFollower($subscription);
        $follower->removeAuthor($subscription);
        $this->entityManager->remove($subscription);
        $this->entityManager->flush();

        return true;
    }
}
