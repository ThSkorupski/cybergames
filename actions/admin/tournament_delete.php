<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/tournaments.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../login.php');
    exit;
}
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}
if (!isValidCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(400);
    exit('Jeton de sécurité invalide. Rechargez la page et réessayez.');
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    header('Location: ../../admin.php?status=error&message=' . rawurlencode('Tournoi invalide.') . '#crud-tournois');
    exit;
}

try {
    $stmt = $pdo->prepare('DELETE FROM tournois WHERE id = :id');
    $stmt->execute([':id' => $id]);
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    header('Location: ../../admin.php?status=error&message=' . rawurlencode('Erreur lors de la suppression du tournoi.') . '#crud-tournois');
    exit;
}

$message = $stmt->rowCount() > 0 ? 'Tournoi supprimé.' : 'Tournoi introuvable.';
$status = $stmt->rowCount() > 0 ? 'success' : 'error';
header('Location: ../../admin.php?status=' . $status . '&message=' . rawurlencode($message) . '#crud-tournois');
exit;
