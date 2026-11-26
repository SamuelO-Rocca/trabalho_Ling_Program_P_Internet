# Biblioteca Virtual — pitch do projeto

## O problema

O controle de uma biblioteca pequena (usuários, livros, autores, categorias e
empréstimos) ainda passa por papel, planilhas ou WhatsApp. Resultado: livro
emprestado que ninguém lembra de devolver, cadastro duplicado e zero visibilidade
do acervo.

## A solução

Um sistema web simples e direto, em português, para cadastrar e acompanhar o
acervo da biblioteca: usuários, livros, autores, categorias e empréstimos com
prazo máximo de 7 dias. O painel administrativo exige login a cada acesso, e a
validação acontece tanto no navegador (resposta imediata) quanto no servidor
(PHP, antes de qualquer escrita no banco).

## Para quem é

Bibliotecas de escola, clubes de leitura, igrejas e pequenos acervos que precisam
de controle básico sem instalar sistema pesado.

## Funcionalidades

| Área | O que faz |
|---|---|
| Usuários | Cadastro com CPF (11 dígitos), e-mail válido, endereço e senha mínima de 8 caracteres |
| Livros | Título, ISBN (10 ou 13 dígitos), ano de publicação, categoria, estoque e capa |
| Autores | Nome, nacionalidade, nascimento, e-mail e biografia; grava no PostgreSQL via PDO com prepared statement |
| Categorias | Código, status (ativa/inativa) e localização física |
| Empréstimos | Usuário + livro + datas; o sistema calcula o prazo e bloqueia acima de 7 dias |
| Painel admin | Login obrigatório em cada acesso; sessão só vale após validação no servidor |

## Stack

- **Front-end:** HTML5 + CSS3 + JavaScript puro (sem framework)
- **Back-end:** PHP 8 com PDO (PostgreSQL)
- **Banco:** PostgreSQL (`Biblioteca`)
- **Deploy local:** Apache (`/ProvaN1/src/`) ou `php -S` para testes

## Estrutura

```
src/
├── index.html / admin.html   páginas inicial e painel administrativo
├── www/                      formulários (usuário, livro, autor, categoria, empréstimo)
├── js/                       validações e UI (tpi2-ui.js, admin.js, um JS por formulário)
├── php/                      endpoints (login, usuario, livro, autor, categoria, emprestimo, conexao)
└── css/estilo.css            identidade visual (vinho/dourado/areia)
```

A pasta raiz (`HTML/`, `JSS/`, `PHP/`, `css/`) é a versão plana original da
prova; `src/` é a versão empacotada implantada no Apache.

## Segurança (endurecida nesta rodada)

- Credenciais do banco e do admin **fora do código**: arquivo `.env` (ignorado
  pelo git) carregado por `php/config.php`; `.env.example` documenta as chaves.
- Conexão PDO com `ERRMODE_EXCEPTION`, `EMULATE_PREPARES = false`, fetch
  associativo e `client_encoding UTF8`.
- Erros de banco vão para o `error_log` e o usuário recebe mensagem genérica
  (nada de vazar host/SQL no navegador).
- Login server-side: `password_verify` contra hash bcrypt, comparação
  `hash_equals` para o usuário, sessão PHP com cookie `HttpOnly` + `SameSite=Lax`
  e `session_regenerate_id`; resposta em JSON com código HTTP correto.
- Credenciais de admin **não existem mais no JavaScript** — o painel só libera
  com resposta 200 do servidor; o fallback local foi removido.
- Validação de CPF por expressão regular (antes era só contagem de caracteres).

Limitações conhecidas (escopo da disciplina): autenticação de usuários comuns
é demonstrativa no cliente (localStorage), e os formulários ainda não têm
token CSRF — próximos passos naturais.

## Como rodar

```bash
# 1. Configure as credenciais locais (uma vez)
cp src/php/.env.example src/php/.env   # edite DB_PASS e ADMIN_PASS_HASH

# 2. Suba o PostgreSQL com o banco "Biblioteca" e rode o servidor
php -S 127.0.0.1:8000 -t src
# abra http://127.0.0.1:8000/index.html
```

No Apache da máquina da disciplina, a raiz do projeto é `src/`
(ex.: `http://localhost/ProvaN1/src/`).

Para gerar um hash de senha para o `.env`:

```bash
php -r "echo password_hash('sua-senha', PASSWORD_DEFAULT), PHP_EOL;"
```

## Próximos passos

1. Migrar a autenticação de usuários para o servidor (tabela `usuario` com
   `password_hash`, sessão PHP em todos os endpoints).
2. Adicionar token CSRF nos formulários e `session_start()` com verificação de
   `$_SESSION['tipo']` nos endpoints administrativos.
3. CRUD completo (listar/editar/excluir) para livros, autores e empréstimos.
4. Upload real da capa do livro com validação de tipo/tamanho no PHP.
