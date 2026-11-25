<?php

require_once __DIR__ . '/conexao.php';

header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}

$nome = trim($_POST['nome'] ?? '');
$nacionalidade = trim($_POST['nacionalidade'] ?? '');
$dataNascimento = trim($_POST['dataNascimento'] ?? '');
$email = trim($_POST['email'] ?? '');
$biografia = trim($_POST['biografia'] ?? '');

if (
    $nome === '' ||
    $nacionalidade === '' ||
    $dataNascimento === '' ||
    $email === '' ||
    $biografia === ''
) {
    http_response_code(400);
    exit('Preencha todos os campos do autor.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit('Digite um e-mail válido.');
}

$data = DateTimeImmutable::createFromFormat('Y-m-d', $dataNascimento);
$errosData = DateTimeImmutable::getLastErrors();
if (
    $data === false ||
    ($errosData !== false && ($errosData['warning_count'] > 0 || $errosData['error_count'] > 0)) ||
    $data > new DateTimeImmutable('today')
) {
    http_response_code(400);
    exit('A data de nascimento é inválida.');
}

try {
    $sql = 'INSERT INTO autor (nome, nacionalidade, data_nascimento, email, biografia)
            VALUES (:nome, :nacionalidade, :data_nascimento, :email, :biografia)';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nome' => $nome,
        ':nacionalidade' => $nacionalidade,
        ':data_nascimento' => $dataNascimento,
        ':email' => $email,
        ':biografia' => $biografia
    ]);

    echo 'Autor cadastrado com sucesso!';
} catch (PDOException $e) {
    if ($e->getCode() === '23505') {
        http_response_code(409);
        exit('Já existe um autor cadastrado com este e-mail.');
    }
    error_log('Erro ao cadastrar autor: ' . $e->getMessage());
    http_response_code(500);
    exit('Erro ao cadastrar autor. Tente novamente.');
}
