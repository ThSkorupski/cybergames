<?php
require_once __DIR__ . '/../../includes/auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$id         = (int) ($_POST['id'] ?? 0);
$username   = trim($_POST['username'] ?? '');
$email      = trim($_POST['email'] ?? '');
$password   = $_POST['password'] ?? '';   // optionnel
$permission = (int) ($_POST['permission'] ?? 1);

// un admin ne peut pas se retirer ses propres droits
if ($id === (int) $_SESSION['user_id']) {
    $permission = 2;
}

$error = null;

if ($id <= 0) {
    $error = 'Utilisateur invalide.';
} else {
    $targetStmt = $pdo->prepare('SELECT permission FROM users WHERE id = :id LIMIT 1');
    $targetStmt->execute([':id' => $id]);
    $targetPermission = $targetStmt->fetchColumn();

    if ($targetPermission === false) {
        $error = 'Utilisateur introuvable.';
    } elseif (
        (int) $id !== (int) $_SESSION['user_id']
        && (int) $targetPermission === 2
    ) {
        $error = "Tu ne peux pas modifier le compte d'un autre administrateur.";
    }
}

if ($error === null) {
    if ($username === '' || $email === '') {
        $error = "Le nom et l'email sont obligatoires.";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = "Le nom d'utilisateur doit contenir entre 3 et 50 caractères.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email invalide.';
    } elseif ($password !== '' && strlen($password) < 8) {
        $error = 'Le mot de passe doit contenir au moins 8 caractères.';
    } elseif (!in_array($permission, [1, 2], true)) {
        $error = 'Permission invalide.';
    }
}

if ($error === null) {
    try {
        if ($password !== '') {
            $stmt = $pdo->prepare(
                'UPDATE users
                 SET username = :username, email = :email, permission = :permission, password = :password
                 WHERE id = :id'
            );
            $stmt->bindValue(':password', password_hash($password, PASSWORD_DEFAULT));
        } else {
            $stmt = $pdo->prepare(
                'UPDATE users
                 SET username = :username, email = :email, permission = :permission
                 WHERE id = :id'
            );
        }
        $stmt->bindValue(':username', $username);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':permission', $permission, PDO::PARAM_INT);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();

        header('Location: ../../admin.php?status=success&message=' . rawurlencode('Utilisateur modifié.') . '#crud-users');
        exit;
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            $error = "Nom d'utilisateur ou email déjà utilisé.";
        } else {
            error_log($e->getMessage());
            $error = 'Erreur lors de la modification.';
        }
    }
}

if ($error !== null) {
    header('Location: ../../admin.php?edit=' . $id . '&status=error&message=' . rawurlencode($error) . '#crud-users');
    exit;
}

header('Location: ../../admin.php#crud-users');
exit;