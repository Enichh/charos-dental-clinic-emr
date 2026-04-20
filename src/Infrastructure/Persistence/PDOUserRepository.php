<?php

namespace CharosEMR\Infrastructure\Persistence;

use CharosEMR\Domain\User\Entities\User;
use CharosEMR\Domain\User\Enums\UserRole;
use CharosEMR\Domain\User\Repositories\UserRepositoryInterface;
use PDO;

class PDOUserRepository implements UserRepositoryInterface
{
    public function __construct(private PDO $pdo) {}

    public function save(User $user): void
    {
        if ($user->getId() === null) {
            $stmt = $this->pdo->prepare(
                "INSERT INTO users (email, password_hash, role, is_active) 
                 VALUES (:email, :password_hash, :role, :is_active)"
            );
            $stmt->execute([
                ':email' => $user->getEmail(),
                ':password_hash' => $user->getPasswordHash(),
                ':role' => $user->getRole()->value,
                ':is_active' => $user->isActive() ? 1 : 0
            ]);
            $user->setId((int) $this->pdo->lastInsertId());
        } else {
            $stmt = $this->pdo->prepare(
                "UPDATE users SET email = :email, password_hash = :password_hash, 
                 role = :role, is_active = :is_active, last_login = :last_login WHERE id = :id"
            );
            $stmt->execute([
                ':id' => $user->getId(),
                ':email' => $user->getEmail(),
                ':password_hash' => $user->getPasswordHash(),
                ':role' => $user->getRole()->value,
                ':is_active' => $user->isActive() ? 1 : 0,
                ':last_login' => $user->getLastLogin() ? $user->getLastLogin()->format('Y-m-d H:i:s') : null
            ]);
        }
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $data = $stmt->fetch();

        if ($data === false) {
            return null;
        }

        return $this->hydrateUser($data);
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $data = $stmt->fetch();

        if ($data === false) {
            return null;
        }

        return $this->hydrateUser($data);
    }

    public function findByRole(string $role): array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE role = :role");
        $stmt->execute([':role' => $role]);
        return array_map([$this, 'hydrateUser'], $stmt->fetchAll());
    }

    public function findActive(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM users WHERE is_active = 1");
        return array_map([$this, 'hydrateUser'], $stmt->fetchAll());
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query("SELECT * FROM users");
        return array_map([$this, 'hydrateUser'], $stmt->fetchAll());
    }

    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute([':id' => $id]);
    }

    private function hydrateUser(array $data): User
    {
        return new User(
            (int) $data['id'],
            $data['email'],
            $data['password_hash'],
            UserRole::from($data['role']),
            (bool) $data['is_active'],
            new \DateTime($data['created_at']),
            new \DateTime($data['updated_at']),
            $data['last_login'] ? new \DateTime($data['last_login']) : null
        );
    }
}
