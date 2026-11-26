<?php

require_once __DIR__ . '/conexao.php';

$nome = $_POST["nome"] ?? "";
$nacionalidade = $_POST["nacionalidade"] ?? "";
$dataNascimento = $_POST["dataNascimento"] ?? "";
$email = $_POST["email"] ?? "";
$biografia = $_POST["biografia"] ?? "";

if (
    $nome == "" ||
    $nacionalidade == "" ||
    $dataNascimento == "" ||
    $email == "" ||
    $biografia == ""
) {
    echo "Preencha todos os campos do autor.";
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Digite um e-mail válido.";
    exit;
}

$data = strtotime($dataNascimento);

if ($data === false || $data > time()) {
    echo "A data de nascimento é inválida.";
    exit;
}

try {
    $sql = "INSERT INTO autor
            (nome, nacionalidade, data_nascimento, email, biografia)
            VALUES
            (:nome, :nacionalidade, :dataNascimento, :email, :biografia)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":nome" => $nome,
        ":nacionalidade" => $nacionalidade,
        ":dataNascimento" => $dataNascimento,
        ":email" => $email,
        ":biografia" => $biografia
    ]);

    echo "Autor cadastrado com sucesso!";
} catch (PDOException $e) {
    error_log("Erro ao cadastrar autor: " . $e->getMessage());
    http_response_code(500);
    echo "Erro ao cadastrar autor. Tente novamente.";
}
