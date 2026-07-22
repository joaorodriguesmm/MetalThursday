/**
 * Configura os comportamentos da página de edição do perfil.
 *
 * Este ficheiro limita-se a associar os módulos reutilizáveis aos elementos
 * específicos da página. A validação definitiva permanece no servidor.
 *
 * @since 1.0.0
 * @version 2.0.0
 */

import ValidadorFicheiro from '../modulos/ValidadorFicheiro';
import ValidadorFormulario from '../modulos/ValidadorFormulario';
import AlternadorPalavraPasse from '../modulos/AlternadorPalavraPasse';
import SeletorPermissoes from '../modulos/SeletorPermissoes';
import GestorFotografiaPerfil from '../modulos/GestorFotografiaPerfil';
import InicializadorTooltips from '../modulos/InicializadorTooltips';

/**
 * Seletores dos elementos da página.
 *
 * @type {Readonly<Record<string, string>>}
 *
 * @since 2.0.0
 * @version 1.0.0
 */
const SELETORES = Object.freeze({
    formularioPerfil: '#formulario-atualizar-perfil',
    formularioPalavraPasse: '#formulario-palavra-passe',

    fotografia: '#fotografia',
    previsualizacaoFotografia: '#previsualizacao-fotografia',
    iniciaisAvatar: '#iniciais-avatar',
    erroFotografia: '#erro-fotografia',
    textoFotografia: '#texto-fotografia',

    permissaoTodas: '[data-permissao-todas="true"]',
    itemPermissaoEmail: '[data-item-permissao-email]',

    alternadorPalavraPasse: '[data-alvo-palavra-passe]',

    tooltip: '[data-bs-toggle="tooltip"]',
});

/**
 * Tipos MIME permitidos para as fotografias.
 *
 * Esta lista deve permanecer alinhada com
 * `AtualizarPerfilRequest`.
 *
 * @type {ReadonlyArray<string>}
 *
 * @since 2.0.0
 * @version 1.0.0
 */
const TIPOS_FOTOGRAFIA_PERMITIDOS = Object.freeze([
    'image/jpeg',
    'image/png',
    'image/webp',
]);

/**
 * Tamanho máximo permitido para a fotografia, em bytes.
 *
 * @type {number}
 *
 * @since 2.0.0
 * @version 1.0.0
 */
const TAMANHO_MAXIMO_FOTOGRAFIA = 10 * 1024 * 1024;

/**
 * Inicia a gestão da fotografia do perfil.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.0.0
 */
function iniciarFotografiaPerfil() {
    const gestorFotografia = new GestorFotografiaPerfil(
        SELETORES.fotografia,
        SELETORES.previsualizacaoFotografia,
        SELETORES.iniciaisAvatar,
    );

    if (!gestorFotografia.estaDisponivel()) {
        return;
    }

    const campoFotografia =
        gestorFotografia.obterCampoFicheiro();

    if (!(campoFotografia instanceof HTMLInputElement)) {
        return;
    }

    new ValidadorFicheiro(
        campoFotografia,
        {
            tiposPermitidos:
                TIPOS_FOTOGRAFIA_PERMITIDOS,

            tamanhoMaximo:
                TAMANHO_MAXIMO_FOTOGRAFIA,

            seletorMensagemErro:
                SELETORES.erroFotografia,

            seletorTextoFicheiro:
                SELETORES.textoFotografia,

            textoPadrao:
                'Selecionar fotografia',

            textoSelecionado:
                'Alterar fotografia',

            aoFicheiroInvalido: () => {
                gestorFotografia
                    .restaurarPrevisualizacao();
            },

            aoFicheiroValido: (ficheiro) => {
                gestorFotografia
                    .previsualizarImagem(ficheiro);
            },

            aoLimparSelecao: () => {
                gestorFotografia
                    .restaurarPrevisualizacao();
            },
        },
    );
}

/**
 * Inicia a validação de apoio do formulário dos dados gerais.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.0.0
 */
