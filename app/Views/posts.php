<?php
use App\Core\Session;
$title = 'Posts | AuthBoard';
ob_start();
?>

<style>
.post-form {
    background: #f9fafb;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 24px;
    border: 1px solid #e5e7eb;
}
.post-form textarea {
    width: 100%;
    min-height: 100px;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-family: inherit;
    font-size: 14px;
    resize: vertical;
    box-sizing: border-box;
}
.post-form button {
    margin-top: 12px;
}
.post-form .file-input {
    margin-top: 12px;
}
.posts-container {
    display: flex;
    flex-direction: column;
    gap: 16px;
}
.post-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 16px;
    transition: box-shadow 0.2s;
}
.post-card:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}
.post-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}
.post-meta {
    display: flex;
    align-items: center;
    gap: 12px;
}
.follow-btn {
    background: #10b981;
    padding: 6px 10px;
    font-size: 13px;
}
.follow-btn.following {
    background: #e5e7eb;
    color: #374151;
    border: 1px solid #d1d5db;
}
.follow-btn.following:hover {
    background: #d1d5db;
}
.post-author {
    font-weight: 600;
    color: #374151;
}
.post-time {
    font-size: 12px;
    color: #9ca3af;
}
.post-actions {
    display: flex;
    gap: 8px;
}
.post-actions button {
    background: #2563eb;
    color: #fff;
    border: none;
    padding: 6px 10px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
}
.post-actions button:hover {
    background: #1e40af;
}
.post-actions .delete-btn {
    background: #dc2626;
}
.post-actions .delete-btn:hover {
    background: #b91c1c;
}
.post-footer {
    display: flex;
    align-items: center;
    margin-top: 12px;
}
.like-btn {
    background: #f3f4f6;
    color: #111827;
    border: 1px solid #e5e7eb;
    padding: 6px 10px;
    border-radius: 6px;
    cursor: pointer;
    font-size: 13px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}
.like-btn .like-count {
    font-weight: 600;
    color: #111827;
}
.like-btn.liked {
    background: #fee2e2;
    border-color: #fecaca;
    color: #b91c1c;
}
.like-btn:hover {
    background: #e5e7eb;
}
.like-btn.liked:hover {
    background: #fecaca;
}
.post-content {
    color: #1f2937;
    line-height: 1.6;
    white-space: pre-wrap;
    word-wrap: break-word;
}
.post-image {
    margin-top: 12px;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    overflow: hidden;
    background: #f8fafc;
}
.post-image img {
    display: block;
    width: 100%;
    height: auto;
}
.loading {
    text-align: center;
    padding: 20px;
    color: #6b7280;
}
.no-more {
    text-align: center;
    padding: 20px;
    color: #9ca3af;
    font-size: 14px;
}
.message {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 16px;
}
.message.success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #6ee7b7;
}
.message.error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}
.modal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 1000;
    padding: 16px;
}
.modal.open {
    display: flex;
}
.modal-content {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    width: min(480px, 100%);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}
.modal-content h3 {
    margin-top: 0;
    margin-bottom: 12px;
}
.modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    margin-top: 12px;
}
.modal-actions .secondary {
    background: #e5e7eb;
    color: #374151;
}
.modal .message {
    margin-bottom: 12px;
}
.current-image {
    margin-top: 8px;
    color: #6b7280;
    font-size: 13px;
}
</style>

<?php if (Session::get('success')): ?>
    <div class="message success">
        <?= htmlspecialchars(Session::get('success')) ?>
        <?php Session::remove('success'); ?>
    </div>
<?php endif; ?>

<?php if (Session::get('error')): ?>
    <div class="message error">
        <?= htmlspecialchars(Session::get('error')) ?>
        <?php Session::remove('error'); ?>
    </div>
<?php endif; ?>

<h2>Posts</h2>

<div class="post-form">
    <form method="POST" action="/posts" id="postForm" enctype="multipart/form-data">
        <textarea name="content" placeholder="What's on your mind, <?= htmlspecialchars($user['name']) ?>?" required></textarea>
        <input type="file" name="image" accept="image/*">
        <button type="submit">Post</button>
    </form>
</div>

<div class="posts-container" id="postsContainer">
    <div class="loading">Loading posts...</div>
</div>
<div class="modal" id="editModal">
    <div class="modal-content">
        <h3>Edit post</h3>
        <div class="message error" id="editError" style="display: none;"></div>
        <form id="editForm">
            <input type="hidden" name="id" id="editPostId">
            <textarea name="content" id="editContent" required></textarea>
            <input type="file" name="image" accept="image/*">
            <div class="current-image" id="currentImageInfo"></div>
            <div class="modal-actions">
                <button type="button" class="secondary" id="cancelEdit">Cancel</button>
                <button type="submit">Save changes</button>
            </div>
        </form>
    </div>
</div>

<script>
const currentUserId = <?= (int)$user['id'] ?>;
let currentPage = 1;
let isLoading = false;
let hasMore = true;
const postsById = new Map();

