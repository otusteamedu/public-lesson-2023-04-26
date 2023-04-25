<?php

namespace App\Controller\Api\v1;

use App\Entity\User;
use App\Manager\UserManager;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api/v1/user')]
#[AsController]
class UserController
{
    public function __construct(private readonly UserManager $userManager)
    {
    }

    #[Route(path: '', methods: ['POST'])]
    public function saveUserAction(Request $request): Response
    {
        $login = $request->request->get('login');
        $user = $this->userManager->createUser($login);

        return new JsonResponse(['success' => true, 'userId' => $user->getId()], Response::HTTP_OK);
    }

    #[Route(path: '', methods: ['GET'])]
    public function getUsersAction(): Response
    {
        $users = $this->userManager->getUsers();
        $code = empty($users) ? Response::HTTP_NO_CONTENT : Response::HTTP_OK;

        return new JsonResponse(['users' => array_map(static fn(User $user) => $user->toArray(), $users)], $code);
    }


    #[Route(path: '/{id}', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getUserAction(int $id): Response
    {
        $user = $this->userManager->getUser($id);
        [$data, $code] = $user === null ?
            [null, Response::HTTP_NOT_FOUND] :
            [['user' => $user->toArray()], Response::HTTP_OK];

        return new JsonResponse($data, $code);
    }

    #[Route(path: '', methods: ['DELETE'])]
    public function deleteUserAction(Request $request): Response
    {
        $userId = $request->query->get('userId');
        $result = $this->userManager->deleteUser($userId);

        return new JsonResponse(['success' => $result], $result ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);
    }

    #[Route(path: '', methods: ['PATCH'])]
    public function updateUserAction(Request $request): Response
    {
        $userId = $request->query->get('userId');
        $login = $request->query->get('login');
        $result = $this->userManager->updateUser($userId, $login);

        return new JsonResponse(['success' => $result !== null], ($result !== null) ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);
    }
}
