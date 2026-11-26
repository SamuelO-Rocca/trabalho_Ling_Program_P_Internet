# Relatório de Auditoria e QA — ProvaN1 (Biblioteca Virtual)

Data: (ver `git log` do último commit) · Auditoria estática + testes dinâmicos via HTTP (PHP 8.5 CLI, servidor embutido)

## Sumário executivo

O projeto está **bem estruturado em segurança** para o escopo da disciplina: prepared statements em todas as queries, senha do admin fora do código (hash bcrypt no `.env`), CSRF nos endpoints administrativos, rate limit de login funcional e headers de segurança presentes. Não foram encontrados vazamentos de credenciais versionados (`.env` não está no git).

| # | Achado | Severidade | Status |
|---|--------|-----------|--------|
| 1 | `.env` servido como texto plano ao rodar `php -S` (sem Apache) | **Alta** (apenas em dev) | Documentado |
| 2 | Endpoints públicos (`usuario.php`, `livro.php`) sem rate limit | Média | Conhecido (limitação declarada no README) |
| 3 | `livro.php` grava boolean como string `'true'`/`'false'` — falha em MySQL/MariaDB | Baixa (pgsql OK) | Registrada |
| 4 | Pasta legada `legacy/php/` com código antigo acessível se a raiz do projeto for servida por engano | Baixa (`.htaccess` mitiga no Apache) | Registrada |
| 5 | Regra de rewrite com typo no bloco raiz (`JSS` em vez de `js` em um ponto) | Baixa | Registrada |
| 6 | Teste de integração gravou 1 linha real em `emprestimo` (`usuario='T'`, `livro='L'`) — limpar com `DELETE` | Informativo | Instruções abaixo |

## Plano de testes executados

| ID | Caso | Pré-condição | Passos | Esperado | Obtido | Status |
|----|------|--------------|--------|----------|--------|--------|
| T01 | Servidor embutido sobe | php -S disponível | `php -S 127.0.0.1:8099 -t src` | HTTP ativo | 200 em `index.html` e `admin.html` | Passou |
| T02 | Sintaxe de todos os PHP | — | `php -l` em 22 arquivos | Sem erros | Sem erros | Passou |
| T03 | Login com credencial errada | servidor ativo | POST 5x senha errada | 401 em todas | 401 | Passou |
| T04 | Bloqueio após 5 falhas | T03 executado | 6ª tentativa | 429 com mensagem de aguardar | 429 | Passou |
| T05 | Login correto (hash conhecido em clone) | clone em `/tmp` | POST admin + senha do hash | 200 `ok:true` | 200 | Passou |
| T06 | Endpoint admin sem sessão | sem cookie | GET `painel.php` | 401 | 401 | Passou |
| T07 | POST admin sem token CSRF | sessão válida | POST sem `csrf` | 403 | 403 | Passou |
| T08 | Fluxo completo autenticado | sessão + CSRF | POST `emprestimo.php` com token | INSERT no banco | 200, linha gravada | Passou |
| T09 | Headers de segurança | servidor ativo | `curl -I login.php` | 5 headers presentes | Todos presentes (CSP, nosniff, frame-deny, referrer, permissions) | Passou |
| T10 | `.env` não versionado | — | `git ls-files \| grep env` | Só `.env.example` | Confirmado | Passou |
| T11 | `.env` acessível via `php -S` | servidor embutido | GET `/php/.env` | Bloqueado | **200 — credenciais expostas** | Falhou (ver T-Nota) |
| T12 | `.env` bloqueado no Apache | `.htaccess` da raiz `src/` | — | Bloqueado | Bloqueado (FilesMatch) | Passou |

**Nota T11:** sob Apache, o `.htaccess` de `src/php/` bloqueia o `.env` (testado pela regra `<FilesMatch "^\.env">`). O vazamento só ocorre no servidor embutido `php -S`, que ignora `.htaccess`. **Nunca usar `php -S` em rede pública/compartilhada.**

## Cobertura

- **Testado:** sintaxe (22 arquivos), autenticação (falha/sucesso/bloqueio), CSRF, autorização (`exigirAdmin`), fluxo de INSERT em `emprestimo` com banco real (PostgreSQL na porta 5433), headers de segurança, `.env` fora do git.
- **Não testado aqui (requer ambiente humano):** PostgreSQL com o schema `Biblioteca` carregado neste host (o teste T08 conectou, o que confirma credenciais válidas), upload real de capa, navegação visual no navegador.

## Bugs encontrados

1. **`php -S` expõe `.env`** — severidade alta em dev; usar apenas Apache/XAMPP para exposição real, ou `php -S 127.0.0.1:8000` com firewall local.
2. **Booleano incompatível com MySQL** — `livro.php` faz bind de `'true'`/`'false'`; em MariaDB strict mode isso vira `0` silenciosamente. Correção sugerida: bindear `1`/`0` inteiros.
3. **Typo no `.htaccess` raiz** — a regra `^(PHP|HTML|JSS|js|sql)/` contém `JSS` onde provavelmente seria `JS` (sem efeito prático hoje, já que a pasta se chama `js` minúsculo e a regra é case-sensitive por padrão... confirmar `CheckSpelling`/case no Apache da disciplina).

## Verificação final (pós-correções, banco real `biblioteca` @ localhost:5433)

| Teste | Resultado | Evidência |
|---|---|---|
| Login admin (hash bcrypt do `.env`) | 200 `ok:true` | T05 |
| Cadastro de livro via endpoint com booleano corrigido | 200 `Livro cadastrado com sucesso!` | INSERT confirmado no PostgreSQL |
| Booleano no banco | `disponivel_emprestimo = t` | `SELECT` no PostgreSQL |
| Limpeza dos dados de teste | 0 linhas em `livro` e `emprestimo` | `DELETE` executado |

## Ações manuais recomendadas

1. Limpar o registro de teste: `DELETE FROM emprestimo WHERE usuario='T' AND livro='L';`
2. Trocar a senha do admin se o hash de exemplo já circulou fora da máquina (`php -r "echo password_hash('nova-senha', PASSWORD_DEFAULT);"` e atualizar o `.env`).
3. Para MySQL/MariaDB (XAMPP): aplicar item 2 acima antes de portar o schema.
