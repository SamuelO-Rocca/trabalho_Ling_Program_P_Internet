
<?php

require_once __DIR__ . "/conexao.php";

$nome = $_POST["nome"] ?? "";
$nacionalidade = $_POST["nacionalidade"] ?? "";
$dataNascimento = $_POST["dataNascimento"] ?? "";
$email = $_POST["email"] ?? "";
$biografia = $_POST["biografia"] ?? "";

if (
    $nome == "" ||
    $nacionalidade == "" ||
    $dataNascimento == "" ||
    $email == "" ||
    $biografia == ""
) {
    echo "Preencha todos os campos do autor.";
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Digite um e-mail válido.";
    exit;
}

$data = strtotime($dataNascimento);

if ($data === false || $data > time()) {
    echo "A data de nascimento é inválida.";
    exit;
}

try {

    $sql = "INSERT INTO autor
            (nome, nacionalidade, data_nascimento, email, biografia)
            VALUES
            (:nome, :nacionalidade, :dataNascimento, :email, :biografia)";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ":nome" => $nome,
        ":nacionalidade" => $nacionalidade,
        ":dataNascimento" => $dataNascimento,
        ":email" => $email,
        ":biografia" => $biografia
    ]);

    echo <<<HTML
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro realizado | Biblioteca</title>

    <link rel="stylesheet" href="../CSS/estilo.css">

    <style>
        .sucesso-card {
            max-width: 650px;
            margin: 5rem auto;
            padding: 2.5rem 2rem;
            text-align: center;
            background-color: var(--areia-claro);
            border: 1px solid rgba(90, 24, 39, 0.15);
            border-top: 5px solid var(--dourado);
            border-radius: var(--raio);
            box-shadow: var(--sombra);
            animation: surgir 0.7s ease both;
        }

        .sucesso-icone {
            width: 75px;
            height: 75px;
            margin: 0 auto 1.2rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            background-color: var(--vinho);
            color: var(--dourado);
            font-size: 2.5rem;
        }

        .sucesso-card h1 {
            color: var(--vinho);
            margin: 0 0 1rem;
        }

        .sucesso-card p {
            color: var(--chumbo);
            margin-bottom: 1.8rem;
            font-size: 1.05rem;
        }

        .sucesso-acoes {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 0.8rem;
        }

        .sucesso-acoes a {
            display: inline-block;
            padding: 0.7rem 1.3rem;
            border-radius: 6px;
            font-weight: bold;
            transition: 0.25s ease;
        }

        .botao-cadastrar {
            background-color: var(--vinho);
            color: var(--areia);
        }

        .botao-cadastrar:hover {
            background-color: var(--vinho-escuro);
            color: var(--dourado);
        }

        .botao-inicio {
            border: 1.5px solid var(--vinho);
            color: var(--vinho);
        }

        .botao-inicio:hover {
            background-color: var(--vinho);
            color: var(--areia);
        }

        @media (max-width: 600px) {
            .sucesso-card {
                margin: 2rem 1rem;
                padding: 2rem 1rem;
            }

            .sucesso-acoes {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>

    <header>
        <h1>Biblioteca</h1>
        <h2>Sistema de gerenciamento</h2>
    </header>

    <main>
        <section class="sucesso-card">

            <div class="sucesso-icone">&#10003;</div>

            <h1>Cadastro realizado!</h1>

            <p>
                O autor foi cadastrado com sucesso em nosso sistema.
                Todas as informações foram armazenadas no banco de dados.
            </p>

            <div class="sucesso-acoes">
                <a href="../HTML/Autor.html" class="botao-cadastrar">
                    Cadastrar outro autor
                </a>

                <a href="../index.html" class="botao-inicio">
                    Voltar ao início
                </a>
            </div>

        </section>
    </main>

</body>
</html>
HTML;

} catch (PDOException $e) {

    echo "Erro ao cadastrar autor: " . $e->getMessage();

}

?>
