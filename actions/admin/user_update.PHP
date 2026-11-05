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

$id         = (int) inputString($_POST, 'id');
$username   = trim(inputString($_POST, 'username'));
$email      = trim(inputString($_POST, 'email'));
$password   = inputString($_POST, 'password');   // optionnel
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

        redirectWithMessage('../../admin.php#crud-users', 'success', 'Utilisateur modifié.');
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
    redirectWithMessage('../../admin.php?edit=' . $id . '#crud-users', 'error', $error);
}

header('Location: ../../admin.php#crud-users');
exit;