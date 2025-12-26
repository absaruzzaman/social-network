<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title><?= $title ?? 'Social Network' ?></title>
    <link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="container">
    <header>
        <h1>Social Network</h1>
        <?php if (!empty($_SESSION['user'])): ?>
            <nav><a href="/dashboard">Dashboard</a> | <a href="/posts">Posts</a> | <a href="/logout">Logout</a></nav>
        <?php endif; ?>
    </header>

    <main>
        <?php echo $content ; ?>
    </main>

    <footer>
        <small>Social Network - A demo work</small>
    </footer>
</div>
</body>
</html>
