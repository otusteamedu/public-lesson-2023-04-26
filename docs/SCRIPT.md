# Doctrine-extensions-bundle

## Готовим проект

1. Запускаем контейнеры командой `docker-compose up -d`
2. Входим в контейнер командой `docker exec -it php sh`. Дальнейшие команды будем выполнять из контейнера
3. Устанавливаем зависимости командой `composer install`
4. Выполняем миграции комадной `php bin/console doctrine:migrations:migrate`

## Проверяем работоспособность приложения

1. Выполняем запрос Add user из Postman-коллекции, получаем успешный ответ.
2. Выполняем запрос Get user list из Postman-коллекции, видим, что поля createdAt и updatedAt заполнены одинаковыми
   значениями.
3. Выполняем запрос Update user из Postman-коллекции, получаем успешный ответ.
4. Ещё раз выполняем запрос Get user list из Postman-коллекции, видим, что обновились значения полей login и updatedAt.

## Устанавливаем doctrine-extensions-bundle и используем расширение timestampable

1. Устанавливаем пакет командой `composer require stof/doctrine-extensions-bundle`
2. Включаем использование расширения `timestampable`:
3. В файле `config/packages/stof_doctrine_extensions.yaml` в секцию `stof_doctrine_extensions` добавляем
    ```yaml
    orm:
        default:
            timestampable: true      
    ```
4. В классе `App\Entity\User`
   1. Импортируем класс `Gedmo\Mapping\Annotation` как `Gedmo` 
   2. добавляем для полей `createdAt` и `updatedAt` атрибуты:
       ```php
       #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
       #[Gedmo\Timestampable(on: 'create')]
       private DateTime $createdAt;

       #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: false)]
       #[Gedmo\Timestampable(on: 'update')]
       private DateTime $updatedAt;
       ```
5. В классе `App\Manager\UserManager`
   1. Исправляем метод `createUser`:
       ```php
       public function createUser(string $login): User
       {
           $user = new User();
           $user->setLogin($login);
           $this->entityManager->persist($user);
           $this->entityManager->flush();

           return $user;
       }
       ```
   2. Исправляем метод `updateUser`
       ```php
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
       ```
6. Выполняем запрос Add user из Postman-коллекции, получаем успешный ответ.
7. Выполняем запрос Get user list из Postman-коллекции, видим, что поля createdAt и updatedAt заполнены одинаковыми
   значениями.
8. Выполняем запрос Update user из Postman-коллекции, получаем успешный ответ.
9. Ещё раз выполняем запрос Get user list из Postman-коллекции, видим, что обновились значения полей login и updatedAt.

## Используем расширение soft-deleteable

1. Включаем использование расширения `timestampable`:
   1. В файле `config/packages/stof_doctrine_extensions.yaml` в секцию `stof_doctrine_extensions.orm.default` добавляем
       ```yaml
       softdeleteable: true      
       ```
   2. В файле `config/packages/doctrine.yaml` в секцию `doctrine.orm` добавляем
       ```yaml
       filters:
           softdeleteable:
               class: Gedmo\SoftDeleteable\Filter\SoftDeleteableFilter
               enabled: true
       ```
2. В классе `App\Entity\User`
   1. Добавляем к классу атрибут `#[Gedmo\SoftDeleteable(fieldName: 'deletedAt')]`
   2. Добавляем поле, геттер и сеттер
       ```php
       #[ORM\Column(name: 'deleted_at', type: 'datetime', nullable: true)]
       private ?DateTime $deletedAt;
       
       public function getDeletedAt(): ?DateTime
       {
           return $this->deletedAt;
       }

       public function setDeletedAt(?DateTime $deletedAt): void
       {
           $this->deletedAt = $deletedAt;
       }
       ```
3. Генерируем миграции в связи с изменением структуры БД командой `php bin/console doctrine:migrations:diff`
4. Применяем миграции командой `php bin/console doctrine:migrations:migrate`
5. Выполняем запрос Delete user из Postman-коллекции. Видим, что в БД заполнилось поле `deletedAt` для соответствующей
   записи
6. Выполняем запрос Get users list из Postman-коллекции. Видим, что удалённая запись не возвращается.

