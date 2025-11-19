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
.post-author {
    font-weight: 600;
    color: #374151;
}
.post-time {
    font-size: 12px;
    color: #9ca3af;
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

<script>
let currentPage = 1;
let isLoading = false;
let hasMore = true;

async function loadPosts() {
    if (isLoading || !hasMore) return;

    console.log('Loading posts, page:', currentPage);
    isLoading = true;

    try {
        const response = await fetch(`/api/posts?page=${currentPage}`);
        console.log('Response received:', response.status);
        const data = await response.json();
        console.log('Data:', data);

        if (!data.success) {
            throw new Error(data.error || 'Failed to load posts');
        }

        if (data.success && data.posts.length > 0) {
            const container = document.getElementById('postsContainer');
            
            // Remove loading message on first load
            if (currentPage === 1) {
                container.innerHTML = '';
            }
            
            data.posts.forEach(post => {
                const postCard = document.createElement('div');
                postCard.className = 'post-card';
                
                const postDate = new Date(post.created_at);
                const timeAgo = getTimeAgo(postDate);
                
                const rawImagePath = post.image_url || post.image_path || '';
                const imagePath = typeof rawImagePath === 'string' ? rawImagePath.trim() : '';
                const imageHtml = imagePath !== ''
                    ? `<div class="post-image"><img src="${escapeHtml(imagePath)}" alt="Post image" loading="lazy"></div>`
                    : '';

                postCard.innerHTML = `
                    <div class="post-header">
                        <span class="post-author">${escapeHtml(post.user_name)}</span>
                        <span class="post-time">${timeAgo}</span>
                    </div>
                    <div class="post-content">${escapeHtml(post.content)}</div>
                    ${imageHtml}
                `;
                

                container.appendChild(postCard);
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
            document.getElementById('postsContainer').innerHTML = '<div class="no-more">No posts yet. Be the first to post!</div>';
        }
    } catch (error) {
        console.error('Error loading posts:', error);
        if (currentPage === 1) {
            document.getElementById('postsContainer').innerHTML = '<div class="message error">Failed to load posts: ' + error.message + '</div>';
        }
    }
    
    isLoading = false;
}

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

// Infinite scroll
window.addEventListener('scroll', () => {
    if ((window.innerHeight + window.scrollY) >= document.body.offsetHeight - 500) {
        console.log('here')
        console.log('window.innerHeight',window.innerHeight)
        console.log('document.body.offsetHeight',document.body.offsetHeight)
        loadPosts();
    }
});

// Load initial posts
loadPosts();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';

