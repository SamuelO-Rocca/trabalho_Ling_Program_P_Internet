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

if (mb_strlen($nome) > 100 || mb_strlen($descricao) > 500 || mb_strlen($localizacao) > 150) {
    http_response_code(400);
    exit('Um dos campos excede o tamanho maximo permitido.');
}

if ($status !== 'ativa' && $status !== 'inativa') {
    http_response_code(400);
    exit('Selecione um status valido.');
}

if (mb_strlen($codigo) < 2 || mb_strlen($codigo) > 50) {
    http_response_code(400);
    exit('O codigo da categoria deve ter entre 2 e 50 caracteres.');
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
        exit('Ja existe uma categoria cadastrada com este codigo.');
    }
    error_log('Erro ao cadastrar categoria: ' . $e->getMessage());
    http_response_code(500);
    exit('Erro ao cadastrar categoria. Tente novamente.');
}
