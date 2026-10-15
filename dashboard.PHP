<?php
session_start();
require_once __DIR__ . '/config.php';

$pageTitle = "Dashboard - CyberGames";

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare('SELECT id, username, email, permission FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $_SESSION['user_id']]);
$user = $stmt->fetch();

if ($user === false) {
    header('Location: logout.php');
    exit;
}

include __DIR__ . '/includes/header.php';
?>

<main class="dashboard-content">
    <h1>Bienvenue, <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?> !</h1>
    <p>Bienvenue sur votre espace CyberGames.</p>

    <section class="dashboard-card">
        <h2>Mes informations</h2>
        <dl class="account-details">
            <dt>ID utilisateur</dt>
            <dd><?= (int) $user['id'] ?></dd>
            <dt>Nom d'utilisateur</dt>
            <dd><?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?></dd>
            <dt>Email</dt>
            <dd><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></dd>
            <dt>Permission</dt>
            <dd><?= (int) $user['permission'] === 2 ? 'Administrateur' : 'Utilisateur' ?></dd>
        </dl>
    </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
