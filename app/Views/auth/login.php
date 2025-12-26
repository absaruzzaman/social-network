<?php
$title = 'Login | Social Network';
ob_start();
?>
<h2>Login</h2>
<form method="POST" action="/login" class="form">
    <label>Email</label>
    <input type="email" name="email" required />
    <label>Password</label>
    <input type="password" name="password" required />
    <button type="submit">Login</button>
</form>
<p>Don't have an account? <a href="/register">Register</a></p>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
