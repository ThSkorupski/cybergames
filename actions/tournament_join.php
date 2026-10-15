<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/tournaments.php';

#pas login on renvoie sur la page de login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée.');
}
#protection csrf
if (!isValidCsrfToken($_POST['csrf_token'] ?? '')) {
    http_response_code(400);
    exit('Jeton de sécurité invalide. Rechargez la page et réessayez.');
}

#renvoie sur la page d'accueil si le tournoi est invalide
$tournamentId = filter_var($_POST['tournament_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($tournamentId === false) {
    header('Location: ../index.php?status=error&message=' . rawurlencode('Tournoi invalide.'));
    exit;
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare(
        'SELECT id, regles, capacite_max,
                inscription_debut <= CURRENT_TIMESTAMP AS registration_started,
                inscription_fin > CURRENT_TIMESTAMP AS registration_open,
                statut
         FROM tournois WHERE id = :id FOR UPDATE'
    );
    $stmt->execute([':id' => $tournamentId]);
    $tournament = $stmt->fetch();

    if ($tournament === false) {
        throw new DomainException('Tournoi introuvable.');
    }
    if ((int) $tournament['registration_started'] !== 1) {
        throw new DomainException('Les inscriptions à ce tournoi ne sont pas encore ouvertes.');
    }
    if ((int) $tournament['registration_open'] !== 1 || in_array($tournament['statut'], ['termine', 'annule'], true)) {
        throw new DomainException('Les inscriptions à ce tournoi sont terminées.');
    }
    if ($tournament['regles'] !== null && $tournament['regles'] !== '' && ($_POST['requirements_confirmed'] ?? '') !== '1') {
        throw new DomainException('Vous devez confirmer respecter les conditions de participation.');
    }

    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM tournois_participants WHERE tournoi_id = :id');
    $countStmt->execute([':id' => $tournamentId]);
    if ((int) $countStmt->fetchColumn() >= (int) $tournament['capacite_max']) {
        throw new DomainException('Ce tournoi est complet.');
    }

    $insert = $pdo->prepare(
        'INSERT INTO tournois_participants (tournoi_id, user_id) VALUES (:tournoi_id, :user_id)'
    );
    $insert->execute([
        ':tournoi_id' => $tournamentId,
        ':user_id' => (int) $_SESSION['user_id'],
    ]);
    $pdo->commit();
} catch (DomainException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    header('Location: ../index.php?status=error&message=' . rawurlencode($exception->getMessage()));
    exit;
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if (($exception->errorInfo[1] ?? null) === 1062) {
        header('Location: ../index.php?status=error&message=' . rawurlencode('Vous êtes déjà inscrit à ce tournoi.'));
        exit;
    }
    error_log($exception->getMessage());
    http_response_code(500);
    exit('Une erreur est survenue lors de l’inscription au tournoi.');
}

header('Location: ../index.php?status=success&message=' . rawurlencode('Inscription au tournoi confirmée.'));
exit;
