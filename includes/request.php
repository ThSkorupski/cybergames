<?php

# Lit un champ texte. Un tableau (ex : email[]=x) est traité comme un champ vide.
function inputString(array $source, string $key): string
{
    $value = $source[$key] ?? '';

    return is_string($value) ? $value : '';
}

# Garde le message en session pour l'afficher une seule fois après la redirection.
function redirectWithMessage(string $location, string $status, string $message): never
{
    $_SESSION['flash'] = ['status' => $status, 'message' => $message];
    header('Location: ' . $location);
    exit;
}

function pullFlashMessage(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);

    return $flash;
}
