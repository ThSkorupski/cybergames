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

$username   = trim(inputString($_POST, 'username'));
$email      = trim(inputString($_POST, 'email'));
$password   = inputString($_POST, 'password');
$permission = (int) ($_POST['permission'] ?? 1);

$error = null;

if ($username === '' || $email === '' || $password === '') {
    $error = 'Tous les champs sont obligatoires.';
} elseif (strlen($username) < 3 || strlen($username) > 50) {
    $error = "Le nom d'utilisateur doit contenir entre 3 et 50 caractères.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Email invalide.';
} elseif (strlen($password) < 8) {
    $error = 'Le mot de passe doit contenir au moins 8 caractères.';
} elseif (!in_array($permission, [1, 2], true)) {
    $error = 'Permission invalide.';
}

if ($error === null) {
    try {
        $stmt = $pdo->prepare(
            'INSERT INTO users (username, email, password, permission)
             VALUES (:username, :email, :password, :permission)'
        );
        $stmt->execute([
            ':username'   => $username,
            ':email'      => $email,
            ':password'   => password_hash($password, PASSWORD_DEFAULT),
            ':permission' => $permission,
        ]);
        redirectWithMessage('../../admin.php#crud-users', 'success', 'Utilisateur créé.');
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $error = "Nom d'utilisateur ou email déjà utilisé.";
        } else {
            error_log($e->getMessage());
            $error = 'Erreur lors de la création.';
        }
    }
}

if ($error !== null) {
    redirectWithMessage('../../admin.php#crud-users', 'error', $error);
}

header('Location: ../../admin.php#crud-users');
exit;