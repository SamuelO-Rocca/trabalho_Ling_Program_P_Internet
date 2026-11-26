<?php


function iniciarSessaoSegura(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    ini_set('session.use_strict_mode', '1');
    session_name('BIBSESS');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Strict',
        'secure' => !empty($_SERVER['HTTPS']),
        'path' => '/'
    ]);
    session_start();
}

function csrfToken(): string {
    iniciarSessaoSegura();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function validarCsrf(?string $token): bool {
    iniciarSessaoSegura();
    return is_string($token)
        && !empty($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $token);
}

function ehAdmin(): bool {
    iniciarSessaoSegura();
    return ($_SESSION['tipo'] ?? '') === 'admin';
}

function exigirAdmin(): void {
    if (!ehAdmin()) {
        http_response_code(401);
        header('Content-Type: text/plain; charset=utf-8');
        exit('Acesso restrito ao administrador. Faca login no painel.');
    }
}

function arquivoTentativasLogin(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
    return sys_get_temp_dir() . '/bib_login_' . md5($ip);
}

function loginBloqueadoAte(): int {
    $arquivo = arquivoTentativasLogin();
    if (!is_readable($arquivo)) {
        return 0;
    }
    $dados = json_decode((string) file_get_contents($arquivo), true);
    return is_array($dados) ? (int) ($dados['bloqueio_ate'] ?? 0) : 0;
}

function registrarFalhaLogin(): void {
    $arquivo = arquivoTentativasLogin();
    $dados = ['tentativas' => 0, 'bloqueio_ate' => 0];
    if (is_readable($arquivo)) {
        $lidos = json_decode((string) file_get_contents($arquivo), true);
        if (is_array($lidos)) {
            $dados = $lidos;
        }
    }
    $dados['tentativas'] = (int) ($dados['tentativas'] ?? 0) + 1;
    if ($dados['tentativas'] >= 5) {
        $dados['bloqueio_ate'] = time() + 60;
        $dados['tentativas'] = 0;
    }
    file_put_contents($arquivo, json_encode($dados), LOCK_EX);
}

function responderBloqueioLogin(): void {
    http_response_code(429);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'mensagem' => 'Muitas tentativas. Aguarde 60 segundos.']);
    exit;
}

function responderFalhaLogin(): void {
    registrarFalhaLogin();
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'mensagem' => 'Usuario ou senha incorretos.']);
    exit;
}
