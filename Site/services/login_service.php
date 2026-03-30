<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../helpers/users.php';

function loginFormDefaults(): array
{
    return [
        'email' => '',
        'password' => '',
    ];
}

function validateLoginInput(array $input): array
{
    $data = [
        'email' => trim($input['email'] ?? ''),
        'password' => trim($input['password'] ?? ''),
    ];

    $errors = [];

    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Adresse email invalide.';
    }

    if ($data['password'] === '') {
        $errors[] = 'Le mot de passe est obligatoire.';
    }

    return [$data, $errors];
}

function handleLogin(array $input): array
{
    [$data, $errors] = validateLoginInput($input);
    $user = null;

    if (!$errors) {
        try {
            $connection = getDbConnection();
            $user = findUserByEmail($connection, $data['email']);

            if (!$user) {
                $errors[] = 'Email ou mot de passe incorrect.';
            } else {
                $isHashed = array_key_exists('is_hashed', $user) ? (bool) $user['is_hashed'] : true;
                $passwordMatches = false;

                if ($isHashed) {
                    $passwordMatches = password_verify($data['password'], $user['password']);
                } else {
                    $passwordMatches = hash_equals($user['password'], $data['password']);

                    if ($passwordMatches) {
                        upgradeLegacyPassword($connection, (int) $user['id'], $data['password']);
                        $user['is_hashed'] = 1;
                    }
                }

                if (!$passwordMatches) {
                    $errors[] = 'Email ou mot de passe incorrect.';
                    $user = null;
                } else {
                    unset($user['password']);
                }
            }
        } catch (Throwable $e) {
            $errors[] = 'Erreur lors de la connexion : ' . $e->getMessage();
        }
    }

    $data['password'] = '';

    return [$data, $errors, $user];
}
