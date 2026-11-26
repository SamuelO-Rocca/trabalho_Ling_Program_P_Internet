<?php

require_once __DIR__ . '/seguranca.php';
enviarCabecalhosSeguranca();

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/conexao.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Metodo nao permitido.');
}

exigirAdmin();

$tabelas = ['usuario', 'livro', 'autor', 'categoria', 'emprestimo'];
$titulos = [
    'usuario' => 'Usuários',
    'livro' => 'Livros',
    'autor' => 'Autores',
    'categoria' => 'Categorias',
    'emprestimo' => 'Empréstimos'
];

$totais = [];
foreach ($tabelas as $tabela) {
    $stmt = $pdo->query('SELECT count(*) AS total FROM ' . $tabela);
    $totais[$tabela] = [
        'titulo' => $titulos[$tabela],
        'total' => (int) $stmt->fetch()['total']
    ];
}

echo json_encode(['ok' => true, 'totais' => $totais]);
