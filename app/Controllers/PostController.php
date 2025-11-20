<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\Post;

class PostController extends Controller {
    public function index() {
        $user = Session::get('user');
        if (!$user) {
            header('Location: /login');
            exit;
        }
        $this->view('posts.php', ['user' => $user]);
    }

    public function create() {
        $user = Session::get('user');
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $content = trim($_POST['content'] ?? '');
        

        if (strlen($content) < 1) {
            Session::set('error', 'Post content cannot be empty.');
            header('Location: /posts');
            exit;
        }

        try {
            $imagePath = $this->handleImageUpload($_FILES['image'] ?? null);
        } catch (\RuntimeException $e) {
            Session::set('error', $e->getMessage());
            header('Location: /posts');
            return;
        }

        Post::create($user['id'], $content, $imagePath);
        Session::set('success', 'Post created successfully!');
        header('Location: /posts');
    }

    private function handleImageUpload(?array $file): ?string {
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Failed to upload image. Please try again.');
        }

        if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
            throw new \RuntimeException('Image is too large. Maximum size is 5MB.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? finfo_file($finfo, $file['tmp_name']) : null;
        if ($finfo) {
            finfo_close($finfo);
        }

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];

        if (!$mimeType || !array_key_exists($mimeType, $allowed)) {
            throw new \RuntimeException('Unsupported image type. Please upload JPG, PNG, GIF, or WEBP files.');
        }

        $rootDir = dirname(__DIR__, 2);
        $primaryUploadDir = $rootDir . '/uploads';
        if (!is_dir($primaryUploadDir) && !mkdir($primaryUploadDir, 0755, true) && !is_dir($primaryUploadDir)) {
            throw new \RuntimeException('Unable to create uploads directory.');
        }

        $filename = uniqid('post_', true) . '.' . $allowed[$mimeType];
        $primaryDestination = $primaryUploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $primaryDestination)) {
            throw new \RuntimeException('Failed to save uploaded image.');
        }

        // Mirror into public/uploads for setups that expose the public directory as the web root
        $publicUploadDir = $rootDir . '/public/uploads';
        if (!is_dir($publicUploadDir)) {
            mkdir($publicUploadDir, 0755, true);
        }
        if (is_dir($publicUploadDir)) {
            @copy($primaryDestination, $publicUploadDir . '/' . $filename);
        }

        // Store a normalized web path; the API will expand it to include any base path
        return 'uploads/' . $filename;
    }

    public function getPosts() {
        header('Content-Type: application/json');

        try {
            $user = Session::get('user');
            if (!$user) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                return;
            }

            $page = (int)($_GET['page'] ?? 1);
            $limit = 10;
            $offset = ($page - 1) * $limit;

            $posts = Post::getAll($limit, $offset, (int)$user['id']);

            foreach ($posts as &$post) {
                $path = trim((string)($post['image_path'] ?? ''));
                if ($path !== '') {
                    $post['image_url'] = url($path);
                }
                $post['is_following'] = (bool)($post['is_following'] ?? false);
            }
            unset($post);

            echo json_encode([
                'success' => true,
                'posts' => $posts,
                'hasMore' => count($posts) === $limit
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function update() {
        header('Content-Type: application/json');

        $user = Session::get('user');
        if (!$user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $postId = (int)($_POST['id'] ?? 0);
        $content = trim($_POST['content'] ?? '');

        if ($postId < 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid post ID']);
            return;
        }

        if (strlen($content) < 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Post content cannot be empty.']);
            return;
        }

        $post = Post::find($postId);
        if (!$post) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Post not found']);
            return;
        }

        if ((int)$post['user_id'] !== (int)$user['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'You are not allowed to edit this post.']);
            return;
        }

        $imagePath = $post['image_path'] ?? null;

        try {
            $newImage = $this->handleImageUpload($_FILES['image'] ?? null);
            if ($newImage !== null) {
                $imagePath = $newImage;
            }
        } catch (\RuntimeException $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            return;
        }

        Post::update($postId, (int)$user['id'], $content, $imagePath);

        $updatedPost = Post::findWithUser($postId);
        if ($updatedPost && isset($updatedPost['image_path']) && trim((string)$updatedPost['image_path']) !== '') {
            $updatedPost['image_url'] = url($updatedPost['image_path']);
        }

        echo json_encode([
            'success' => true,
            'post' => $updatedPost
        ]);
    }
        public function delete() {
        header('Content-Type: application/json');

        $user = Session::get('user');
        if (!$user) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $postId = (int)($_POST['id'] ?? 0);
        if ($postId < 1) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid post ID']);
            return;
        }

        $post = Post::find($postId);
        if (!$post) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Post not found']);
            return;
        }

        if ((int)$post['user_id'] !== (int)$user['id']) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'You are not allowed to delete this post.']);
            return;
        }

        $imagePath = $post['image_path'] ?? '';

        if (!Post::delete($postId, (int)$user['id'])) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Failed to delete post. Please try again.']);
            return;
        }

        $this->removeImageFiles($imagePath);

        echo json_encode(['success' => true]);
    }

    private function removeImageFiles(?string $imagePath): void {
        $normalizedPath = trim((string)$imagePath);
        if ($normalizedPath === '') {
            return;
        }

        $rootDir = dirname(__DIR__, 2);
        $paths = [
            $rootDir . '/' . ltrim($normalizedPath, '/'),
            $rootDir . '/public/' . ltrim($normalizedPath, '/')
        ];

        foreach ($paths as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
}