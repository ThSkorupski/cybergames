<?php

#DateTime, :: accede a la classe, pour utiliser une fonction statique.
function parseTournamentDate(string $value): ?string
{
    $date = DateTime::createFromFormat('!Y-m-d\TH:i', $value, new DateTimeZone('UTC'));
    $errors = DateTime::getLastErrors();

    #si il y a pas la data ou si il y a des erreurs de parsing, on renvoie null
    if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
        return null;
    }

    return $date->format('Y-m-d H:i:00');
}

function formatTournamentDate(string $value, string $format): string
{
    $date = DateTime::createFromFormat('!Y-m-d H:i:s', $value, new DateTimeZone('UTC'));
    if ($date === false) {
        throw new UnexpectedValueException('La date du tournoi enregistrée en base est invalide.');
    }

    return $date->format($format);
}

function validateTournamentInput(array $input): array
{
    foreach (['nom', 'jeu', 'date_debut', 'inscription_debut', 'inscription_fin'] as $requiredField) {
        if (!isset($input[$requiredField]) || !is_string($input[$requiredField])) {
            return ['error' => 'Les champs du tournoi sont invalides ou incomplets.'];
        }
    }
    foreach (['description', 'regles'] as $optionalField) {
        if (isset($input[$optionalField]) && !is_string($input[$optionalField])) {
            return ['error' => 'Les champs du tournoi sont invalides.'];
        }
    }

    $name = trim($input['nom']);
    $game = trim($input['jeu']);
    $description = trim($input['description'] ?? '');
    $rules = trim($input['regles'] ?? '');
    $eventStartsAt = parseTournamentDate($input['date_debut']);
    $registrationStartsAt = parseTournamentDate($input['inscription_debut']);
    $registrationEndsAt = parseTournamentDate($input['inscription_fin']);
    $capacity = filter_var($input['capacite_max'] ?? null, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 64],
    ]);

    if ($name === '' || strlen($name) > 100 || $game === '' || strlen($game) > 100) {
        return ['error' => 'Le nom du tournoi et le jeu sont obligatoires (100 caractères maximum).'];
    }
    if (
        $eventStartsAt === null
        || $registrationStartsAt === null
        || $registrationEndsAt === null
        || $registrationEndsAt <= $registrationStartsAt
    ) {
        return ['error' => 'Les dates sont invalides : la fin des inscriptions doit être après leur début.'];
    }
    if ($capacity === false) {
        return ['error' => 'La capacité doit être comprise entre 1 et 64 participants.'];
    }

    return [
        'data' => [
            'nom' => $name,
            'jeu' => $game,
            'description' => $description,
            'regles' => $rules,
            'date_debut' => $eventStartsAt,
            'capacite_max' => $capacity,
            'inscription_debut' => $registrationStartsAt,
            'inscription_fin' => $registrationEndsAt,
        ],
    ];
}
