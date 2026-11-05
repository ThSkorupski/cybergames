<?php

# Limite les essais de connexion par couple adresse IP + email.
# Les compteurs sont dans le dossier temporaire : aucune table à ajouter en base.
const LOGIN_MAX_ATTEMPTS = 5;
const LOGIN_LOCK_SECONDS = 900;

function loginThrottlePath(string $email): string
{
    $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cybergames_login_throttle';
    if (!is_dir($directory)) {
        @mkdir($directory, 0700, true);
    }

    $key = ($_SERVER['REMOTE_ADDR'] ?? '') . '|' . strtolower($email);

    return $directory . DIRECTORY_SEPARATOR . hash('sha256', $key) . '.json';
}

function readLoginAttempts(string $path): array
{
    $empty = ['count' => 0, 'last' => 0];
    $content = is_file($path) ? @file_get_contents($path) : false;
    $attempts = $content === false ? null : json_decode($content, true);

    if (!is_array($attempts) || !isset($attempts['count'], $attempts['last'])) {
        return $empty;
    }

    # Le compteur repart de zéro une fois le délai écoulé
    return time() - (int) $attempts['last'] >= LOGIN_LOCK_SECONDS ? $empty : $attempts;
}

# Renvoie le nombre de secondes à attendre, 0 si la connexion est autorisée.
function loginRetrySeconds(string $email): int
{
    $attempts = readLoginAttempts(loginThrottlePath($email));
    if ((int) $attempts['count'] < LOGIN_MAX_ATTEMPTS) {
        return 0;
    }

    return max(1, LOGIN_LOCK_SECONDS - (time() - (int) $attempts['last']));
}

function recordLoginFailure(string $email): void
{
    $path = loginThrottlePath($email);
    $attempts = readLoginAttempts($path);
    $attempts['count'] = (int) $attempts['count'] + 1;
    $attempts['last'] = time();

    if (@file_put_contents($path, json_encode($attempts), LOCK_EX) === false) {
        error_log('Impossible d\'enregistrer la tentative de connexion : ' . $path);
    }
}

function clearLoginFailures(string $email): void
{
    $path = loginThrottlePath($email);
    if (is_file($path)) {
        @unlink($path);
    }
}
