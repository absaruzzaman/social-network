<?php
use App\Core\Session;
$title = 'Dashboard | Social Network';
ob_start();
?>

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

<h2>Welcome, <?php echo  htmlspecialchars($user['name']) ?></h2>
<p>Your email: <?= htmlspecialchars($user['email']) ?></p>

<div style="margin-top: 24px; padding: 20px; background: #f9fafb; border-radius: 8px; border: 1px solid #e5e7eb;">
    <h3 style="margin-top: 0; font-size: 16px; color: #374151;">📧 Email Testing</h3>
    <p style="font-size: 14px; color: #6b7280; margin-bottom: 16px;">
        Test your Mailtrap integration by sending a test email to <strong><?= htmlspecialchars($user['email']) ?></strong>
    </p>
    <a href="/test-mail" 
       style="display: inline-block; padding: 10px 20px; background: #10b981; color: white; text-decoration: none; border-radius: 6px; font-size: 14px; font-weight: 500;"
       onclick="return confirm('Send a test email to <?= htmlspecialchars($user['email']) ?>?');">
        📨 Send Test Email
    </a>
    <p style="font-size: 12px; color: #9ca3af; margin-top: 12px; margin-bottom: 0;">
        💡 Check your <a href="https://mailtrap.io/inboxes" target="_blank" style="color: #2563eb;">Mailtrap inbox</a> to see the email.
    </p>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout.php';
