<?php


function carregarEnv($arquivo) {
    if (!is_readable($arquivo)) {
        return;
    }
    $linhas = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($linhas as $linha) {
        $linha = trim($linha);
        if ($linha === '' || $linha[0] === '#' || strpos($linha, '=') === false) {
            continue;
        }
        list($chave, $valor) = explode('=', $linha, 2);
        $chave = trim($chave);
        if ($chave !== '' && getenv($chave) === false) {
            putenv($chave . '=' . trim($valor));
        }
    }
}

carregarEnv(__DIR__ . '/.env');
