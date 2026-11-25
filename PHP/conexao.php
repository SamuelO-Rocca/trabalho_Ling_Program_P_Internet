
<?php

$host = "localhost";
$porta = "5432";
$banco = "Biblioteca";
$usuario = "postgres";
$senha = "uniube";

$pdo = new PDO(
    "pgsql:host=$host;port=$porta;dbname=$banco",
    $usuario,
    $senha
);

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
