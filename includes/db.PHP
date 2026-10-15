<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

$page = basename($_SERVER['PHP_SELF']);
$isAdmin = false;

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT permission FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $permission = $stmt->fetchColumn();

    if ($permission === false) {
        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['permission']);
    } else {
        $_SESSION['permission'] = (int) $permission;
        $isAdmin = $_SESSION['permission'] === 2;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= $pageTitle ?? 'CyberGames' ?></title>

    <link rel="stylesheet" href="includes/css/headerStyle.css">

</head>

<body>
<header>
    <nav>
        <ul>
            <li><a href="index.php">Menu</a></li>
            
            <?php if (!isset($_SESSION['user_id'])): ?>
                <?php
                #Attention si le nom de page change probleme
                if ($page !== 'register.php') {
                    echo '<li><a href="register.php">Créer un compte</a></li>';
                }

                #Attention si le nom de page change probleme
                if ($page !== 'login.php') {
                    echo '<li><a href="login.php">Se connecter</a></li>';
                }
                ?>
            <?php else: ?>
                <li><a href="dashboard.php">Dashboard</a></li>
                <?php if ($isAdmin) {
                    echo '<li><a href="admin.php">Administration</a></li>';
                } ?>
                <li><a href="logout.php">Se déconnecter</a></li>
            <?php endif; ?>
        </ul>
    </nav>
</header>
