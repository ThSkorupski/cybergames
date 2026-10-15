<?php
require_once __DIR__ . '/includes/auth.php'; // check permission et autres variables utiles
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/tournaments.php';
requireAdmin(); // bloque si l'user est pas admin

$users = $pdo->query("SELECT id, username, email, permission FROM users")->fetchAll(PDO::FETCH_ASSOC);
$tournaments = $pdo->query(
    'SELECT t.id, t.nom, t.jeu, t.description, t.regles, t.date_debut, t.capacite_max,
            t.inscription_debut, t.inscription_fin, t.statut, COUNT(tp.user_id) AS participant_count
     FROM tournois t
     LEFT JOIN tournois_participants tp ON tp.tournoi_id = t.id
     GROUP BY t.id
     ORDER BY t.date_debut DESC'
)->fetchAll(PDO::FETCH_ASSOC);
$editUser = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    foreach ($users as $user) {
        if ((int) $user['id'] === $editId) {
            $editUser = $user;
            break;
        }
    }
}
$editTournament = null;
if (isset($_GET['edit_tournament'])) {
    $editTournamentId = (int) $_GET['edit_tournament'];
    foreach ($tournaments as $tournament) {
        if ((int) $tournament['id'] === $editTournamentId) {
            $editTournament = $tournament;
            break;
        }
    }
}

// nom de la page
$pageTitle = 'Administration';
// fichiers CSS et JS supplémentaires pour cette page (gerer dans le header)
$extraCss  = ['assets/css/admin.css'];
$extraJs   = ['assets/js/admin.js'];
require __DIR__ . '/includes/header.php';
?>

