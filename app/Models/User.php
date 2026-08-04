<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class User
{
    public int $id;
    public string $name;
    public string $email;
    public string $passwordHash;
    public int $roleId;
    public string $roleName;
    public bool $isActive;
    public ?string $rememberToken;
    public int $failedLoginCount;
    public ?string $lockedUntil;

    public static function findByEmail(string $email): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.*, r.name AS role_name FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function findById(int $id): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.*, r.name AS role_name FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public static function findByRememberToken(string $tokenHash): ?self
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.*, r.name AS role_name FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.remember_token = :token LIMIT 1'
        );
        $stmt->execute(['token' => $tokenHash]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    public function isLocked(): bool
    {
        return $this->lockedUntil !== null && strtotime($this->lockedUntil) > time();
    }

    public function verifyPassword(string $plain): bool
    {
        return password_verify($plain, $this->passwordHash);
    }

    public function recordSuccessfulLogin(): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users SET failed_login_count = 0, locked_until = NULL, last_login_at = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $this->id]);
    }

    public function recordFailedLogin(int $maxAttempts, int $lockMinutes): void
    {
        $db = Database::connection();
        $count = $this->failedLoginCount + 1;

        $lockedUntil = $count >= $maxAttempts
            ? (new \DateTimeImmutable("+{$lockMinutes} minutes"))->format('Y-m-d H:i:s')
            : null;

        $stmt = $db->prepare(
            'UPDATE users SET failed_login_count = :count, locked_until = :locked_until WHERE id = :id'
        );
        $stmt->execute(['count' => $count, 'locked_until' => $lockedUntil, 'id' => $this->id]);
    }

    public function setRememberToken(?string $tokenHash): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET remember_token = :token WHERE id = :id');
        $stmt->execute(['token' => $tokenHash, 'id' => $this->id]);
        $this->rememberToken = $tokenHash;
    }

    /**
     * @return array{users: self[], total: int}
     */
    public static function search(string $keyword, int $page, int $perPage): array
    {
        $db = Database::connection();
        $offset = max(0, ($page - 1) * $perPage);
        $like = '%' . $keyword . '%';

        // $countStmt = $db->prepare(
        //     'SELECT COUNT(*) FROM users WHERE name LIKE :like OR email LIKE :like'
        // );
        // $countStmt->execute(['like' => $like]);

        $countStmt = $db->prepare(
            'SELECT COUNT(*)
            FROM users
            WHERE name LIKE :name_like
                OR email LIKE :email_like'
        );

        $countStmt->execute([
            'name_like'  => $like,
            'email_like' => $like,
        ]);

        $total = (int) $countStmt->fetchColumn();

        // $stmt = $db->prepare(
        //     'SELECT u.*, r.name AS role_name FROM users u
        //      JOIN roles r ON r.id = u.role_id
        //      WHERE u.name LIKE :like OR u.email LIKE :like
        //      ORDER BY u.name ASC
        //      LIMIT :limit OFFSET :offset'
        // );
        // $stmt->bindValue('like', $like);
        // $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        // $stmt->bindValue('offset', $offset, PDO::PARAM_INT);

        $stmt = $db->prepare(
            'SELECT u.*, r.name AS role_name
            FROM users u
            JOIN roles r ON r.id = u.role_id
            WHERE u.name LIKE :name_like
                OR u.email LIKE :email_like
            ORDER BY u.name ASC
            LIMIT :limit OFFSET :offset'
        );

        $stmt->bindValue(':name_like', $like, PDO::PARAM_STR);
        $stmt->bindValue(':email_like', $like, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();

        return [
            'users' => array_map(self::hydrate(...), $stmt->fetchAll()),
            'total' => $total,
        ];
    }

    public static function emailExists(string $email, ?int $excludingId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE email = :email';
        $params = ['email' => $email];
        if ($excludingId !== null) {
            $sql .= ' AND id != :id';
            $params['id'] = $excludingId;
        }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public static function create(string $name, string $email, string $plainPassword, int $roleId): self
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO users (name, email, password_hash, role_id, is_active) VALUES (:name, :email, :hash, :role_id, 1)'
        );
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'hash' => password_hash($plainPassword, PASSWORD_ARGON2ID),
            'role_id' => $roleId,
        ]);

        return self::findById((int) Database::connection()->lastInsertId());
    }

    public function updateProfile(string $name, string $email): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET name = :name, email = :email WHERE id = :id');
        $stmt->execute(['name' => $name, 'email' => $email, 'id' => $this->id]);
        $this->name = $name;
        $this->email = $email;
    }

    public function updateRole(int $roleId): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET role_id = :role_id WHERE id = :id');
        $stmt->execute(['role_id' => $roleId, 'id' => $this->id]);
        $this->roleId = $roleId;
    }

    public function updatePassword(string $plainPassword): void
    {
        $hash = password_hash($plainPassword, PASSWORD_ARGON2ID);
        $stmt = Database::connection()->prepare(
            'UPDATE users SET password_hash = :hash, failed_login_count = 0, locked_until = NULL WHERE id = :id'
        );
        $stmt->execute(['hash' => $hash, 'id' => $this->id]);
        $this->passwordHash = $hash;
    }

    public function setActive(bool $active): void
    {
        $stmt = Database::connection()->prepare('UPDATE users SET is_active = :active WHERE id = :id');
        $stmt->execute(['active' => $active ? 1 : 0, 'id' => $this->id]);
        $this->isActive = $active;
    }

    public function unlock(): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE users SET failed_login_count = 0, locked_until = NULL WHERE id = :id'
        );
        $stmt->execute(['id' => $this->id]);
    }

    public function hasTransactions(): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM transactions WHERE created_by = :id');
        $stmt->execute(['id' => $this->id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    /** @throws \RuntimeException if the user still owns transactions (delete would violate the FK) */
    public function delete(): void
    {
        if ($this->hasTransactions()) {
            throw new \RuntimeException(
                'This user still has recorded transactions and cannot be permanently deleted. Deactivate the account instead.'
            );
        }
        $stmt = Database::connection()->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute(['id' => $this->id]);
    }

    public static function countActiveAdmins(): int
    {
        $stmt = Database::connection()->query(
            "SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id
             WHERE r.name = 'admin' AND u.is_active = 1"
        );
        return (int) $stmt->fetchColumn();
    }

    private static function hydrate(array $row): self
    {
        $user = new self();
        $user->id = (int) $row['id'];
        $user->name = $row['name'];
        $user->email = $row['email'];
        $user->passwordHash = $row['password_hash'];
        $user->roleId = (int) $row['role_id'];
        $user->roleName = $row['role_name'];
        $user->isActive = (bool) $row['is_active'];
        $user->rememberToken = $row['remember_token'];
        $user->failedLoginCount = (int) $row['failed_login_count'];
        $user->lockedUntil = $row['locked_until'];
        return $user;
    }
}
