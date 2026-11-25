<?php

require_once __DIR__ . '/conexao.php';

header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}

$nome = trim($_POST['nome'] ?? '');
$descricao = trim($_POST['descricao'] ?? '');
$codigo = trim($_POST['codigo'] ?? '');
$status = trim($_POST['status'] ?? '');
$localizacao = trim($_POST['localizacao'] ?? '');

if (
    $nome === '' ||
    $descricao === '' ||
    $codigo === '' ||
    $status === '' ||
    $localizacao === ''
) {
    http_response_code(400);
    exit('Preencha todos os campos da categoria.');
}

if ($status !== 'ativa' && $status !== 'inativa') {
    http_response_code(400);
    exit('Selecione um status válido.');
}

if (mb_strlen($codigo) < 2) {
    http_response_code(400);
    exit('O código da categoria deve possuir pelo menos 2 caracteres.');
}

try {
    $sql = 'INSERT INTO categoria (nome, descricao, codigo, status, localizacao)
            VALUES (:nome, :descricao, :codigo, :status, :localizacao)';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nome' => $nome,
        ':descricao' => $descricao,
        ':codigo' => $codigo,
        ':status' => $status,
        ':localizacao' => $localizacao
    ]);

    echo 'Categoria cadastrada com sucesso!';
} catch (PDOException $e) {
    if ($e->getCode() === '23505') {
        http_response_code(409);
        exit('Já existe uma categoria cadastrada com este código.');
    }
    error_log('Erro ao cadastrar categoria: ' . $e->getMessage());
    http_response_code(500);
    exit('Erro ao cadastrar categoria. Tente novamente.');
}
