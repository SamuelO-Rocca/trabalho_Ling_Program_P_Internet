<?php

require_once __DIR__ . '/seguranca.php';
enviarCabecalhosSeguranca();

require_once __DIR__ . '/conexao.php';

header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}

$titulo = trim($_POST['titulo'] ?? '');
$autor = trim($_POST['autor'] ?? '');
$isbn = trim($_POST['isbn'] ?? '');
$editora = trim($_POST['editora'] ?? '');
$edicao = trim($_POST['edicao'] ?? '1');
$ano = trim($_POST['ano_publicacao'] ?? '');
$numeroPaginas = trim($_POST['numero_paginas'] ?? '');
$categoria = trim($_POST['categoria'] ?? '');
$idioma = trim($_POST['idioma'] ?? '');
$sinopse = trim($_POST['sinopse'] ?? '');
$estoque = trim($_POST['estoque'] ?? '1');
$disponivel = isset($_POST['disponivel_emprestimo']);

if (
    $titulo === '' ||
    $autor === '' ||
    $isbn === '' ||
    $ano === '' ||
    $numeroPaginas === '' ||
    $categoria === '' ||
    $idioma === ''
) {
    http_response_code(400);
    exit('Preencha todos os campos do livro.');
}

if (mb_strlen($titulo) > 200 || mb_strlen($autor) > 150 || mb_strlen($editora) > 100 || mb_strlen($sinopse) > 1000) {
    http_response_code(400);
    exit('Um dos campos excede o tamanho máximo permitido.');
}

if (!preg_match('/^(\d{10}|\d{13})$/', $isbn)) {
    http_response_code(400);
    exit('Informe um ISBN válido com 10 ou 13 dígitos.');
}

if (!ctype_digit($ano)) {
    http_response_code(400);
    exit('O ano deve ser um número.');
}

$anoInt = (int) $ano;
$anoAtual = (int) date('Y');
if ($anoInt < 1000 || $anoInt > $anoAtual) {
    http_response_code(400);
    exit('Digite um ano de publicação válido.');
}

if (!ctype_digit($numeroPaginas) || (int) $numeroPaginas < 1 || (int) $numeroPaginas > 20000) {
    http_response_code(400);
    exit('Informe um número de páginas válido.');
}

if ($edicao === '') {
    $edicao = '1';
}
if (!ctype_digit($edicao) || (int) $edicao < 1 || (int) $edicao > 50) {
    http_response_code(400);
    exit('Informe uma edição válida.');
}

if ($estoque === '') {
    $estoque = '1';
}
if (!ctype_digit($estoque) || (int) $estoque < 0 || (int) $estoque > 9999) {
    http_response_code(400);
    exit('Informe uma quantidade em estoque válida.');
}

$categoriasPermitidas = ['ficcao', 'nao-ficcao', 'tecnico', 'infantil', 'biografia', 'poesia', 'hq', 'autoajuda'];
if (!in_array($categoria, $categoriasPermitidas, true)) {
    http_response_code(400);
    exit('Selecione uma categoria válida.');
}

$idiomasPermitidos = ['pt-BR', 'en', 'es', 'fr', 'de', 'outro'];
if (!in_array($idioma, $idiomasPermitidos, true)) {
    http_response_code(400);
    exit('Selecione um idioma válido.');
}

try {
    $sql = 'INSERT INTO livro (
                titulo, autor, isbn, editora, edicao, ano_publicacao,
                numero_paginas, categoria, idioma, sinopse, estoque,
                disponivel_emprestimo
            ) VALUES (
                :titulo, :autor, :isbn, :editora, :edicao, :ano_publicacao,
                :numero_paginas, :categoria, :idioma, :sinopse, :estoque,
                :disponivel_emprestimo
            )';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':titulo' => $titulo,
        ':autor' => $autor,
        ':isbn' => $isbn,
        ':editora' => $editora !== '' ? $editora : null,
        ':edicao' => (int) $edicao,
        ':ano_publicacao' => $anoInt,
        ':numero_paginas' => (int) $numeroPaginas,
        ':categoria' => $categoria,
        ':idioma' => $idioma,
        ':sinopse' => $sinopse !== '' ? $sinopse : null,
        ':estoque' => (int) $estoque,
        ':disponivel_emprestimo' => $disponivel ? 'true' : 'false'
    ]);

    echo 'Livro cadastrado com sucesso!';
} catch (PDOException $e) {
    if ($e->getCode() === '23505') {
        http_response_code(409);
        exit('Já existe um livro cadastrado com este ISBN.');
    }
    error_log('Erro ao cadastrar livro: ' . $e->getMessage());
    http_response_code(500);
    exit('Erro ao cadastrar livro. Tente novamente.');
}