const editModal = document.getElementById('editModal');
const editForm = document.getElementById('editForm');
const editContent = document.getElementById('editContent');
const editPostId = document.getElementById('editPostId');
const editError = document.getElementById('editError');
const currentImageInfo = document.getElementById('currentImageInfo');
const cancelEditBtn = document.getElementById('cancelEdit');
const postsContainer = document.getElementById('postsContainer');
const followStateByUser = new Map();

function generatePostHtml(post, timeAgo, imageHtml, actionsHtml, followButtonHtml, likeButtonHtml) {
    return `
        <div class="post-header">
            <div class="post-meta">
                <span class="post-author">${escapeHtml(post.user_name)}</span>
                ${followButtonHtml}
            </div>
            <div style="display: flex; align-items: center; gap: 8px;">
                <span class="post-time">${timeAgo}</span>
                ${actionsHtml}
            </div>
        </div>
        <div class="post-content">${escapeHtml(post.content)}</div>
        ${imageHtml}
        <div class="post-footer">
            ${likeButtonHtml}
        </div>
    `;
}

function attachEditHandler(card, post) {
    const editBtn = card.querySelector('.edit-btn');
    if (editBtn) {
        editBtn.addEventListener('click', () => openEditDialog(post.id));
    }
}

function attachDeleteHandler(card, post) {
    const deleteBtn = card.querySelector('.delete-btn');
    if (!deleteBtn) return;

    deleteBtn.addEventListener('click', async () => {
        const confirmed = window.confirm('Delete this post? This action cannot be undone.');
        if (!confirmed) return;

        try {
            const formData = new FormData();
            formData.append('id', post.id);

            const response = await fetch('/posts/delete', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.error || 'Failed to delete post');
            }

            postsById.delete(post.id);
            card.remove();
            showEmptyStateIfNeeded();
        } catch (error) {
            window.alert('Failed to delete post: ' + error.message);
        }
    });
}

function getFollowingState(userId, defaultState = false) {
    if (!followStateByUser.has(userId)) {
        followStateByUser.set(userId, Boolean(defaultState));
    }

    return followStateByUser.get(userId);
}

function setFollowingState(userId, isFollowing) {
    followStateByUser.set(userId, isFollowing);

    document.querySelectorAll(`.follow-btn[data-user-id="${userId}"]`).forEach(button => {
        button.dataset.following = isFollowing ? 'true' : 'false';
        button.textContent = isFollowing ? 'Unfollow' : 'Follow';
        button.classList.toggle('following', isFollowing);
    });
}

function buildLikeButton(post) {
    const liked = Boolean(post.is_liked);
    const count = Number.isFinite(post.like_count) ? post.like_count : 0;
    const icon = liked ? '♥' : '♡';
    const label = liked ? 'Unlike' : 'Like';

    return `<button type="button" class="like-btn${liked ? ' liked' : ''}" data-post-id="${post.id}" data-liked="${liked ? 'true' : 'false'}"><span class="like-icon">${icon}</span> <span class="like-label">${label}</span> · <span class="like-count">${count}</span></button>`;
}

function setLikeState(postId, isLiked, likeCount) {
    const post = postsById.get(postId);
    if (post) {
        post.is_liked = isLiked;
        post.like_count = likeCount;
    }

    document.querySelectorAll(`.like-btn[data-post-id="${postId}"]`).forEach(button => {
        button.dataset.liked = isLiked ? 'true' : 'false';
        button.classList.toggle('liked', isLiked);
        const icon = button.querySelector('.like-icon');
        const label = button.querySelector('.like-label');
        const count = button.querySelector('.like-count');
        if (icon) icon.textContent = isLiked ? '♥' : '♡';
        if (label) label.textContent = isLiked ? 'Unlike' : 'Like';
        if (count) count.textContent = likeCount;
    });
}

function attachLikeHandler(card, post) {
    const likeBtn = card.querySelector('.like-btn');
    if (!likeBtn) return;

    likeBtn.addEventListener('click', async () => {
        const isLiked = likeBtn.dataset.liked === 'true';
        const endpoint = isLiked ? '/posts/unlike' : '/posts/like';

        const formData = new FormData();
        formData.append('id', post.id);

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.error || 'Unable to update like');
            }

            setLikeState(post.id, data.is_liked, data.like_count);
        } catch (error) {
            window.alert('Failed to update like: ' + error.message);
        }
    });
}

function buildFollowButton(userId, isFollowing = false) {
    const following = getFollowingState(userId, isFollowing);
    const followingClass = following ? ' following' : '';
    const label = following ? 'Unfollow' : 'Follow';

    return `<button type="button" class="follow-btn${followingClass}" data-user-id="${userId}" data-following="${following ? 'true' : 'false'}">${label}</button>`;
}

function attachFollowHandler(card, post) {
    const followBtn = card.querySelector('.follow-btn');
    if (!followBtn) return;

    followBtn.addEventListener('click', async () => {
        const targetUserId = parseInt(followBtn.dataset.userId, 10);
        const isCurrentlyFollowing = followBtn.dataset.following === 'true';
        const endpoint = isCurrentlyFollowing ? '/users/unfollow' : '/users/follow';

        const formData = new FormData();
        formData.append('user_id', targetUserId);

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (!data.success) {
                throw new Error(data.error || 'Unable to update follow status');
            }

            setFollowingState(targetUserId, data.is_following);
        } catch (error) {
            window.alert('Failed to update follow status: ' + error.message);
        }
    });
}

