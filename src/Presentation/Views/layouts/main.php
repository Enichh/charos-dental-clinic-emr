<?php declare(strict_types=1); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Charos Dental Clinic EMR' ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <?php if (isset($customCss)): ?>
    <link rel="stylesheet" href="<?= $customCss ?>">
    <?php endif; ?>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="/" class="nav-brand">Charos Dental Clinic</a>
            <ul class="nav-menu">
                <li><a href="/appointments">Appointments</a></li>
                <li><a href="/prescriptions">Prescriptions</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="/auth/logout">Logout</a></li>
                <?php else: ?>
                    <li><a href="/auth/login">Login</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <main class="main-content">
        <?php if (isset($flashMessage)): ?>
        <div class="flash-message <?= $flashType ?? 'info' ?>">
            <?= $flashMessage ?>
        </div>
        <?php endif; ?>

        <?= $content ?>
    </main>

    <footer class="footer">
        <p>&copy; <?= date('Y') ?> Charos Dental Clinic EMR System</p>
    </footer>

    <script src="/js/main.js"></script>
    <?php if (isset($customJs)): ?>
    <script src="<?= $customJs ?>"></script>
    <?php endif; ?>
</body>
</html>