function iniciarValidacaoPerfil() {
    if (!document.querySelector(SELETORES.formularioPerfil)) {
        return;
    }

    new ValidadorFormulario(
        SELETORES.formularioPerfil,
        {
            regras: {
                nome: [
                    'obrigatorio',
                    'minimo:3',
                    'maximo:255',
                ],

                email: [
                    'obrigatorio',
                    'email',
                    'maximo:255',
                ],
            },

            mensagens: {
                nome: {
                    obrigatorio:
                        'Por favor, insere o teu nome.',

                    minimo:
                        'O nome deve ter, pelo menos, 3 caracteres.',

                    maximo:
                        'O nome não pode ter mais de 255 caracteres.',
                },

                email: {
                    obrigatorio:
                        'Por favor, insere o teu endereço de e-mail.',

                    email:
                        'Por favor, insere um endereço de e-mail válido.',

                    maximo:
                        'O endereço de e-mail não pode ter mais de 255 caracteres.',
                },
            },
        },
    );
}

/**
 * Inicia o seletor das permissões de e-mail.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.0.0
 */
function iniciarPermissoesEmail() {
    const campoTodas = document.querySelector(
        SELETORES.permissaoTodas,
    );

    if (!(campoTodas instanceof HTMLInputElement)) {
        return;
    }

    const itensPermissoes = document.querySelectorAll(
        SELETORES.itemPermissaoEmail,
    );

    if (itensPermissoes.length === 0) {
        return;
    }

    new SeletorPermissoes(
        campoTodas,
        itensPermissoes,
    );
}

/**
 * Inicia a validação de apoio da alteração da palavra-passe.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.0.0
 */
function iniciarValidacaoPalavraPasse() {
    if (
        !document.querySelector(
            SELETORES.formularioPalavraPasse,
        )
    ) {
        return;
    }

    new ValidadorFormulario(
        SELETORES.formularioPalavraPasse,
        {
            regras: {
                palavra_passe_atual: [
                    'obrigatorio',
                ],

                nova_palavra_passe: [
                    'obrigatorio',
                    'minimo:12',
                    'maiuscula',
                    'minuscula',
                    'numero',
                    'simbolo',
                    'diferente:palavra_passe_atual',
                ],

                confirmacao_nova_palavra_passe: [
                    'obrigatorio',
                    'confirmado:nova_palavra_passe',
                ],
            },

            mensagens: {
                palavra_passe_atual: {
                    obrigatorio:
                        'Por favor, insere a tua palavra-passe atual.',
                },

                nova_palavra_passe: {
                    obrigatorio:
                        'Por favor, insere a nova palavra-passe.',

                    minimo:
                        'A nova palavra-passe deve ter, pelo menos, 12 caracteres.',

                    maiuscula:
                        'A nova palavra-passe deve conter uma letra maiúscula.',

                    minuscula:
                        'A nova palavra-passe deve conter uma letra minúscula.',

                    numero:
                        'A nova palavra-passe deve conter um número.',

                    simbolo:
                        'A nova palavra-passe deve conter um símbolo.',

                    diferente:
                        'A nova palavra-passe deve ser diferente da palavra-passe atual.',
                },

                confirmacao_nova_palavra_passe: {
                    obrigatorio:
                        'Por favor, confirma a nova palavra-passe.',

                    confirmado:
                        'A confirmação da nova palavra-passe não coincide.',
                },
            },
        },
    );
}

/**
 * Inicia a apresentação e ocultação das palavras-passe.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.0.0
 */
function iniciarAlternadoresPalavraPasse() {
    const alternadores = document.querySelectorAll(
        SELETORES.alternadorPalavraPasse,
    );

    if (alternadores.length === 0) {
        return;
    }

    new AlternadorPalavraPasse(
        alternadores,
    );
}

/**
 * Inicia os tooltips existentes na página.
 *
 * @return {void}
 *
 * @since 1.0.0
 * @version 2.0.0
 */
function iniciarTooltips() {
    new InicializadorTooltips(
        SELETORES.tooltip,
    );
}

/**
 * Inicia os comportamentos da página.
 *
 * @return {void}
 *
 * @since 2.0.0
 * @version 1.0.0
 */
function iniciarPaginaPerfil() {
    iniciarFotografiaPerfil();
    iniciarValidacaoPerfil();
    iniciarPermissoesEmail();
    iniciarValidacaoPalavraPasse();
    iniciarAlternadoresPalavraPasse();
    iniciarTooltips();
}

/**
 * Inicia a página depois de a estrutura HTML estar disponível.
 *
 * @since 1.0.0
 * @version 2.0.0
 */
document.addEventListener(
    'DOMContentLoaded',
    iniciarPaginaPerfil,
);
