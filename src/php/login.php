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

$adminUsuario = getenv('ADMIN_USER') ?: 'admin';
$adminHash = getenv('ADMIN_PASS_HASH') ?: '$2y$12$PchA2yOupjf7M.vlOiieMeUg.ySkOrs556S8v194mwq4DBbxlvMz.';

if (!hash_equals($adminUsuario, $usuario) || !password_verify($senha, $adminHash)) {
    responder(false, 'Usuário ou senha incorretos.', 401);
}

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS'])
]);
session_start();
session_regenerate_id(true);
$_SESSION['tipo'] = 'admin';
$_SESSION['usuario'] = $usuario;

responder(true, 'Login realizado com sucesso!');
