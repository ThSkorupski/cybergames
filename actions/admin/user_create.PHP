<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$username   = trim($_POST['username'] ?? '');
$email      = trim($_POST['email'] ?? '');
$password   = $_POST['password'] ?? '';
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
        header('Location: ../../admin.php?status=success&message=' . rawurlencode('Utilisateur créé.') . '#crud-users');
        exit;
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
    header('Location: ../../admin.php?status=error&message=' . rawurlencode($error) . '#crud-users');
    exit;
}

header('Location: ../../admin.php#crud-users');
exit;