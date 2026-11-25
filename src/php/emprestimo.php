<?php

require_once __DIR__ . '/conexao.php';

header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}

$usuario = trim($_POST['usuario'] ?? '');
$livro = trim($_POST['livro'] ?? '');
$dataEmprestimo = trim($_POST['dataEmprestimo'] ?? '');
$dataDevolucao = trim($_POST['dataDevolucao'] ?? '');
$observacao = trim($_POST['observacao'] ?? '');

if (
    $usuario === '' ||
    $livro === '' ||
    $dataEmprestimo === '' ||
    $dataDevolucao === '' ||
    $observacao === ''
) {
    http_response_code(400);
    exit('Preencha todos os campos do empréstimo.');
}

$inicio = DateTimeImmutable::createFromFormat('Y-m-d', $dataEmprestimo);
$errosInicio = DateTimeImmutable::getLastErrors();
$fim = DateTimeImmutable::createFromFormat('Y-m-d', $dataDevolucao);
$errosFim = DateTimeImmutable::getLastErrors();

if (
    $inicio === false ||
    $fim === false ||
    ($errosInicio !== false && ($errosInicio['warning_count'] > 0 || $errosInicio['error_count'] > 0)) ||
    ($errosFim !== false && ($errosFim['warning_count'] > 0 || $errosFim['error_count'] > 0))
) {
    http_response_code(400);
    exit('Digite datas válidas.');
}

if ($fim < $inicio) {
    http_response_code(400);
    exit('A data de devolução não pode ser anterior à data do empréstimo.');
}

$dias = (int) $inicio->diff($fim)->format('%a');

if ($dias > 7) {
    http_response_code(400);
    exit('O empréstimo não pode ultrapassar 7 dias.');
}

try {
    $sql = 'INSERT INTO emprestimo (usuario, livro, data_emprestimo, data_devolucao, observacao)
            VALUES (:usuario, :livro, :data_emprestimo, :data_devolucao, :observacao)';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':usuario' => $usuario,
        ':livro' => $livro,
        ':data_emprestimo' => $dataEmprestimo,
        ':data_devolucao' => $dataDevolucao,
        ':observacao' => $observacao
    ]);

    echo 'Empréstimo registrado com sucesso!';
} catch (PDOException $e) {
    error_log('Erro ao registrar empréstimo: ' . $e->getMessage());
    http_response_code(500);
    exit('Erro ao registrar empréstimo. Tente novamente.');
}