function attachPostActions(card, post) {
    attachEditHandler(card, post);
    attachDeleteHandler(card, post);
    attachFollowHandler(card, post);
    attachLikeHandler(card, post);
}

function buildPostCard(post) {
    postsById.set(post.id, post);
    const postCard = document.createElement('div');
    postCard.className = 'post-card';
    postCard.dataset.postId = post.id;
    postCard.dataset.authorId = post.user_id;

    const postDate = new Date(post.created_at);
    const timeAgo = getTimeAgo(postDate);

    const rawImagePath = (post.image_url || post.image_path || '').trim();
    const imageHtml = rawImagePath !== ''
        ? `<div class="post-image"><img src="${escapeHtml(rawImagePath)}" alt="Post image" loading="lazy"></div>`
        : '';

    const followButtonHtml = post.user_id === currentUserId
        ? ''
        : buildFollowButton(post.user_id, post.is_following);

    const likeButtonHtml = buildLikeButton(post);

    const actionsHtml = post.user_id === currentUserId
        ? `<div class="post-actions"><button type="button" class="edit-btn" data-post-id="${post.id}">Edit</button><button type="button" class="delete-btn" data-post-id="${post.id}">Delete</button></div>`
        : '';
    postCard.innerHTML = generatePostHtml(post, timeAgo, imageHtml, actionsHtml, followButtonHtml, likeButtonHtml);
    attachPostActions(postCard, post);
    return postCard;
}

function updatePostCard(post) {
    const card = document.querySelector(`[data-post-id="${post.id}"]`);
    if (!card) return;
    const newCard = buildPostCard(post);
    card.replaceWith(newCard);
}

async function loadPosts() {
    if (isLoading || !hasMore) return;

    isLoading = true;

    try {
        const response = await fetch(`/api/posts?page=${currentPage}`);
        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Failed to load posts');
        }

        if (data.posts.length > 0) {
            const container = document.getElementById('postsContainer');

            if (currentPage === 1) {
                container.innerHTML = '';
            }
            

            data.posts.forEach(post => {
                const postCard = buildPostCard(post);
                container.appendChild(postCard);
                setFollowingState(post.user_id, getFollowingState(post.user_id, post.is_following));
            });
            

            hasMore = data.hasMore;
            currentPage++;
            

            if (!hasMore) {
                const noMore = document.createElement('div');
                noMore.className = 'no-more';
                noMore.textContent = 'No more posts';
                container.appendChild(noMore);
            }
        } else if (currentPage === 1) {
            postsContainer.innerHTML = '<div class="no-more">No posts yet. Be the first to post!</div>';
        }
    } catch (error) {
        console.error('Error loading posts:', error);
        if (currentPage === 1) {
            postsContainer.innerHTML = '<div class="message error">Failed to load posts: ' + error.message + '</div>';
        }
    }
    

    isLoading = false;
}

function showEditError(message) {
    editError.textContent = message;
    editError.style.display = 'block';
}

function clearEditError() {
    editError.textContent = '';
    editError.style.display = 'none';
}

function openEditDialog(postId) {
    const post = postsById.get(postId);
    if (!post) return;

    clearEditError();
    editForm.reset();
    editPostId.value = post.id;
    editContent.value = post.content;

    const rawImagePath = (post.image_url || post.image_path || '').trim();
    currentImageInfo.textContent = rawImagePath !== ''
        ? `Current image will be kept unless you upload a new one.`
        : '';

    editModal.classList.add('open');
    editContent.focus();
}

function closeEditDialog() {
    editModal.classList.remove('open');
    editForm.reset();
    clearEditError();
}

editForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    clearEditError();

    const formData = new FormData(editForm);

    try {
        const response = await fetch('/posts/update', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.error || 'Failed to update post');
        }

        if (data.post) {
            updatePostCard(data.post);
        }

        closeEditDialog();
    } catch (error) {
        showEditError(error.message);
    }
});

cancelEditBtn.addEventListener('click', () => closeEditDialog());
editModal.addEventListener('click', (event) => {
    if (event.target === editModal) {
        closeEditDialog();
    }
});


function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function getTimeAgo(date) {
    const seconds = Math.floor((new Date() - date) / 1000);
    

    if (seconds < 60) return 'just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
    if (seconds < 604800) return Math.floor(seconds / 86400) + ' days ago';
    

    return date.toLocaleDateString();
}

function showEmptyStateIfNeeded() {
    const hasPosts = postsContainer.querySelector('.post-card');
    if (!hasPosts) {
        postsContainer.innerHTML = '<div class="no-more">No posts yet. Be the first to post!</div>';
    }
}

// Infinite scroll
window.addEventListener('scroll', () => {
    if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 500) {
        loadPosts();
    }
});

// Load initial posts
loadPosts();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';