## Проверяем работу фильтра при использовании QueryBuilder

1. В классе `App\Manager\UserManager` добавим метод `getUserWithQueryBuilder`
    ```php
    public function getUserWithQueryBuilder(int $userId): ?User
    {
        $qb = $this->entityManager->createQueryBuilder();
        
        $qb->select('u')
            ->from(User::class, 'u')
            ->where($qb->expr()->eq('u.id', ':id'))
            ->setParameter('id', $userId);
        
        return $qb->getQuery()->getOneOrNullResult();
    }    
    ```
2. В классе `App\Controller\Api\v1\UserController` исправляем метод `getUserAction`
    ```php
    #[Route(path: '/{id}', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getUserAction(int $id): Response
    {
        $user = $this->userManager->getUserWithQueryBuilder($id);
        [$data, $code] = $user === null ?
            [null, Response::HTTP_NOT_FOUND] :
            [['user' => $user->toArray()], Response::HTTP_OK];

        return new JsonResponse($data, $code);
    }   
    ```
3. Выполняем запрос Get user из Postman-коллекции с идентификатором удалённого пользователя. Видим пустой ответ, т.е.
   фильтр применился и работает.

## Отключаем фильтр

1. В классе `App\Manager\UserManager` добавляем метод `getUserIgnoreDeleted`
    ```php
    public function getUserIgnoreDeleted(int $userId): ?User
    {
        $filters = $this->entityManager->getFilters();
        $filters->disable('softdeleteable');
        $userRepository = $this->entityManager->getRepository(User::class);

        return $userRepository->find($userId);
    }
    ```
2. В классе `App\Controller\Api\v1\UserController` исправляем метод `getUserAction`
    ```php
    #[Route(path: '/{id}', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function getUserAction(int $id): Response
    {
        $user = $this->userManager->getUserIgnoreDeleted($id);
        [$data, $code] = $user === null ?
            [null, Response::HTTP_NOT_FOUND] :
            [['user' => $user->toArray()], Response::HTTP_OK];

        return new JsonResponse($data, $code);
    }   
    ```
3. Выполняем запрос Get user из Postman-коллекции с идентификатором удалённого пользователя. Видим удалённую запись.

## Проверяем работу hard-delete

1. В классе `App\Manager\UserManager` добавляем метод `hardDeleteUser`
    ```php
    public function hardDeleteUser(int $userId): bool
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        /** @var User $user */
        $user = $userRepository->find($userId);
        if ($user === null) {
            return false;
        }
        $this->entityManager->remove($user);
        $this->entityManager->flush();
        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return true;
    }   
    ```
2. В классе `App\Controller\Api\v1\UserController` исправляем метод `deleteUserAction`
    ```php
    #[Route(path: '', methods: ['DELETE'])]
    public function deleteUserAction(Request $request): Response
    {
        $userId = $request->query->get('userId');
        $result = $this->userManager->hardDeleteUser($userId);

        return new JsonResponse(['success' => $result], $result ? Response::HTTP_OK : Response::HTTP_BAD_REQUEST);
    } 
    ```
3. Выполняем запрос Delete user из Postman-коллекции для ещё не удалённой записи.
4. Выполняем запрос Get user из Postman-коллекции с идентификатором удалённого пользователя. Видим пустой ответ, т.е.
   запись удалена полностью.

## Проверяем работу подписок

1. Выполняем запрос Add subscription из Postman-коллекции для двух неудалённых записей.
2. Выполняем запрос Get user list из Postman-коллекции, видим у связанных записей непустые массивы `authors` /
   `followers`.
3. Выполняем запрос Delete subscription из Postman-коллекции для тех же записей.
4. Выполняем запрос Get user list из Postman-коллекции, видим у этих записей пустые массивы `authors` / `followers`.

## Добавляем использование расширения sortable

1. Включаем использование расширения `sortable`:
   1. В файле `config/packages/stof_doctrine_extensions.yaml` в секцию `stof_doctrine_extensions.orm.default` добавляем
       ```yaml
       sortable: true      
       ``` 
