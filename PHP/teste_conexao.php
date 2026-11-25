<?php

try {
    require_once "conexao.php";
    echo "Conexão com PostgreSQL realizada com sucesso!";
} catch (Throwable $e) {
    echo "Erro na conexão: " . $e->getMessage();
}

?>