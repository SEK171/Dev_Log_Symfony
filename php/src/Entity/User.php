<?php
namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'accounts')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, nullable: false)]
    private ?string $username = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $password = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: false)]
    private ?\DateTime $registered = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: false)]
    private ?int $permission = null;

    // These collections are just for convenience in code - data is still in separate tables
    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'assignedBy')]
    private Collection $assignedTasks;

    #[ORM\OneToMany(targetEntity: Task::class, mappedBy: 'assignedTo')]
    private Collection $receivedTasks;

    #[ORM\OneToMany(targetEntity: Notification::class, mappedBy: 'user', cascade: ['remove'])]
    private Collection $notifications;

    public function __construct()
    {
        $this->assignedTasks = new ArrayCollection();
        $this->receivedTasks = new ArrayCollection();
        $this->notifications = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function getRoles(): array
    {
        $roles = ['ROLE_USER']; // guarantee every user at least has ROLE_USER
        
        // Add ROLE_ADMIN based on permission level (0 = admin)
        if ($this->permission === 0) {
            $roles[] = 'ROLE_ADMIN';
        }

        return array_unique($roles);
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // clear any stored sensitive info here if needed
        // $this->plainPassword = null;
    }

    public function getRegistered(): ?\DateTime
    {
        return $this->registered;
    }

    public function setRegistered(\DateTime $registered): static
    {
        $this->registered = $registered;
        return $this;
    }

    public function getPermission(): ?int
    {
        return $this->permission;
    }

    public function setPermission(int $permission): static
    {
        $this->permission = $permission;
        return $this;
    }

    public function getAssignedTasks(): Collection
    {
        return $this->assignedTasks;
    }

    public function getReceivedTasks(): Collection
    {
        return $this->receivedTasks;
    }

    public function getNotifications(): Collection
    {
        return $this->notifications;
    }
}