<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/tournaments.php';

$currentUserId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$stmt = $pdo->prepare(
    'SELECT t.id, t.nom, t.jeu, t.description, t.regles, t.date_debut, t.capacite_max,
            t.inscription_debut, t.inscription_fin, t.statut,
            COUNT(tp.user_id) AS participant_count,
            MAX(CASE WHEN tp.user_id = :user_id THEN 1 ELSE 0 END) AS is_joined,
            t.inscription_debut <= CURRENT_TIMESTAMP AS registration_open
     FROM tournois t
     LEFT JOIN tournois_participants tp ON tp.tournoi_id = t.id
     WHERE t.inscription_fin > CURRENT_TIMESTAMP
       AND t.statut NOT IN (\'termine\', \'annule\')
     GROUP BY t.id
     ORDER BY t.date_debut ASC'
);
$stmt->execute([':user_id' => $currentUserId]);
$tournaments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pastStmt = $pdo->prepare(
    'SELECT t.id, t.nom, t.jeu, t.date_debut,
            MAX(CASE WHEN tp.user_id = :user_id THEN 1 ELSE 0 END) AS is_joined
     FROM tournois t
     LEFT JOIN tournois_participants tp ON tp.tournoi_id = t.id
     WHERE (t.date_debut <= CURRENT_TIMESTAMP OR t.statut = \'termine\')
       AND t.statut <> \'annule\'
     GROUP BY t.id
     ORDER BY t.date_debut DESC'
);
$pastStmt->execute([':user_id' => $currentUserId]);
$pastTournaments = $pastStmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Menu - CyberGames';
include __DIR__ . '/includes/header.php';
?>

<main class="home-content">
    <h1>CyberGames</h1>
    <p>Retrouve ici les prochains événements de la communauté.</p>

    <section class="tournaments-card">
        <h2>Prochains tournois</h2>
        <?php if (isset($_GET['status'], $_GET['message'])): ?>
            <p class="tournament-message <?= $_GET['status'] === 'error' ? 'error' : 'success' ?>" role="status">
                <?= htmlspecialchars($_GET['message'], ENT_QUOTES, 'UTF-8') ?>
            </p>
        <?php endif; ?>
        <div class="tournament-list">
            <?php if ($tournaments === []): ?>
                <p>Aucun tournoi n’est actuellement ouvert aux inscriptions.</p>
            <?php endif; ?>
            <?php foreach ($tournaments as $tournament): ?>
                <?php
                $isFull = (int) $tournament['participant_count'] >= (int) $tournament['capacite_max'];
                $isJoined = (int) $tournament['is_joined'] === 1;
                $registrationOpen = (int) $tournament['registration_open'] === 1;
                ?>
                <article class="tournament-item">
                    <div class="tournament-details">
                        <h3><?= htmlspecialchars($tournament['nom'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="tournament-game"><?= htmlspecialchars($tournament['jeu'], ENT_QUOTES, 'UTF-8') ?></p>
                        <?php if ($tournament['description'] !== null && $tournament['description'] !== ''): ?>
                            <p><?= nl2br(htmlspecialchars($tournament['description'], ENT_QUOTES, 'UTF-8')) ?></p>
                        <?php endif; ?>
                        <p>
                            Tournoi le <?= htmlspecialchars(formatTournamentDate($tournament['date_debut'], 'd/m/Y H:i'), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <p>
                            Inscriptions du <?= htmlspecialchars(formatTournamentDate($tournament['inscription_debut'], 'd/m/Y H:i'), ENT_QUOTES, 'UTF-8') ?>
                            au <?= htmlspecialchars(formatTournamentDate($tournament['inscription_fin'], 'd/m/Y H:i'), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <p>Places : <?= (int) $tournament['participant_count'] ?> / <?= (int) $tournament['capacite_max'] ?></p>
                        <?php if ($tournament['regles'] !== null && $tournament['regles'] !== ''): ?>
                            <p><strong>Conditions :</strong> <?= nl2br(htmlspecialchars($tournament['regles'], ENT_QUOTES, 'UTF-8')) ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="tournament-action">
                        <?php if ($isJoined): ?>
                            <span class="tournament-status">Déjà inscrit</span>
                        <?php elseif ($isFull): ?>
                            <span class="tournament-status">Complet</span>
                        <?php elseif (!$registrationOpen): ?>
                            <span class="tournament-status">Inscriptions bientôt ouvertes</span>
                        <?php elseif ($currentUserId === 0): ?>
                            <a class="join-button" href="login.php">Se connecter pour rejoindre</a>
                        <?php else: ?>
                            <form method="post" action="actions/tournament_join.php">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="tournament_id" value="<?= (int) $tournament['id'] ?>">
                                <?php if ($tournament['regles'] !== null && $tournament['regles'] !== ''): ?>
                                    <label class="requirements-confirmation">
                                        <input type="checkbox" name="requirements_confirmed" value="1" required>
                                        Je confirme respecter les conditions
                                    </label>
                                <?php endif; ?>
                                <button class="join-button" type="submit">Rejoindre</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="tournaments-card">
        <h2>Tournois passés</h2>
        <div class="tournament-list">
            <?php if ($pastTournaments === []): ?>
                <p>Aucun tournoi passé pour le moment.</p>
            <?php endif; ?>
            <?php foreach ($pastTournaments as $tournament): ?>
                <article class="tournament-item">
                    <div class="tournament-details">
                        <h3><?= htmlspecialchars($tournament['nom'], ENT_QUOTES, 'UTF-8') ?></h3>
                        <p class="tournament-game"><?= htmlspecialchars($tournament['jeu'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p>
                            Tournoi du <?= htmlspecialchars(formatTournamentDate($tournament['date_debut'], 'd/m/Y H:i'), ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                    <div class="tournament-action">
                        <?php if ($currentUserId === 0): ?>
                            <a class="join-button" href="login.php">Se connecter pour vérifier sa participation</a>
                        <?php elseif ((int) $tournament['is_joined'] === 1): ?>
                            <span class="tournament-status">Tu y as participé</span>
                        <?php else: ?>
                            <span class="tournament-status">Tu n’y as pas participé</span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>
