(function () {
    'use strict';

    var nucleo = window.TPI2;
    if (!nucleo) {
        return;
    }

    function elemento(id) {
        return document.getElementById(id);
    }

    function falharLogin(msg, campo) {
        nucleo.marcarErro(campo);
        nucleo.toast(msg, 'erro');
        var botao = elemento('btnEntrarUsuario');
        if (botao) {
            botao.disabled = false;
        }
        var senha = elemento('senhaLogin');
        if (senha) {
            senha.value = '';
            senha.focus();
        }
    }

    function entrar(email, senha) {
        var url = (nucleo.base ? nucleo.base + '/' : '') + 'php/login_usuario.php';
        var corpo = 'email=' + encodeURIComponent(email) +
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
                    nucleo.entrarUsuario(resultado.dados.nome, resultado.dados.email);
                    nucleo.atualizarSessao();
                    nucleo.toast('Login realizado com sucesso!');
                    setTimeout(function () {
                        location.href = (nucleo.base ? nucleo.base + '/' : '') + 'index.html';
                    }, 900);
                } else {
                    falharLogin(resultado.dados.mensagem || 'E-mail ou senha incorretos.', 'senhaLogin');
                }
            })
            .catch(function () {
                falharLogin('Não foi possível validar o acesso agora. Tente novamente.', 'senhaLogin');
            });
    }

    function iniciarLogin() {
        var form = elemento('formLoginUsuario');
        if (form) {
            form.addEventListener('submit', function (evento) {
                evento.preventDefault();

                var email = elemento('emailLogin').value.trim();
                var senha = elemento('senhaLogin').value;

                if (!email) {
                    falharLogin('Informe o e-mail do cadastro.', 'emailLogin');
                    return;
                }
                if (!senha) {
                    falharLogin('Informe a senha.', 'senhaLogin');
                    return;
                }

                var botao = elemento('btnEntrarUsuario');
                if (botao) {
                    botao.disabled = true;
                }
                nucleo.toast('Verificando credenciais...');
                entrar(email, senha);
            });
        }

        var botaoVoltar = elemento('btnVoltarLogin');
        if (botaoVoltar) {
            botaoVoltar.addEventListener('click', function () {
                nucleo.voltar();
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciarLogin);
    } else {
        iniciarLogin();
    }
}());
