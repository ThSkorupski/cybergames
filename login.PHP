<?php
session_start();
require_once __DIR__ . '/config.php';

#$pageTitle modifie le title dans le header
$pageTitle = "Login - CyberGames";
$error = null;

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([":email" => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user["password"])) {
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["username"] = $user["username"];
        $_SESSION["permission"] = (int) ($user["permission"] ?? 0);
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Email ou mot de passe incorrect.";
    }
}

include 'includes/header.php';
?>

<main class="auth-content">
<section class="auth-card">
    <h1>Se connecter</h1>

<?php if ($error !== null): ?>
    <p class="auth-message error" role="alert">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </p>
<?php endif; ?>

<?php if (isset($_GET['registered'])): ?>
    <p class="auth-message success" role="status">
        Compte créé avec succès. Vous pouvez maintenant vous connecter.
    </p>
<?php endif; ?>

<form class="auth-form" method="POST">
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Mot de passe" required>
    <button type="submit">Connexion</button>
</form>

<a class="secondary-button" href="register.php">Créer un compte</a>
</section>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>