<?php

require_once __DIR__ . '/config.php';

$host = getenv('DB_HOST') ?: 'localhost';
$porta = getenv('DB_PORT') ?: '5432';
$banco = getenv('DB_NAME') ?: 'Biblioteca';
$usuarioBanco = getenv('DB_USER') ?: 'postgres';
$senhaBanco = getenv('DB_PASS') ?: '';

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$porta;dbname=$banco",
        $usuarioBanco,
        $senhaBanco,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    $pdo->exec("SET client_encoding TO 'UTF8'");
} catch (PDOException $e) {
    error_log('Falha ao conectar no banco: ' . $e->getMessage());
    http_response_code(500);
    exit('Não foi possível conectar ao banco de dados.');
}
