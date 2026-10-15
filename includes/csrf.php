<?php

function csrfToken(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException('La session doit être démarrée avant la création du jeton CSRF.');
    }

    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function isValidCsrfToken($token): bool
{
    return is_string($token)
        && session_status() === PHP_SESSION_ACTIVE
        && isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}
