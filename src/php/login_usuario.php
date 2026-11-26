<?php

require_once __DIR__ . '/seguranca.php';
enviarCabecalhosSeguranca();

require_once __DIR__ . '/conexao.php';
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

$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';

if ($email === '' || $senha === '') {
    responder(false, 'Preencha o e-mail e a senha.', 400);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120) {
    responder(false, 'Digite um e-mail valido.', 400);
}

if (loginBloqueadoAte() > time()) {
    responderBloqueioLogin();
}

iniciarSessaoSegura();

// Hash de reserva: mantem o tempo de resposta parecido quando o e-mail
// nao existe, para nao revelar cadastros pelo tempo de resposta.
$hashReserva = password_hash('verificacao-de-reserva', PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare(
        'SELECT id, nome, email, senha_hash
           FROM usuario
          WHERE email = :email
          LIMIT 1'
    );
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch();
} catch (PDOException $e) {
    error_log('Erro ao consultar usuario no login: ' . $e->getMessage());
    responder(false, 'Nao foi possivel validar o acesso agora. Tente novamente.', 500);
}

$hash = $usuario !== false ? $usuario['senha_hash'] : $hashReserva;

if ($usuario === false || !password_verify($senha, $hash)) {
    responderFalhaLogin();
}

session_regenerate_id(true);
$_SESSION['tipo'] = 'usuario';
$_SESSION['usuario_id'] = (int) $usuario['id'];
$_SESSION['usuario'] = $usuario['nome'];

responder(true, 'Login realizado com sucesso!');
