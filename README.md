Metro Web Board (metro_wb_lab)
=================================

Small PHP web application for a simple posts/dashboard system used for coursework.

Requirements
------------
- PHP 7.4+ (or compatible)
- Composer
- MySQL (or MariaDB)
- Optional: XAMPP/WAMP for Apache + PHP

Quick setup
-----------
1. Install PHP dependencies (if any):

```bash
composer install
```

2. Create a database and import schema + seed data:

Windows (MySQL):

```powershell
mysql -u root -p < sql/schema.sql
mysql -u root -p < sql/seed_posts.sql
```

Adjust the DB user/password as needed in your environment.

Running locally (built-in PHP server)
-----------------------------------
From the project root run (serves `public/`):

```bash
php -S localhost:8000 -t public
```

Then open http://localhost:8000 in your browser.

If you use XAMPP/WAMP/Apache, put the project `public/` as the document root (or configure a virtual host) and start Apache/MySQL from the control panel.

Stopping the server
-------------------
- If you started the built-in PHP server in a terminal, stop it with Ctrl+C.
- If you closed the terminal, find the process by port and kill it (Windows):

```powershell
netstat -ano | findstr :8000
taskkill /PID <PID> /F
# or kill all php processes
taskkill /IM php.exe /F
```

Features (implemented)
----------------------
- User authentication: register (sign up) and login with session support. See `app/Controllers/AuthController.php` and views in `app/Views/auth/`.
- Posts: authenticated users can create posts and see a posts listing. See `app/Controllers/PostController.php` and `app/Models/Post.php`.
- Like / Unlike: users can like or unlike posts (toggle behavior implemented in controllers/models).
- Follow / Unfollow: users can follow and unfollow other users; follow relationships are tracked in the DB.

Database and seeds
------------------
- Schema file: `sql/schema.sql` (includes tables for users, posts, likes, follows).
- Example seed data: `sql/seed_posts.sql`.

Important files
---------------
- Front controller: [public/index.php](public/index.php)
- App code: [app/](app/)
- Controllers: [app/Controllers/](app/Controllers/)
- Models: [app/Models/](app/Models/)
- Views: [app/Views/](app/Views/)
- Mailer helper: [app/Core/Mailer.php](app/Core/Mailer.php)
- Static assets: [public/assets/](public/assets/)

Usage notes
-----------
- Open the app in a browser after starting the server and register a new user to test posting, liking, and following flows.
- Static assets are in `public/assets/` and project-level `assets/`.
- Mailer: outgoing email (if configured) is handled by `app/Core/Mailer.php`.
