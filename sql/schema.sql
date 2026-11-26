CREATE TABLE IF NOT EXISTS autor (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    nacionalidade VARCHAR(100) NOT NULL,
    data_nascimento DATE NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    biografia TEXT NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS categoria (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    descricao TEXT NOT NULL,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    status VARCHAR(10) NOT NULL CHECK (status IN ('ativa', 'inativa')),
    localizacao VARCHAR(150) NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS usuario (
    id SERIAL PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    cpf CHAR(11) NOT NULL UNIQUE,
    data_nascimento DATE NOT NULL,
    telefone VARCHAR(11),
    tipo_usuario VARCHAR(20) NOT NULL CHECK (tipo_usuario IN ('aluno', 'professor', 'funcionario', 'visitante')),
    cep CHAR(8) NOT NULL,
    endereco VARCHAR(150) NOT NULL,
    complemento VARCHAR(60),
    cidade VARCHAR(80) NOT NULL,
    estado CHAR(2) NOT NULL,
    senha_hash VARCHAR(255) NOT NULL,
    observacoes VARCHAR(500),
    aceite_termos BOOLEAN NOT NULL DEFAULT FALSE,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS livro (
    id SERIAL PRIMARY KEY,
    titulo VARCHAR(200) NOT NULL,
    autor VARCHAR(150) NOT NULL,
    isbn VARCHAR(13) NOT NULL UNIQUE,
    editora VARCHAR(100),
    edicao SMALLINT NOT NULL DEFAULT 1 CHECK (edicao >= 1),
    ano_publicacao SMALLINT NOT NULL CHECK (ano_publicacao >= 1000),
    numero_paginas INTEGER NOT NULL CHECK (numero_paginas >= 1),
    categoria VARCHAR(50) NOT NULL,
    idioma VARCHAR(10) NOT NULL DEFAULT 'pt-BR',
    sinopse TEXT,
    estoque INTEGER NOT NULL DEFAULT 1 CHECK (estoque >= 0),
    disponivel_emprestimo BOOLEAN NOT NULL DEFAULT TRUE,
    capa VARCHAR(255),
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS emprestimo (
    id SERIAL PRIMARY KEY,
    usuario VARCHAR(150) NOT NULL,
    livro VARCHAR(200) NOT NULL,
    data_emprestimo DATE NOT NULL,
    data_devolucao DATE NOT NULL,
    observacao TEXT NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CHECK (data_devolucao >= data_emprestimo),
    CHECK (data_devolucao - data_emprestimo <= 7)
);
