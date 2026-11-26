<?php

require_once __DIR__ . '/conexao.php';

try {
    $pdo->query('SELECT 1');
    echo 'Conexão com o banco realizada com sucesso!';
} catch (Throwable $e) {
    error_log('Teste de conexão falhou: ' . $e->getMessage());
    http_response_code(500);
    echo 'Não foi possível conectar ao banco de dados.';
}
