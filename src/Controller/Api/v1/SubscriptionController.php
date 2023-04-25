<?php

namespace App\Controller\Api\v1;

use App\Entity\User;
use App\Manager\UserManager;
use App\Service\SubscriptionService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api/v1/subscription')]
#[AsController]
class SubscriptionController
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
    ) {
    }

    #[Route(path: '', methods: ['POST'])]
    public function createSubscriptionAction(Request $request): Response
    {
        $authorId = $request->request->get('authorId');
        $followerId = $request->request->get('followerId');
        $subscription = $this->subscriptionService->createSubscription($authorId, $followerId);
        $code = $subscription === null ? Response::HTTP_BAD_REQUEST : Response::HTTP_OK;

        return new JsonResponse(['success' => $subscription !== null], $code);
    }

    #[Route(path: '', methods: ['DELETE'])]
    public function deleteSubscriptionAction(Request $request): Response
    {
        $authorId = $request->query->get('authorId');
        $followerId = $request->query->get('followerId');
        $result = $this->subscriptionService->deleteSubscription($authorId, $followerId);

        return new JsonResponse(['success' => $result], $result ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);
    }
}