2. В классе `App\Entity\Subscription`
   1. Импортируем класс `Gedmo\Mapping\Annotation` как `Gedmo`
   2. Добавляем новое поле, геттеры и сеттеры
       ```php
       #[ORM\Column(name: '`order`', type: 'integer')]
       #[Gedmo\SortablePosition]
       private ?int $order;

       public function getOrder(): ?int
       {
           return $this->order;
       }

       public function setOrder(?int $order): void
       {
           $this->order = $order;
       }
       ```
3. В классе `App\Manager\SubscriptionManager` исправляем метод `createSubscription`
    ```php
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
    ```
4. В классе `App\Service\SubscriptionService` исправляем метод `createSubscription`
    ```php
    public function createSubscription(int $authorId, int $followerId, ?int $order): ?Subscription
    {
        $author = $this->userManager->getUser($authorId);
        $follower = $this->userManager->getUser($followerId);
        if ($author === null || $follower === null) {
            return null;
        }

        return $this->subscriptionManager->createSubscription($author, $follower, $order);
    }
    ```
5. В классе `App\Controller\Api\v1\SubscriptionController` исправляем метод `createSubscriptionAction`
    ```php
    #[Route(path: '', methods: ['POST'])]
    public function createSubscriptionAction(Request $request): Response
    {
        $authorId = $request->request->get('authorId');
        $followerId = $request->request->get('followerId');
        $order = $request->request->get('order');
        $subscription = $this->subscriptionService->createSubscription($authorId, $followerId, $order);
        $code = $subscription === null ? Response::HTTP_BAD_REQUEST : Response::HTTP_OK;

        return new JsonResponse(['success' => $subscription !== null], $code);
    }
    ```
6. В классе `App\Entity\User` исправляем метод `toArray`
    ```php
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'login' => $this->login,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
            'followers' => array_map(
                static fn(Subscription $subscription) => [
                    'subscriptionId' => $subscription->getId(),
                    'userId' => $subscription->getFollower()->getId(),
                    'login' => $subscription->getFollower()->getLogin(),
                    'order' => $subscription->getOrder(),
                ],
                $this->followers->toArray()
            ),
            'authors' => array_map(
                static fn(Subscription $subscription) => [
                    'subscriptionId' => $subscription->getId(),
                    'userId' => $subscription->getAuthor()->getId(),
                    'login' => $subscription->getAuthor()->getLogin(),
                    'order' => $subscription->getOrder(),
                ],
                $this->authors->toArray()
            ),
        ];
    }
    ```
7. Генерируем миграции в связи с изменением структуры БД командой `php bin/console doctrine:migrations:diff`
8. Применяем миграции командой `php bin/console doctrine:migrations:migrate`
9. Несколько раз выполняем запрос Add user из Postman-коллекции, чтобы была возможность добавить записи в разном
   порядке.
10. Выполняем запрос Add subscription из Postman-коллекции для одного и того же значения поля `authorId`, разными
    значениями поля `followerId` и со следущими значениями поля `order`: не задано (не пустое!), 0, не задано, 1
11. Выполняем запрос Get user из Postman-коллекции с идентификатором, равным значению поля `authorId` из предыдущего
    пункта. Видим, что записи возвращаются в произвольном порядке.
12. В классе `App\Entity\User` добавляем к полю `followers` атрибут `#[ORM\OrderBy(['order' => 'asc'])]`
13. Ещё раз выполняем запрос Get user из Postman-коллекции. Видим, что записи возвращаются в правильном порядке.
14. Выполняем ещё раз запрос Add subscription из Postman-коллекции для другого значения `authorId` и `order` = 0.
15. Ещё раз выполняем запрос Get user из Postman-коллекции. Видим, что у всех записей `order` увеличился на 1.

## Добавляем группировку сортировки

1. В классе `App\Entity\Subscription` добавляем к полю `author` атрибут `#[Gedmo\SortableGroup]`
2. Выполняем запрос Add subscription из Postman-коллекции для того же значения `authorId`, что и в предыдущий раз и
   `order` = 0.
3. Выполняем запрос Get user из Postman-коллекции. Видим, что `order` у записей не изменился.
