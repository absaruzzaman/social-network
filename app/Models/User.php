<?php
namespace App\Models;

use PDO;

class User {
    private static function connect(): PDO {
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $db = getenv('DB_NAME') ?: 'metro_web_class';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    }

    public static function findByEmail(string $email): ?array {
        $stmt = self::connect()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findById(int $id): ?array {
        $stmt = self::connect()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $name, string $email, string $password): int {
        $stmt = self::connect()->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
        $stmt->execute([$name, $email, $password]);
        return (int)self::connect()->lastInsertId();
    }

    public static function isFollowing(int $followerId, int $followingId): bool {
        $stmt = self::connect()->prepare('SELECT 1 FROM follows WHERE follower_id = ? AND following_id = ? LIMIT 1');
        $stmt->execute([$followerId, $followingId]);
        return (bool)$stmt->fetchColumn();
    }

    public static function follow(int $followerId, int $followingId): bool {
        if ($followerId === $followingId) {
            return false;
        }

        $stmt = self::connect()->prepare('INSERT IGNORE INTO follows (follower_id, following_id) VALUES (?, ?)');
        $stmt->execute([$followerId, $followingId]);

        return $stmt->rowCount() > 0 || self::isFollowing($followerId, $followingId);
    }

    public static function unfollow(int $followerId, int $followingId): bool {
        if ($followerId === $followingId) {
            return false;
        }

        $stmt = self::connect()->prepare('DELETE FROM follows WHERE follower_id = ? AND following_id = ?');
        $stmt->execute([$followerId, $followingId]);

        return $stmt->rowCount() > 0 || !self::isFollowing($followerId, $followingId);
    }
}