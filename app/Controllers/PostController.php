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
            $page = (int)($_GET['page'] ?? 1);
            $limit = 10;
            $offset = ($page - 1) * $limit;

            $posts = Post::getAll($limit, $offset);

            foreach ($posts as &$post) {
                $path = trim((string)($post['image_path'] ?? ''));
                if ($path !== '') {
                    $post['image_url'] = url($path);
                }
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
}