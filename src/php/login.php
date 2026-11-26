<?php

require_once __DIR__ . '/seguranca.php';
enviarCabecalhosSeguranca();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');

function responder($ok, $mensagem, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode(['ok' => $ok, 'mensagem' => $mensagem]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(false, 'Metodo nao permitido.', 405);
}

$usuario = trim($_POST['usuario'] ?? '');
$senha = $_POST['senha'] ?? '';

if ($usuario === '' || $senha === '') {
    responder(false, 'Preencha o usuario e a senha.', 400);
}

$adminUsuario = getenv('ADMIN_USER');
$adminHash = getenv('ADMIN_PASS_HASH');

if ($adminUsuario === false || $adminUsuario === '' || $adminHash === false || $adminHash === '') {
    error_log('ADMIN_USER/ADMIN_PASS_HASH nao configurados no .env');
    responder(false, 'Autenticacao indisponivel. Contate o responsavel.', 500);
}

if (loginBloqueadoAte() > time()) {
    responderBloqueioLogin();
}

iniciarSessaoSegura();

if (!hash_equals($adminUsuario, $usuario) || !password_verify($senha, $adminHash)) {
    responderFalhaLogin();
}
session_regenerate_id(true);
$_SESSION['tipo'] = 'admin';
$_SESSION['usuario'] = $usuario;

responder(true, 'Login realizado com sucesso!');
