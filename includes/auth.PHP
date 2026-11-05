<?php
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/request.php';
startAppSession();

require_once __DIR__ . '/../config.php'; // $pdo

$isAdmin = false;

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare('SELECT username, permission FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $sessionUser = $stmt->fetch();

    if ($sessionUser === false) {
        unset($_SESSION['user_id'], $_SESSION['username'], $_SESSION['permission']);
    } else {
        $_SESSION['username'] = $sessionUser['username'];
        $_SESSION['permission'] = (int) $sessionUser['permission'];
        $isAdmin = $_SESSION['permission'] === 2;
    }
}

// Fonction à appeler en haut d'une page réservée aux admins
function requireAdmin(): void
{
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
    if (($_SESSION['permission'] ?? 0) !== 2) {
        http_response_code(403);
        exit('Accès refusé');
    }
}