<?php

require_once __DIR__ . '/seguranca.php';
enviarCabecalhosSeguranca();

require_once __DIR__ . '/auth.php';

header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metodo nao permitido.');
}

exigirAdmin();

if (!validarCsrf($_POST['csrf'] ?? null)) {
    http_response_code(403);
    exit('Token de seguranca invalido. Recarregue a pagina e tente de novo.');
}

require_once __DIR__ . '/conexao.php';

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

if (mb_strlen($nome) > 150 || mb_strlen($nacionalidade) > 100 || mb_strlen($biografia) > 2000) {
    http_response_code(400);
    exit('Um dos campos excede o tamanho maximo permitido.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120) {
    http_response_code(400);
    exit('Digite um e-mail valido.');
}

$data = DateTimeImmutable::createFromFormat('Y-m-d', $dataNascimento);
$errosData = DateTimeImmutable::getLastErrors();
if (
    $data === false ||
    ($errosData !== false && ($errosData['warning_count'] > 0 || $errosData['error_count'] > 0)) ||
    $data > new DateTimeImmutable('today')
) {
    http_response_code(400);
    exit('A data de nascimento e invalida.');
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
        exit('Ja existe um autor cadastrado com este e-mail.');
    }
    error_log('Erro ao cadastrar autor: ' . $e->getMessage());
    http_response_code(500);
    exit('Erro ao cadastrar autor. Tente novamente.');
}
