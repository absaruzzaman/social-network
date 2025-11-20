<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Core\Session;
use App\Models\User;

class UserController extends Controller {
    public function follow() {
        $this->handleFollowToggle(true);
    }

    public function unfollow() {
        $this->handleFollowToggle(false);
    }

    private function handleFollowToggle(bool $shouldFollow): void {
        header('Content-Type: application/json');

        $currentUser = Session::get('user');
        if (!$currentUser) {
            http_response_code(401);
            echo json_encode(['success' => false, 'error' => 'Unauthorized']);
            return;
        }

        $targetUserId = (int)($_POST['user_id'] ?? 0);
        if ($targetUserId < 1 || $targetUserId === (int)$currentUser['id']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid user selection.']);
            return;
        }

        $targetUser = User::findById($targetUserId);
        if (!$targetUser) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'User not found.']);
            return;
        }

        $result = $shouldFollow
            ? User::follow((int)$currentUser['id'], $targetUserId)
            : User::unfollow((int)$currentUser['id'], $targetUserId);

        if (!$result) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unable to update follow state.']);
            return;
        }

        echo json_encode([
            'success' => true,
            'is_following' => $shouldFollow
        ]);
    }
}