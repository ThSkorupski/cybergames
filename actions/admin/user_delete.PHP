<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$id = (int) ($_POST['id'] ?? 0);

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

header('Location: ../../admin.php?status=' . $status . '&message=' . rawurlencode($message) . '#crud-users');
exit;