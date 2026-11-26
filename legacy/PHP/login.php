<?php

require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

function responder($ok, $mensagem, $codigo = 200) {
    http_response_code($codigo);
    echo json_encode(['ok' => $ok, 'mensagem' => $mensagem]);
    exit;
}

$usuario = $_POST['usuario'] ?? '';
$senha = $_POST['senha'] ?? '';

if ($usuario === '' || $senha === '') {
    responder(false, 'Preencha o usuário e a senha.', 400);
}

$adminUsuario = getenv('ADMIN_USER');
$adminHash = getenv('ADMIN_PASS_HASH');

if ($adminUsuario === false || $adminUsuario === '' || $adminHash === false || $adminHash === '') {
    error_log('ADMIN_USER/ADMIN_PASS_HASH nao configurados no .env');
    responder(false, 'Autenticação indisponível. Contate o responsável.', 500);
}

if (!hash_equals($adminUsuario, $usuario) || !password_verify($senha, $adminHash)) {
    responder(false, 'Usuário ou senha incorretos.', 401);
}

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict',
    'secure' => !empty($_SERVER['HTTPS'])
]);
session_start();
session_regenerate_id(true);
$_SESSION['tipo'] = 'admin';
$_SESSION['usuario'] = $usuario;

responder(true, 'Login realizado com sucesso!');
