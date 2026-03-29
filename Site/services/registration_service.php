<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/users.php';

/**
 * Données par défaut du formulaire d'inscription.
 */
function registrationFormDefaults(): array
{
    return [
        'name' => '',
        'email' => '',
        'password' => '',
        'confirm_password' => '',
    ];
}

/**
 * Valide les entrées utilisateur et retourne les champs normalisés + erreurs.
 */
function validateRegistrationInput(array $input): array
{
    $data = [
        'name' => trim($input['name'] ?? ''),
        'email' => trim($input['email'] ?? ''),
        'password' => trim($input['password'] ?? ''),
        'confirm_password' => trim($input['confirm_password'] ?? ''),
    ];

    $errors = [];

    if ($data['name'] === '' || mb_strlen($data['name']) < 3) {
        $errors[] = 'Le nom doit contenir au moins 3 caractères.';
    }

    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Adresse email invalide.';
    }

    if (mb_strlen($data['password']) < 8) {
        $errors[] = 'Le mot de passe doit contenir au moins 8 caractères.';
    }

    if ($data['password'] !== $data['confirm_password']) {
        $errors[] = 'La confirmation du mot de passe ne correspond pas.';
    }

    return [$data, $errors];
}

/**
 * Tente de créer un compte et retourne [champs, erreurs, succès].
 */
function handleRegistration(array $input): array
{
    [$data, $errors] = validateRegistrationInput($input);
    $createdUser = null;

    if (!$errors) {
        try {
            $connection = getDbConnection();

            if (userEmailExists($connection, $data['email'])) {
                $errors[] = 'Cette adresse email est déjà utilisée.';
            } else {
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                $newUserId = createUser($connection, $data['name'], $data['email'], $hashedPassword);

                $freshUser = findUserById($connection, (int) $newUserId);
                if ($freshUser) {
                    unset($freshUser['password']);
                    $createdUser = $freshUser;
                }

                $data = registrationFormDefaults();
            }
        } catch (Throwable $e) {
            $errors[] = 'Erreur lors de l\'inscription : ' . $e->getMessage();
        }
    }

    // Ne jamais renvoyer les mots de passe vers la vue.
    $data['password'] = '';
    $data['confirm_password'] = '';

    return [$data, $errors, $createdUser];
}
