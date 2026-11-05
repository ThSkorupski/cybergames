<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php'; 
$page = basename($_SERVER['PHP_SELF']);

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle ?? 'CyberGames') ?></title>

    <link rel="stylesheet" href="includes/css/headerStyle.css">
    <?php foreach ($extraCss ?? [] as $stylesheet): ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($stylesheet, ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
    <?php foreach ($extraJs ?? [] as $script): ?>
        <script src="<?= htmlspecialchars($script, ENT_QUOTES, 'UTF-8') ?>" defer></script>
    <?php endforeach; ?>
</head>

<body>
<header>
    <nav>
        <ul>
            <li><a href="index.php">Menu</a></li>

            <?php if (!isset($_SESSION['user_id'])): ?>
                <?php if ($page !== 'register.php'): ?>
                    <li><a href="register.php">Créer un compte</a></li>
                <?php endif; ?>
                <?php if ($page !== 'login.php'): ?>
                    <li><a href="login.php">Se connecter</a></li>
                <?php endif; ?>
            <?php else: ?>
                <li><a href="dashboard.php">Dashboard</a></li>
                <?php if ($isAdmin): ?> <!--$isAdmin est une variable de auth.php -->
                    <li><a href="admin.php">Administration</a></li>
                <?php endif; ?>
                <li class="user-name">
                    <?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?>
                </li>
                <li>
                    <form class="logout-form" method="post" action="logout.php">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit">Se déconnecter</button>
                    </form>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</header>