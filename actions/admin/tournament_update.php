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
$validation = validateTournamentInput($_POST);
if ($id === false) {
    $error = 'Tournoi invalide.';
} elseif (isset($validation['error'])) {
    $error = $validation['error'];
} else {
    try {
        $pdo->beginTransaction();
        $data = $validation['data'];
        $data['id'] = $id;
        $lock = $pdo->prepare('SELECT id FROM tournois WHERE id = :id FOR UPDATE');
        $lock->execute([':id' => $id]);
        if ($lock->fetchColumn() === false) {
            $error = 'Tournoi introuvable.';
        }
        $participantCount = $pdo->prepare('SELECT COUNT(*) FROM tournois_participants WHERE tournoi_id = :id');
        $participantCount->execute([':id' => $id]);
        $currentParticipantCount = $participantCount->fetchColumn();
        if (!isset($error) && (int) $data['capacite_max'] < (int) $currentParticipantCount) {
            $error = 'La capacité ne peut pas être inférieure au nombre de participants déjà inscrits.';
        } elseif (!isset($error)) {
            $stmt = $pdo->prepare(
                'UPDATE tournois
                 SET nom = :nom, jeu = :jeu, description = :description, regles = :regles,
                     date_debut = :date_debut, capacite_max = :capacite_max,
                     inscription_debut = :inscription_debut, inscription_fin = :inscription_fin
                 WHERE id = :id'
            );
            $stmt->execute($data);
            if ($stmt->rowCount() === 0) {
                $exists = $pdo->prepare('SELECT 1 FROM tournois WHERE id = :id');
                $exists->execute([':id' => $id]);
                if ($exists->fetchColumn() === false) {
                    $error = 'Tournoi introuvable.';
                }
            }
        }
        if (isset($error)) {
            $pdo->rollBack();
        } else {
            $pdo->commit();
        }
    } catch (PDOException $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log($exception->getMessage());
        $error = 'Erreur lors de la modification du tournoi.';
    }
}

if (isset($error)) {
    header('Location: ../../admin.php?status=error&message=' . rawurlencode($error) . '#crud-tournois');
    exit;
}

header('Location: ../../admin.php?status=success&message=' . rawurlencode('Tournoi modifié.') . '#crud-tournois');
exit;
