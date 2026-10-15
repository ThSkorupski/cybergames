<?php
session_start();
require_once __DIR__ . '/config.php';

$pageTitle = "Inscription - CyberGames";
$error = null;
$oldUsername = '';
$oldEmail = '';

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

#$oldusername, oldemail..., si l'user fait une erreur --> garde le l'username et l'email pour que sa soit re-remplis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldUsername = trim($_POST['username'] ?? '');
    $oldEmail = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirm'] ?? '';

    if ($oldUsername === '' || $oldEmail === '' || $password === '' || $passwordConfirm === '') {
        $error = 'Tous les champs sont obligatoires.';
    } elseif (strlen($oldUsername) < 3 || strlen($oldUsername) > 50) {
        $error = "Le nom d'utilisateur doit contenir entre 3 et 50 caractères.";
    } elseif (!filter_var($oldEmail, FILTER_VALIDATE_EMAIL)) {
        $error = 'Veuillez saisir une adresse email valide.';
    } elseif (strlen($password) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Les mots de passe ne correspondent pas.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (username, email, password)
                 VALUES (:username, :email, :password)'
            );
            $stmt->execute([
                ':username' => $oldUsername,
                ':email' => $oldEmail,
                ':password' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            header('Location: login.php?registered=1');
            exit;
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                $error = 'Ce nom d’utilisateur ou cette adresse email est déjà utilisé(e).';
            } else {
                error_log($exception->getMessage());
                $error = "L'inscription est temporairement indisponible.";
            }
        }
    }
}

include __DIR__ . '/includes/header.php';
?>

<main class="auth-content">
<section class="auth-card">
<h1>Créer un compte</h1>

<?php if ($error !== null): ?>
    <p role="alert" style="color: red;">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </p>
<?php endif; ?>

<form class="auth-form" method="post" action="register.php">
    <label for="username">Nom d'utilisateur :</label>
    <input
        type="text"
        id="username"
        name="username"
        value="<?= htmlspecialchars($oldUsername, ENT_QUOTES, 'UTF-8') ?>"
        minlength="3"
        maxlength="50"
        required
    >

    <label for="email">Email :</label>
    <input
        type="email"
        id="email"
        name="email"
        value="<?= htmlspecialchars($oldEmail, ENT_QUOTES, 'UTF-8') ?>"
        required
    >

    <label for="password">Mot de passe :</label>
    <input type="password" id="password" name="password" minlength="8" required>

    <label for="password_confirm">Confirmer le mot de passe :</label>
    <input type="password" id="password_confirm" name="password_confirm" minlength="8" required>

    <button type="submit">S'inscrire</button>
</form>
</section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
