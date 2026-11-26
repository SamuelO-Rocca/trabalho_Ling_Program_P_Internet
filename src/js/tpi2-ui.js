(function () {
    'use strict';

    var config = window.TPI2_CONFIG || {};

    var base = '';
    var eu = document.currentScript;
    if (eu && eu.src) {
        var parte = eu.src.split('?')[0];
        base = parte.replace(/\/js\/tpi2-ui\.js$/, '');
    }

    var CHAVE_SESSAO = 'tpi2_sessao';
    var CHAVE_NAVEGACAO = 'tpi2_navegacao';

    function injetarCss() {
        var jaExiste = document.querySelector('link[data-tpi2]');
        if (jaExiste) {
            return;
        }
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = (base ? base + '/' : '') + 'css/estilo.css';
        link.setAttribute('data-tpi2', 'estilo');
        document.head.appendChild(link);
    }

    function toast(mensagem, tipo) {
        var caixa = document.createElement('div');
        caixa.className = 'tpi2-toast' + (tipo === 'erro' ? ' tpi2-toast-erro' : '');
        caixa.setAttribute('role', 'alert');
        caixa.textContent = mensagem;
        document.body.appendChild(caixa);

        setTimeout(function () {
            caixa.classList.add('saindo');
            setTimeout(function () {
                if (caixa.parentNode) {
                    caixa.parentNode.removeChild(caixa);
                }
            }, 350);
        }, 3200);
    }

    function limparErros(form) {
        var comErro = form.querySelectorAll('.tpi2-erro');
        for (var i = 0; i < comErro.length; i++) {
            comErro[i].classList.remove('tpi2-erro');
        }
    }

    function marcarErro(idCampo) {
        var campo = document.getElementById(idCampo);
        if (campo) {
            campo.classList.remove('tpi2-erro');
            void campo.offsetWidth;
            campo.classList.add('tpi2-erro');
            campo.focus();
            campo.addEventListener('input', function () {
                campo.classList.remove('tpi2-erro');
            }, { once: true });
        }
    }

    function lerJson(alvo, chave, padrao) {
        try {
            var bruto = alvo.getItem(chave);
            return bruto ? JSON.parse(bruto) : padrao;
        } catch (erro) {
            return padrao;
        }
    }

    function salvarJson(alvo, chave, valor) {
        try {
            alvo.setItem(chave, JSON.stringify(valor));
        } catch (erro) {
            return;
        }
    }

    function sessaoAtual() {
        return lerJson(sessionStorage, CHAVE_SESSAO, null);
    }

    function temSessao() {
        return !!sessaoAtual();
    }

    function ehAdmin() {
        var sessao = sessaoAtual();
        return !!sessao && sessao.tipo === 'admin';
    }

    function entrarAdmin() {
        salvarJson(sessionStorage, CHAVE_SESSAO, {
            tipo: 'admin',
            nome: 'admin'
        });
    }

    function entrarUsuario(nome, email) {
        salvarJson(sessionStorage, CHAVE_SESSAO, {
            tipo: 'usuario',
            nome: nome,
            email: email
        });
    }

    function encerrarSessaoServidor() {
        if (!temSessao()) {
            return Promise.resolve();
        }
        var url = (base ? base + '/' : '') + 'php/logout.php';
        return fetch(url, { method: 'POST' }).catch(function () {
            return null;
        });
    }

    function sair() {
        encerrarSessaoServidor().then(function () {
            sessionStorage.removeItem(CHAVE_SESSAO);
        });
    }

    function paginaAtual() {
        return location.pathname.split('?')[0].split('#')[0];
    }

    function paginaInicial() {
        return (base ? base + '/' : '') + 'index.html';
    }

    function registrarNavegacao() {
        var pilha = lerJson(sessionStorage, CHAVE_NAVEGACAO, []);
        var atual = paginaAtual();

        if (pilha[pilha.length - 1] !== atual) {
            pilha.push(atual);
            if (pilha.length > 20) {
                pilha.shift();
            }
            salvarJson(sessionStorage, CHAVE_NAVEGACAO, pilha);
        }
    }

    function voltar() {
        var pilha = lerJson(sessionStorage, CHAVE_NAVEGACAO, []);
        pilha.pop();
        var alvo = pilha.pop();
        salvarJson(sessionStorage, CHAVE_NAVEGACAO, pilha);
        location.href = alvo ? alvo : paginaInicial();
    }

    function naPaginaInicial() {
        var caminho = paginaAtual();
        return caminho === '/' || /\/$/.test(caminho) || /(^|\/)index\.html$/.test(caminho);
    }

    function criarLinkVoltar() {
        var opcao = document.createElement('a');
        opcao.href = paginaInicial();
        opcao.textContent = 'Voltar';
        opcao.className = 'tpi2-link-voltar';
        opcao.addEventListener('click', function (evento) {
            evento.preventDefault();
            voltar();
        });
        return opcao;
    }

    function injetarVoltar() {
        if (naPaginaInicial()) {
            return;
        }
        var nav = document.querySelector('.menu-principal');
        if (nav) {
            if (nav.querySelector('.tpi2-link-voltar')) {
                return;
            }
            nav.insertBefore(criarLinkVoltar(), nav.firstChild);
            return;
        }
        if (document.querySelector('.tpi2-voltar-solto')) {
            return;
        }
        var corpo = document.querySelector('main') || document.body;
        var linha = document.createElement('p');
        linha.className = 'tpi2-voltar-solto';
        linha.appendChild(criarLinkVoltar());
        corpo.appendChild(linha);
    }

    function injetarSessao() {
        var menu = document.querySelector('.menu-principal') || document.querySelector('header');
        if (!menu) {
            return;
        }
        var antiga = menu.querySelector('.tpi2-sessao');
        if (antiga) {
            antiga.parentNode.removeChild(antiga);
        }
        var sessao = sessaoAtual();
        if (!sessao) {
            return;
        }
        var caixa = document.createElement('div');
        caixa.className = 'tpi2-sessao';
        var texto = document.createElement('span');
        texto.textContent = 'Sessão: ' + sessao.nome + (sessao.tipo === 'admin' ? ' (administrador)' : '');
        var botao = document.createElement('button');
        botao.type = 'button';
        botao.textContent = 'Sair';
        botao.className = 'tpi2-btn-sair';
        botao.addEventListener('click', function () {
            sair();
            location.href = paginaInicial();
        });
        caixa.appendChild(texto);
        caixa.appendChild(botao);
        menu.appendChild(caixa);
    }

    function injetarAvisoEmprestimo() {
        var form = document.getElementById('formEmprestimo');
        if (!form || temSessao() || document.getElementById('tpi2-aviso-conta')) {
            return;
        }
        var aviso = document.createElement('div');
        aviso.id = 'tpi2-aviso-conta';
        aviso.className = 'tpi2-aviso';

        var texto = document.createElement('p');
        texto.textContent = 'O registro de empréstimo é restrito ao administrador. Faça login no painel para continuar.';

        var criar = document.createElement('p');
        var linkCriar = document.createElement('a');
        linkCriar.href = (base ? base + '/' : '') + 'admin.html';
        linkCriar.textContent = 'Ir para o painel administrativo';
        criar.appendChild(linkCriar);

        aviso.appendChild(texto);
        aviso.appendChild(criar);
        form.parentNode.insertBefore(aviso, form);
    }

    function exigirConta(form) {
        if (form.id !== 'formEmprestimo' || temSessao()) {
            return false;
        }
        toast('Faça login no painel administrativo para registrar um empréstimo.', 'erro');
        setTimeout(function () {
            location.href = (base ? base + '/' : '') + 'admin.html';
        }, 900);
        return true;
    }

    function buscarCsrf() {
        var url = (base ? base + '/' : '') + 'php/csrf.php';
        return fetch(url, { method: 'GET', credentials: 'same-origin' })
            .then(function (resposta) {
                return resposta.json();
            })
            .then(function (dados) {
                return dados && dados.csrf ? dados.csrf : null;
            })
            .catch(function () {
                return null;
            });
    }

    function enviarAoPhp(form) {
        if (!config.phpScript) {
            return;
        }
        var url = (base ? base + '/' : '') + 'php/' + config.phpScript;

        function postar(token) {
            var dados = new FormData(form);
            if (token) {
                dados.set('csrf', token);
            }
            toast('Enviando ao servidor...');
            fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                body: dados
            }).then(function (resposta) {
                return resposta.text().then(function (texto) {
                    return { status: resposta.status, texto: texto };
                });
            }).then(function (resultado) {
                if (resultado.status === 200) {
                    toast(resultado.texto || 'Registro salvo com sucesso!');
                    form.reset();
                    if (typeof config.depoisDeEnviar === 'function') {
                        config.depoisDeEnviar();
                    }
                } else if (resultado.status === 401) {
                    toast('Faça login no painel administrativo antes de enviar.', 'erro');
                } else if (resultado.status === 403) {
                    toast('Sessão expirada. Recarregue a página e faça login de novo.', 'erro');
                } else {
                    toast(resultado.texto || 'Não foi possível salvar. Verifique os campos.', 'erro');
                }
            }).catch(function () {
                toast('Não foi possível falar com o servidor agora.', 'erro');
            });
        }

        if (config.csrf) {
            buscarCsrf().then(postar);
        } else {
            postar(null);
        }
    }

    function iniciar() {
        injetarCss();
        registrarNavegacao();
        injetarVoltar();
        injetarSessao();
        injetarAvisoEmprestimo();

        var form = config.formId ? document.getElementById(config.formId) : null;
        if (!form) {
            return;
        }

        form.addEventListener('submit', function (evento) {
            evento.preventDefault();
            limparErros(form);

            if (exigirConta(form)) {
                return;
            }

            var erro = config.validar ? config.validar(form) : null;

            if (erro) {
                marcarErro(erro.campo);
                toast(erro.msg, 'erro');
                return;
            }

            enviarAoPhp(form);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciar);
    } else {
        iniciar();
    }

    window.TPI2 = {
        toast: toast,
        marcarErro: marcarErro,
        sessaoAtual: sessaoAtual,
        ehAdmin: ehAdmin,
        entrarAdmin: entrarAdmin,
        entrarUsuario: entrarUsuario,
        sair: sair,
        encerrarSessaoServidor: encerrarSessaoServidor,
        atualizarSessao: injetarSessao,
        voltar: voltar,
        base: base
    };
}());
