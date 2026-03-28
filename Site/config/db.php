<?php

function getDbConnection(): mysqli
{
    $host = getenv('DB_HOST') ?: 'db';
    $port = (int) (getenv('DB_PORT') ?: 3306);
    $database = getenv('DB_DATABASE') ?: 'site_actualite';
    $username = getenv('DB_USERNAME') ?: 'root';
    $password = getenv('DB_PASSWORD') ?: 'root';

    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $mysqli = new mysqli($host, $username, $password, $database, $port);
    $mysqli->set_charset('utf8mb4');

    return $mysqli;
}
