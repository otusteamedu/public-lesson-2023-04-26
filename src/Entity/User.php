<?php

namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Table(name: '`user`')]
#[ORM\Entity]
#[Gedmo\SoftDeleteable(fieldName: 'deletedAt')]
class User
{
    #[ORM\Column(name: 'id', type: 'bigint', unique: true)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 32, nullable: false)]
    private string $login;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    #[Gedmo\Timestampable(on: 'create')]
    private DateTime $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: false)]
    #[Gedmo\Timestampable(on: 'update')]
    private DateTime $updatedAt;

    #[ORM\OneToMany(mappedBy: 'follower', targetEntity: 'Subscription')]
    private Collection $authors;

    #[ORM\OneToMany(mappedBy: 'author', targetEntity: 'Subscription')]
    #[ORM\OrderBy(['order' => 'asc'])]
    private Collection $followers;

    #[ORM\Column(name: 'deleted_at', type: 'datetime', nullable: true)]
    private ?DateTime $deletedAt;

    public function __construct()
    {
        $this->authors = new ArrayCollection();
        $this->followers = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function setLogin(string $login): void
    {
        $this->login = $login;
    }

    public function getCreatedAt(): DateTime {
        return $this->createdAt;
    }

    public function setCreatedAt(): void {
        $this->createdAt = new DateTime();
    }

    public function getUpdatedAt(): DateTime {
        return $this->updatedAt;
    }

    public function setUpdatedAt(): void {
        $this->updatedAt = new DateTime();
    }

    public function getDeletedAt(): ?DateTime
    {
        return $this->deletedAt;
    }

    public function setDeletedAt(?DateTime $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
    }

    public function addAuthor(Subscription $subscription): void
    {
        if (!$this->authors->contains($subscription)) {
            $this->authors->add($subscription);
        }
    }

    public function removeAuthor(Subscription $subscription): void
    {
        if ($this->authors->contains($subscription)) {
            $this->authors->removeElement($subscription);
        }
    }

    public function addFollower(Subscription $subscription): void
    {
        if (!$this->followers->contains($subscription)) {
            $this->followers->add($subscription);
        }
    }

    public function removeFollower(Subscription $subscription): void
    {
        if ($this->followers->contains($subscription)) {
            $this->followers->removeElement($subscription);
        }
    }

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
}
