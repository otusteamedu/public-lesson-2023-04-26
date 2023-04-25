<?php

namespace App\Service;

use App\Entity\Subscription;
use App\Entity\User;
use App\Manager\SubscriptionManager;
use App\Manager\UserManager;

class SubscriptionService
{
    public function __construct(
        private readonly UserManager $userManager,
        private readonly SubscriptionManager $subscriptionManager,
    )
    {
    }


    public function createSubscription(int $authorId, int $followerId): ?Subscription
    {
        $author = $this->userManager->getUser($authorId);
        $follower = $this->userManager->getUser($followerId);
        if ($author === null || $follower === null) {
            return null;
        }

        return $this->subscriptionManager->createSubscription($author, $follower);
    }

    public function deleteSubscription(int $authorId, int $followerId): bool
    {
        $author = $this->userManager->getUser($authorId);
        $follower = $this->userManager->getUser($followerId);
        if ($author === null || $follower === null) {
            return false;
        }

        return $this->subscriptionManager->deleteSubscription($author, $follower);
    }
}
