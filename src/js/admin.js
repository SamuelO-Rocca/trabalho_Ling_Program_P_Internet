(function () {
    'use strict';

    var nucleo = window.TPI2;
    if (!nucleo) {
        return;
    }

    function elemento(id) {
        return document.getElementById(id);
    }

    function render() {
        var login = elemento('tpi2-login');
        var painel = elemento('tpi2-painel');
        if (!login || !painel) {
            return;
        }
        var admin = nucleo.ehAdmin();
        login.hidden = admin;
        painel.hidden = !admin;

        var senha = elemento('senhaAdmin');
        if (senha) {
            senha.value = '';
        }
    }

    function carregarResumo() {
        var grid = elemento('tpi2-resumo-grid');
        if (!grid) {
            return;
        }
        var url = (nucleo.base ? nucleo.base + '/' : '') + 'php/painel.php';
        fetch(url, { method: 'GET', credentials: 'same-origin' })
            .then(function (resposta) {
                return resposta.json();
            })
            .then(function (dados) {
                grid.textContent = '';
                if (!dados || !dados.totais) {
                    grid.textContent = 'Resumo indisponível.';
                    return;
                }
                Object.keys(dados.totais).forEach(function (chave) {
                    var item = dados.totais[chave];
                    var caixa = document.createElement('div');
                    caixa.className = 'tpi2-resumo-item';
                    var numero = document.createElement('strong');
                    numero.textContent = item.total;
                    var rotulo = document.createElement('span');
                    rotulo.textContent = item.titulo;
                    caixa.appendChild(numero);
                    caixa.appendChild(rotulo);
                    grid.appendChild(caixa);
                });
            })
            .catch(function () {
                grid.textContent = 'Não foi possível carregar o resumo agora.';
            });
    }

    function liberar() {
        nucleo.entrarAdmin();
        render();
        nucleo.atualizarSessao();
        carregarResumo();
        nucleo.toast('Acesso ao painel liberado.');
    }

    function falharLogin(msg, campo) {
        nucleo.marcarErro(campo);
        nucleo.toast(msg, 'erro');
        var botao = elemento('btnEntrarAdmin');
        if (botao) {
            botao.disabled = false;
        }
        var senha = elemento('senhaAdmin');
        if (senha) {
            senha.value = '';
            senha.focus();
        }
    }

    function entrar(identidade, senha) {
        var url = (nucleo.base ? nucleo.base + '/' : '') + 'php/login.php';
        var corpo = 'usuario=' + encodeURIComponent(identidade) +
                    '&senha=' + encodeURIComponent(senha);

        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: corpo
        })
            .then(function (resposta) {
                return resposta.json().then(function (dados) {
                    return { status: resposta.status, dados: dados };
                });
            })
            .then(function (resultado) {
                if (resultado.status === 200 && resultado.dados.ok === true) {
                    liberar();
                } else {
                    falharLogin(resultado.dados.mensagem || 'Usuário ou senha incorretos.', 'senhaAdmin');
                }
            })
            .catch(function () {
                falharLogin('Não foi possível validar o acesso agora. Tente novamente.', 'senhaAdmin');
            });
    }

    function iniciarAdmin() {
        nucleo.sair();
        nucleo.encerrarSessaoServidor();
        nucleo.atualizarSessao();

        var form = elemento('formLoginAdmin');
        if (form) {
            form.addEventListener('submit', function (evento) {
                evento.preventDefault();

                var identidade = elemento('loginAdmin').value;
                var senha = elemento('senhaAdmin').value;

                if (!identidade.trim()) {
                    falharLogin('Informe o usuário do administrador.', 'loginAdmin');
                    return;
                }
                if (!senha) {
                    falharLogin('Informe a senha do administrador.', 'senhaAdmin');
                    return;
                }

                var botao = elemento('btnEntrarAdmin');
                if (botao) {
                    botao.disabled = true;
                }
                nucleo.toast('Verificando credenciais...');
                entrar(identidade, senha);
            });
        }

        var botaoSair = elemento('btnSairAdmin');
        if (botaoSair) {
            botaoSair.addEventListener('click', function () {
                nucleo.sair();
                nucleo.encerrarSessaoServidor().then(function () {
                    render();
                    nucleo.atualizarSessao();
                    nucleo.toast('Sessão encerrada.');
                });
            });
        }

        var botaoVoltar = elemento('btnVoltarLogin');
        if (botaoVoltar) {
            botaoVoltar.addEventListener('click', function () {
                nucleo.voltar();
            });
        }

        render();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciarAdmin);
    } else {
        iniciarAdmin();
    }
}());
