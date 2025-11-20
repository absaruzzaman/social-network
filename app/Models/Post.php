<?php
namespace App\Models;

use PDO;

class Post {
    /**
     * Cached flag to avoid repeatedly checking the posts table structure.
     */
    private static ?bool $hasImagePathColumn = null;

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

    private static function postsTableHasImagePath(PDO $pdo): bool {
        if (self::$hasImagePathColumn !== null) {
            return self::$hasImagePathColumn;
        }

        $stmt = $pdo->prepare("SHOW COLUMNS FROM posts LIKE 'image_path'");
        $stmt->execute();
        self::$hasImagePathColumn = (bool)$stmt->fetch();

        return self::$hasImagePathColumn;
    }

    public static function create(int $userId, string $content, ?string $imagePath = null): int {
        $pdo = self::connect();

        if (self::postsTableHasImagePath($pdo)) {
            $stmt = $pdo->prepare('INSERT INTO posts (user_id, content, image_path) VALUES (?, ?, ?)');
            $stmt->execute([$userId, $content, $imagePath ?? '']);
        } else {
            $stmt = $pdo->prepare('INSERT INTO posts (user_id, content) VALUES (?, ?)');
            $stmt->execute([$userId, $content]);
        }

        return (int)$pdo->lastInsertId();
    }

    public static function getAll(int $limit = 10, int $offset = 0, ?int $currentUserId = null): array {
        $pdo = self::connect();
        $stmt = $pdo->prepare('
            SELECT
                p.*,
                u.name as user_name,
                CASE WHEN f.follower_id IS NULL THEN 0 ELSE 1 END AS is_following,
                COALESCE(l.like_count, 0) AS like_count,
                CASE WHEN ul.user_id IS NULL THEN 0 ELSE 1 END AS is_liked
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN follows f ON f.following_id = p.user_id AND f.follower_id = :current_user_id
            LEFT JOIN (
                SELECT post_id, COUNT(*) AS like_count
                FROM likes
                GROUP BY post_id
            ) l ON l.post_id = p.id
            LEFT JOIN likes ul ON ul.post_id = p.id AND ul.user_id = :current_user_id
            ORDER BY p.created_at DESC
            LIMIT :limit OFFSET :offset
        ');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->bindValue(':current_user_id', $currentUserId, $currentUserId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array {
        $pdo = self::connect();
        $stmt = $pdo->prepare('SELECT * FROM posts WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        $post = $stmt->fetch();
        return $post !== false ? $post : null;
    }

    public static function findWithUser(int $id, ?int $currentUserId = null): ?array {
        $pdo = self::connect();
        $stmt = $pdo->prepare('
            SELECT p.*, u.name as user_name
            SELECT
                p.*,
                u.name as user_name,
                COALESCE(l.like_count, 0) AS like_count,
                CASE WHEN ul.user_id IS NULL THEN 0 ELSE 1 END AS is_liked
            FROM posts p
            JOIN users u ON p.user_id = u.id
            LEFT JOIN (
                SELECT post_id, COUNT(*) AS like_count
                FROM likes
                GROUP BY post_id
            ) l ON l.post_id = p.id
            LEFT JOIN likes ul ON ul.post_id = p.id AND ul.user_id = :current_user_id
            WHERE p.id = :id
            LIMIT 1
        ');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':current_user_id', $currentUserId, $currentUserId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->execute();

        $post = $stmt->fetch();
        return $post !== false ? $post : null;
    }

    public static function update(int $id, int $userId, string $content, ?string $imagePath = null): bool {
        $pdo = self::connect();

        if (self::postsTableHasImagePath($pdo)) {
            $stmt = $pdo->prepare('UPDATE posts SET content = :content, image_path = :image_path WHERE id = :id AND user_id = :user_id');
            $stmt->bindValue(':image_path', $imagePath ?? '');
        } else {
            $stmt = $pdo->prepare('UPDATE posts SET content = :content WHERE id = :id AND user_id = :user_id');
        }

        $stmt->bindValue(':content', $content);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }
    
    public static function delete(int $id, int $userId): bool {
        $pdo = self::connect();
        $stmt = $pdo->prepare('DELETE FROM posts WHERE id = :id AND user_id = :user_id');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public static function like(int $postId, int $userId): bool {
        $pdo = self::connect();
        $stmt = $pdo->prepare('
            INSERT INTO likes (post_id, user_id)
            VALUES (:post_id, :user_id)
            ON DUPLICATE KEY UPDATE created_at = VALUES(created_at)
        ');
        $stmt->bindValue(':post_id', $postId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public static function unlike(int $postId, int $userId): bool {
        $pdo = self::connect();
        $stmt = $pdo->prepare('DELETE FROM likes WHERE post_id = :post_id AND user_id = :user_id');
        $stmt->bindValue(':post_id', $postId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount() > 0;
    }

    public static function getLikeSummary(int $postId, ?int $userId = null): array {
        $pdo = self::connect();
        $stmt = $pdo->prepare('
            SELECT
                COUNT(*) AS like_count,
                SUM(CASE WHEN user_id = :user_id THEN 1 ELSE 0 END) AS user_like_count
            FROM likes
            WHERE post_id = :post_id
        ');
        $stmt->bindValue(':post_id', $postId, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $userId, $userId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetch();

        return [
            'like_count' => (int)($result['like_count'] ?? 0),
            'is_liked' => ((int)($result['user_like_count'] ?? 0)) > 0,
        ];
    }
}