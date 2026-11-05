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

$validation = validateTournamentInput($_POST);
if (isset($validation['error'])) {
    redirectWithMessage('../../admin.php#crud-tournois', 'error', $validation['error']);
}

$data = $validation['data'];
try {
    $stmt = $pdo->prepare(
        'INSERT INTO tournois
            (nom, jeu, description, regles, date_debut, capacite_max, inscription_debut, inscription_fin, statut, created_by)
         VALUES
            (:nom, :jeu, :description, :regles, :date_debut, :capacite_max, :inscription_debut, :inscription_fin, :statut, :created_by)'
    );
    $data['statut'] = 'a_venir';
    $data['created_by'] = (int) $_SESSION['user_id'];
    $stmt->execute($data);
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    redirectWithMessage('../../admin.php#crud-tournois', 'error', 'Erreur lors de la création du tournoi.');
}

redirectWithMessage('../../admin.php#crud-tournois', 'success', 'Tournoi créé.');