<!-- bouton qui change la page en fonction de celui selectionner -->
<main>
  <div class="admin-tabs">
    <a href="#users" class="tab">Utilisateurs</a>
    <a href="#crud-users" class="tab">CRUD Utilisateurs</a>
    <a href="#crud-tournois" class="tab">CRUD Tournois</a>
  </div>

  <!--si le bouton selectionner est #users affiche pour la page user -->
  <section id="users" class="panel">
    <h2>Utilisateurs</h2>
    <div class="table-wrapper">
      <table>
    <thead>
      <tr><th>ID</th><th>Nom</th><th>Email</th><th>Permission</th></tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
    <tr>
      <td><?= (int) $u['id'] ?></td>
      <td><?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?></td>
      <td><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
      <td><span class="permission"><?= (int) $u['permission'] ?></span></td>
    </tr>
      <?php endforeach; ?>
    </tbody>
      </table>
    </div>
  </section>

  <section id="crud-users" class="panel">
    <h2>CRUD Utilisateurs</h2>
    <?php if (isset($_GET['status'])): ?>
      <p class="admin-message <?= $_GET['status'] === 'error' ? 'error' : 'success' ?>">
        <?= htmlspecialchars($_GET['message'] ?? '', ENT_QUOTES, 'UTF-8') ?>
      </p>
    <?php endif; ?>
    
    <!-- tableau avec questionnaire pour crée un user-->
    <div class="crud-grid">
      <form method="post" action="actions/admin/user_create.php" class="crud-form">
        <h3>Créer un utilisateur</h3>
        <label for="create-username">Nom d'utilisateur</label>
        <input id="create-username" name="username" type="text" minlength="3" maxlength="50" required>
        <label for="create-email">Email</label>
        <input id="create-email" name="email" type="email" required>
        <label for="create-password">Mot de passe</label>
        <input id="create-password" name="password" type="password" minlength="8" required>
        <label for="create-permission">Permission</label>
        <select id="create-permission" name="permission">
          <option value="1">Utilisateur</option>
          <option value="2">Administrateur</option>
        </select>
        <button type="submit">Créer</button>
      </form>
      
      <!-- tableau qui apparait quand on clique pour modifier un user-->
      <?php if ($editUser !== null): ?>
        <form method="post" action="actions/admin/user_update.php" class="crud-form">
          <h3>Modifier un utilisateur</h3>
          <input type="hidden" name="id" value="<?= (int) $editUser['id'] ?>">
          <label for="edit-username">Nom d'utilisateur</label>
          <input id="edit-username" name="username" type="text" value="<?= htmlspecialchars($editUser['username'], ENT_QUOTES, 'UTF-8') ?>" minlength="3" maxlength="50" required>
          <label for="edit-email">Email</label>
          <input id="edit-email" name="email" type="email" value="<?= htmlspecialchars($editUser['email'], ENT_QUOTES, 'UTF-8') ?>" required>
          <label for="edit-password">Nouveau mot de passe <small>(optionnel)</small></label>
          <input id="edit-password" name="password" type="password" minlength="8">
          <label for="edit-permission">Permission</label>
          <select id="edit-permission" name="permission">
            <option value="1" <?= (int) $editUser['permission'] === 1 ? 'selected' : '' ?>>Utilisateur</option>
            <option value="2" <?= (int) $editUser['permission'] === 2 ? 'selected' : '' ?>>Administrateur</option>
          </select>
          <button type="submit">Enregistrer</button>
          <a class="cancel-link" href="admin.php#crud-users">Annuler</a>
        </form>
      <?php endif; ?>
    </div>

    <!--affiche les utilisateurs avec un bouton pour les modifier / supprimer (n'apparait pas si permission egale ou superieur a la personne connectée) -->
    <h3>Actions utilisateurs</h3>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>ID</th><th>Utilisateur</th><th>Email</th><th>Permission</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?= (int) $u['id'] ?></td>
              <td><?= htmlspecialchars($u['username'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                <span class="permission">
                  <?= (int) $u['permission'] === 2 ? 'Administrateur' : 'Utilisateur' ?>
                </span>
              </td>
              <td class="actions">
                <?php if ((int) $u['permission'] !== 2 || (int) $u['id'] === (int) $_SESSION['user_id']): ?>
                  <a class="button-link" href="admin.php?edit=<?= (int) $u['id'] ?>#crud-users">Modifier</a>
                <?php else: ?>
                  <span class="locked-action">modification interdite</span>
                <?php endif; ?>
                <?php if (
                    (int) $u['id'] !== (int) $_SESSION['user_id']
                    && (int) $u['permission'] !== 2
                ): ?>
                  <form method="post" action="actions/admin/user_delete.php" onsubmit="return confirm('Supprimer cet utilisateur ?');">
                    <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                    <button class="delete-button" type="submit">Supprimer</button>
                  </form>
                <!-- emepeche si l'user a la meme permission-->
                <?php elseif ((int) $u['permission'] === 2): ?>
                  <span class="locked-action">Compte admin protégé</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section id="crud-tournois" class="panel">
    <h2>CRUD Tournois</h2>
    <?php if (isset($_GET['status'], $_GET['message'])): ?>
      <p class="admin-message <?= $_GET['status'] === 'error' ? 'error' : 'success' ?>" role="status">
        <?= htmlspecialchars($_GET['message'], ENT_QUOTES, 'UTF-8') ?>
      </p>
    <?php endif; ?>

    <div class="crud-grid">
      <form method="post" action="actions/admin/tournament_create.php" class="crud-form">
        <h3>Créer un tournoi</h3>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
        <label for="create-tournament-name">Nom du tournoi</label>
        <input id="create-tournament-name" name="nom" type="text" maxlength="100" required>
        <label for="create-tournament-game">Jeu</label>
        <input id="create-tournament-game" name="jeu" type="text" maxlength="100" required>
        <label for="create-tournament-description">Description</label>
        <textarea id="create-tournament-description" name="description" rows="3"></textarea>
        <label for="create-tournament-rules">Règles / conditions de participation</label>
        <textarea id="create-tournament-rules" name="regles" rows="3"></textarea>
        <label for="create-tournament-start">Date et heure du tournoi</label>
        <input id="create-tournament-start" name="date_debut" type="datetime-local" required>
        <label for="create-registration-start">Ouverture des inscriptions</label>
        <input id="create-registration-start" name="inscription_debut" type="datetime-local" required>
        <label for="create-registration-end">Clôture des inscriptions</label>
        <input id="create-registration-end" name="inscription_fin" type="datetime-local" required>
        <label for="create-tournament-capacity">Capacité maximale</label>
        <input id="create-tournament-capacity" name="capacite_max" type="number" min="1" max="100000" required>
        <button type="submit">Créer le tournoi</button>
      </form>

      <?php if ($editTournament !== null): ?>
        <form method="post" action="actions/admin/tournament_update.php" class="crud-form">
          <h3>Modifier un tournoi</h3>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="id" value="<?= (int) $editTournament['id'] ?>">
          <label for="edit-tournament-name">Nom du tournoi</label>
          <input id="edit-tournament-name" name="nom" type="text" maxlength="100" value="<?= htmlspecialchars($editTournament['nom'], ENT_QUOTES, 'UTF-8') ?>" required>
          <label for="edit-tournament-game">Jeu</label>
          <input id="edit-tournament-game" name="jeu" type="text" maxlength="100" value="<?= htmlspecialchars($editTournament['jeu'], ENT_QUOTES, 'UTF-8') ?>" required>
          <label for="edit-tournament-description">Description</label>
          <textarea id="edit-tournament-description" name="description" rows="3"><?= htmlspecialchars($editTournament['description'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
          <label for="edit-tournament-rules">Règles / conditions de participation</label>
          <textarea id="edit-tournament-rules" name="regles" rows="3"><?= htmlspecialchars($editTournament['regles'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
          <label for="edit-tournament-start">Date et heure du tournoi</label>
          <input id="edit-tournament-start" name="date_debut" type="datetime-local" value="<?= htmlspecialchars(formatTournamentDate($editTournament['date_debut'], 'Y-m-d\TH:i'), ENT_QUOTES, 'UTF-8') ?>" required>
          <label for="edit-registration-start">Ouverture des inscriptions</label>
          <input id="edit-registration-start" name="inscription_debut" type="datetime-local" value="<?= htmlspecialchars(formatTournamentDate($editTournament['inscription_debut'], 'Y-m-d\TH:i'), ENT_QUOTES, 'UTF-8') ?>" required>
          <label for="edit-registration-end">Clôture des inscriptions</label>
          <input id="edit-registration-end" name="inscription_fin" type="datetime-local" value="<?= htmlspecialchars(formatTournamentDate($editTournament['inscription_fin'], 'Y-m-d\TH:i'), ENT_QUOTES, 'UTF-8') ?>" required>
          <label for="edit-tournament-capacity">Capacité maximale</label>
          <input id="edit-tournament-capacity" name="capacite_max" type="number" min="1" max="100000" value="<?= (int) $editTournament['capacite_max'] ?>" required>
          <button type="submit">Enregistrer les modifications</button>
          <a class="cancel-link" href="admin.php#crud-tournois">Annuler</a>
        </form>
      <?php endif; ?>
    </div>

    <h3>Tournois existants</h3>
    <div class="table-wrapper">
      <table>
        <thead><tr><th>Tournoi</th><th>Jeu</th><th>Dates</th><th>Participants</th><th>Actions</th></tr></thead>
        <tbody>
          <?php if ($tournaments === []): ?>
            <tr><td colspan="5">Aucun tournoi créé.</td></tr>
          <?php endif; ?>
          <?php foreach ($tournaments as $tournament): ?>
            <tr>
              <td><?= htmlspecialchars($tournament['nom'], ENT_QUOTES, 'UTF-8') ?></td>
              <td><?= htmlspecialchars($tournament['jeu'], ENT_QUOTES, 'UTF-8') ?></td>
              <td>
                Tournoi : <?= htmlspecialchars(formatTournamentDate($tournament['date_debut'], 'd/m/Y H:i'), ENT_QUOTES, 'UTF-8') ?><br>
                Inscriptions : <?= htmlspecialchars(formatTournamentDate($tournament['inscription_debut'], 'd/m/Y H:i'), ENT_QUOTES, 'UTF-8') ?>
                – <?= htmlspecialchars(formatTournamentDate($tournament['inscription_fin'], 'd/m/Y H:i'), ENT_QUOTES, 'UTF-8') ?>
              </td>
              <td><?= (int) $tournament['participant_count'] ?> / <?= (int) $tournament['capacite_max'] ?></td>
              <td class="actions">
                <a class="button-link" href="admin.php?edit_tournament=<?= (int) $tournament['id'] ?>#crud-tournois">Modifier</a>
                <form method="post" action="actions/admin/tournament_delete.php" onsubmit="return confirm('Supprimer ce tournoi et toutes ses inscriptions ?');">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="id" value="<?= (int) $tournament['id'] ?>">
                  <button class="delete-button" type="submit">Supprimer</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>