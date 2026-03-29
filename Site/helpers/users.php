<?php

declare(strict_types=1);

/**
 * Vérifie si une adresse email est déjà utilisée par un utilisateur.
 */
function userEmailExists(mysqli $connection, string $email): bool
{
    $query = 'SELECT id FROM users WHERE email = ? LIMIT 1';
    $stmt = $connection->prepare($query);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->store_result();

    $exists = $stmt->num_rows > 0;
    $stmt->close();

    return $exists;
}

/**
 * Crée un utilisateur et retourne l'identifiant inséré.
 */
function createUser(mysqli $connection, string $name, string $email, string $hashedPassword, string $role = 'writer'): int
{
    $query = 'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)';
    $stmt = $connection->prepare($query);
    $stmt->bind_param('ssss', $name, $email, $hashedPassword, $role);
    $stmt->execute();

    $newId = $connection->insert_id;
    $stmt->close();

    return $newId;
}

/**
 * Récupère un utilisateur par son email ou retourne null si absent.
 */
function findUserByEmail(mysqli $connection, string $email): ?array
{
    $query = 'SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1';
    $stmt = $connection->prepare($query);
    $stmt->bind_param('s', $email);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;

    $stmt->close();

    return $user ?: null;
}
