<?php

require_once __DIR__ . '/seguranca.php';
enviarCabecalhosSeguranca();

require_once __DIR__ . '/conexao.php';

function cpfValido(string $cpf): bool {
    if (preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }
    for ($t = 9; $t < 11; $t++) {
        $soma = 0;
        for ($i = 0; $i < $t; $i++) {
            $soma += (int) $cpf[$i] * ($t + 1 - $i);
        }
        $digito = ($soma * 10) % 11;
        if ($digito === 10) {
            $digito = 0;
        }
        if ($digito !== (int) $cpf[$t]) {
            return false;
        }
    }
    return true;
}

header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}

$nome = trim($_POST['nome'] ?? '');
$email = trim($_POST['email'] ?? '');
$cpf = trim($_POST['cpf'] ?? '');
$dataNascimento = trim($_POST['data_nascimento'] ?? '');
$telefone = trim($_POST['telefone'] ?? '');
$tipoUsuario = trim($_POST['tipo_usuario'] ?? '');
$cep = trim($_POST['cep'] ?? '');
$endereco = trim($_POST['endereco'] ?? '');
$complemento = trim($_POST['complemento'] ?? '');
$cidade = trim($_POST['cidade'] ?? '');
$estado = trim($_POST['estado'] ?? '');
$senha = $_POST['senha'] ?? '';
$confirmarSenha = $_POST['confirmar_senha'] ?? '';
$observacoes = trim($_POST['observacoes'] ?? '');
$aceiteTermos = isset($_POST['aceite_termos']);

if (
    $nome === '' ||
    $email === '' ||
    $cpf === '' ||
    $dataNascimento === '' ||
    $tipoUsuario === '' ||
    $cep === '' ||
    $endereco === '' ||
    $cidade === '' ||
    $estado === '' ||
    $senha === '' ||
    $confirmarSenha === ''
) {
    http_response_code(400);
    exit('Preencha todos os campos obrigatórios do usuário.');
}

if (!$aceiteTermos) {
    http_response_code(400);
    exit('É obrigatório aceitar as regras de uso da biblioteca.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    exit('Digite um e-mail válido.');
}

if (mb_strlen($nome) > 100 || mb_strlen($endereco) > 150 || mb_strlen($complemento) > 60
    || mb_strlen($cidade) > 80 || mb_strlen($observacoes) > 500) {
    http_response_code(400);
    exit('Um dos campos excede o tamanho máximo permitido.');
}

if (!preg_match('/^\d{11}$/', $cpf) || !cpfValido($cpf)) {
    http_response_code(400);
    exit('Digite um CPF válido.');
}

if ($telefone !== '' && !preg_match('/^\d{10,11}$/', $telefone)) {
    http_response_code(400);
    exit('Digite um telefone válido com 10 ou 11 dígitos.');
}

if (!preg_match('/^\d{8}$/', $cep)) {
    http_response_code(400);
    exit('Digite um CEP válido com 8 dígitos.');
}

$tiposPermitidos = ['aluno', 'professor', 'funcionario', 'visitante'];
if (!in_array($tipoUsuario, $tiposPermitidos, true)) {
    http_response_code(400);
    exit('Selecione um tipo de usuário válido.');
}

$estadosPermitidos = [
    'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO',
    'MA', 'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI',
    'RJ', 'RN', 'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO'
];
if (!in_array($estado, $estadosPermitidos, true)) {
    http_response_code(400);
    exit('Selecione um estado válido.');
}

$data = DateTimeImmutable::createFromFormat('Y-m-d', $dataNascimento);
$errosData = DateTimeImmutable::getLastErrors();
if (
    $data === false ||
    ($errosData !== false && ($errosData['warning_count'] > 0 || $errosData['error_count'] > 0)) ||
    $data > new DateTimeImmutable('today')
) {
    http_response_code(400);
    exit('A data de nascimento é inválida.');
}

$tamanhoSenha = strlen($senha);
if ($tamanhoSenha < 8 || $tamanhoSenha > 72) {
    http_response_code(400);
    exit('A senha deve ter entre 8 e 72 caracteres.');
}

if (!hash_equals($senha, $confirmarSenha)) {
    http_response_code(400);
    exit('As senhas não conferem.');
}

$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

try {
    $sql = 'INSERT INTO usuario (
                nome, email, cpf, data_nascimento, telefone, tipo_usuario,
                cep, endereco, complemento, cidade, estado, senha_hash,
                observacoes, aceite_termos
            ) VALUES (
                :nome, :email, :cpf, :data_nascimento, :telefone, :tipo_usuario,
                :cep, :endereco, :complemento, :cidade, :estado, :senha_hash,
                :observacoes, :aceite_termos
            )';

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nome' => $nome,
        ':email' => $email,
        ':cpf' => $cpf,
        ':data_nascimento' => $dataNascimento,
        ':telefone' => $telefone !== '' ? $telefone : null,
        ':tipo_usuario' => $tipoUsuario,
        ':cep' => $cep,
        ':endereco' => $endereco,
        ':complemento' => $complemento !== '' ? $complemento : null,
        ':cidade' => $cidade,
        ':estado' => $estado,
        ':senha_hash' => $senhaHash,
        ':observacoes' => $observacoes !== '' ? $observacoes : null,
        ':aceite_termos' => 'true'
    ]);

    echo 'Usuário cadastrado com sucesso!';
} catch (PDOException $e) {
    if ($e->getCode() === '23505') {
        http_response_code(409);
        exit('Já existe um usuário cadastrado com este e-mail ou CPF.');
    }
    error_log('Erro ao cadastrar usuário: ' . $e->getMessage());
    http_response_code(500);
    exit('Erro ao cadastrar usuário. Tente novamente.');
}
