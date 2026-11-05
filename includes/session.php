<?php

# Point d'entrée unique pour démarrer la session avec des réglages sûrs.
function startAppSession(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    # Refuse un identifiant de session inconnu (protection contre la fixation de session)
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}
