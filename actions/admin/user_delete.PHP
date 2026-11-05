<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}
if (!isValidCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(400);
    exit('Jeton de sécurité invalide. Rechargez la page et réessayez.');
}

$id = (int) inputString($_POST, 'id');

if ($id === (int) $_SESSION['user_id']) {
    $status = 'error';
    $message = 'Tu ne peux pas te supprimer toi-même.';
} elseif ($id > 0) {
    $targetStmt = $pdo->prepare('SELECT permission FROM users WHERE id = :id LIMIT 1');
    $targetStmt->execute([':id' => $id]);
    $targetPermission = $targetStmt->fetchColumn();

    if ($targetPermission === false) {
        $status = 'error';
        $message = 'Utilisateur introuvable.';
    } elseif ((int) $targetPermission === 2) {
        $status = 'error';
        $message = "Tu ne peux pas supprimer le compte d'un autre administrateur.";
    } else {
        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $status = 'success';
        $message = 'Utilisateur supprimé.';
    }
} else {
    $status = 'error';
    $message = 'Utilisateur invalide.';
}

redirectWithMessage('../../admin.php#crud-users', $status, $message);