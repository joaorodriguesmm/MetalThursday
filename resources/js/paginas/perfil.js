import AlternadorPalavraPasse
    from '../modulos/AlternadorPalavraPasse';

import GestorFotografiaPerfil
    from '../modulos/GestorFotografiaPerfil';

import InicializadorTooltips
    from '../modulos/InicializadorTooltips';

import SeletorPermissoes
    from '../modulos/SeletorPermissoes';

import ValidadorFicheiro
    from '../modulos/ValidadorFicheiro';

import ValidadorFormulario
    from '../modulos/ValidadorFormulario';

/**
 * Configura os comportamentos da página de edição do perfil.
 *
 * A validação executada no navegador melhora a experiência do utilizador.
 * A validação definitiva permanece no servidor.
 *
 * @since 1.0.0
 */

/**
 * Seletores dos elementos da página.
 *
 * @type {Readonly<Record<string, string>>}
 *
 * @since 2.0.0
 */
const SELETORES = Object.freeze({
    formularioPerfil:
        '#formulario-atualizar-perfil',

    formularioPalavraPasse:
        '#formulario-palavra-passe',

    fotografia:
        '#fotografia',

    previsualizacaoFotografia:
        '#previsualizacao-fotografia',

    iniciaisAvatar:
        '#iniciais-avatar',

    erroFotografia:
        '#erro-fotografia',

    textoFotografia:
        '#texto-fotografia',

    permissaoTodas:
        '[data-permissao-todas="true"]',

    itemPermissaoEmail:
        '[data-item-permissao-email]',

    alternadorPalavraPasse:
        '[data-alvo-palavra-passe]',

    tooltip:
        '[data-bs-toggle="tooltip"]',
});

/**
 * Tamanho máximo permitido para a fotografia, em bytes.
 *
 * Corresponde ao limite de 10 MiB aplicado pelo servidor e comunicado na
 * interface.
 *
 * @type {number}
 *
 * @since 2.0.0
 */
const TAMANHO_MAXIMO_FOTOGRAFIA =
    10 * (1024 ** 2);

/**
 * Comprimento mínimo da nova palavra-passe.
 *
 * Este valor permanece local enquanto o formulário não declarar o limite
 * através do atributo `minlength`.
 *
 * @type {number}
 *
 * @since 2.0.0
 */
const COMPRIMENTO_MINIMO_PALAVRA_PASSE =
    12;

/**
 * Obtém obrigatoriamente um campo de entrada através do respetivo nome.
 *
 * @param {HTMLFormElement} formulario Formulário pesquisado.
 * @param {string} nome Nome HTML do campo.
 *
 * @returns {HTMLInputElement} Campo encontrado.
 *
 * @throws {TypeError} Quando o campo esperado não existe.
 *
 * @since 2.0.0
 */
function obterCampoEntradaFormulario(
    formulario,
    nome,
) {
    const campo =
        formulario.elements.namedItem(
            nome,
        );

    if (!(campo instanceof HTMLInputElement)) {
        throw new TypeError(
            `O formulário "${formulario.id}" deve possuir o campo "${nome}".`,
        );
    }

    return campo;
}

/**
 * Obtém obrigatoriamente o comprimento máximo declarado num campo.
 *
 * @param {HTMLInputElement} campo Campo pesquisado.
 *
 * @returns {number} Comprimento máximo positivo.
 *
 * @throws {TypeError} Quando o campo não declara um limite válido.
 *
 * @since 2.0.0
 */
function obterComprimentoMaximo(
    campo,
) {
    if (
        !Number.isInteger(
            campo.maxLength,
        )
        || campo.maxLength <= 0
    ) {
        throw new TypeError(
            `O campo "${campo.name}" deve possuir um comprimento máximo válido.`,
        );
    }

    return campo.maxLength;
}

/**
 * Obtém o comprimento máximo opcional declarado num campo.
 *
 * @param {HTMLInputElement} campo Campo pesquisado.
 *
 * @returns {number|null} Comprimento máximo ou nulo.
 *
 * @since 2.0.0
 */
function obterComprimentoMaximoOpcional(
    campo,
) {
    return Number.isInteger(
        campo.maxLength,
    )
    && campo.maxLength > 0
        ? campo.maxLength
        : null;
}

/**
 * Acrescenta uma regra de comprimento máximo quando existe um limite.
 *
 * @param {Array<string|Function>} regras Regras base.
 * @param {number|null} comprimentoMaximo Comprimento máximo opcional.
 *
 * @returns {Array<string|Function>} Regras finais.
 *
 * @since 2.0.0
 */
function acrescentarRegraMaximo(
    regras,
    comprimentoMaximo,
) {
    return comprimentoMaximo === null
        ? [
            ...regras,
        ]
        : [
            ...regras,
            `maximo:${comprimentoMaximo}`,
        ];
}

/**
 * Obtém os tipos declarados no atributo `accept` do campo da fotografia.
 *
 * A validação estrutural dos MIME é efetuada pelo ValidadorFicheiro.
 *
 * @param {HTMLInputElement} campoFotografia Campo da fotografia.
 *
 * @returns {Array<string>} Tipos declarados.
 *
 * @throws {TypeError} Quando o campo não declara tipos permitidos.
 *
 * @since 2.0.0
 */
function obterTiposFotografiaPermitidos(
    campoFotografia,
) {
    const tipos =
        campoFotografia.accept
            .split(',')
            .map(
                (tipo) =>
                    tipo.trim().toLowerCase(),
            )
            .filter(
                (tipo) =>
                    tipo !== '',
            );

    if (tipos.length === 0) {
        throw new TypeError(
            'O campo da fotografia deve declarar os tipos MIME permitidos.',
        );
    }

    return Array.from(
        new Set(
            tipos,
        ),
    );
}

/**
 * Inicia a gestão e a validação da fotografia do perfil.
 *
 * @returns {void}
 *
 * @since 1.0.0
 */
function iniciarFotografiaPerfil() {
    const gestorFotografia =
        new GestorFotografiaPerfil(
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
        throw new TypeError(
            'Não foi encontrado um campo válido para a fotografia do perfil.',
        );
    }

    new ValidadorFicheiro(
        campoFotografia,
        {
            tiposPermitidos:
                obterTiposFotografiaPermitidos(
                    campoFotografia,
                ),

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

            aoFicheiroValido: (ficheiro) => {
                gestorFotografia.previsualizarImagem(
                    ficheiro,
                );
            },

            aoFicheiroInvalido: () => {
                gestorFotografia
                    .restaurarPrevisualizacao();
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
 * @returns {void}
 *
 * @since 1.0.0
 */
function iniciarValidacaoPerfil() {
    const formulario =
        document.querySelector(
            SELETORES.formularioPerfil,
        );

    if (!(formulario instanceof HTMLFormElement)) {
        return;
    }

    const campoNome =
        obterCampoEntradaFormulario(
            formulario,
            'nome',
        );

    const campoEmail =
        obterCampoEntradaFormulario(
            formulario,
            'email',
        );

    const comprimentoMaximoNome =
        obterComprimentoMaximo(
            campoNome,
        );

    const comprimentoMaximoEmail =
        obterComprimentoMaximo(
            campoEmail,
        );

    new ValidadorFormulario(
        formulario,
        {
            regras: {
                nome: [
                    'obrigatorio',
                    'minimo:3',
                    `maximo:${comprimentoMaximoNome}`,
                ],

                email: [
                    'obrigatorio',
                    'email',
                    `maximo:${comprimentoMaximoEmail}`,
                ],
            },

            mensagens: {
                nome: {
                    obrigatorio:
                        'Por favor, insere o teu nome.',

                    minimo:
                        'O nome deve ter, pelo menos, 3 caracteres.',

                    maximo:
                        `O nome não pode ter mais de ${comprimentoMaximoNome} caracteres.`,
                },

                email: {
                    obrigatorio:
                        'Por favor, insere o teu endereço de e-mail.',

                    email:
                        'Por favor, insere um endereço de e-mail válido.',

                    maximo:
                        `O endereço de e-mail não pode ter mais de ${comprimentoMaximoEmail} caracteres.`,
                },
            },
        },
    );
}

/**
 * Inicia o seletor das permissões de e-mail.
 *
 * @returns {void}
 *
 * @since 1.0.0
 */
function iniciarPermissoesEmail() {
    const campoTodas =
        document.querySelector(
            SELETORES.permissaoTodas,
        );

    if (!(campoTodas instanceof HTMLInputElement)) {
        return;
    }

    const itensPermissoes =
        document.querySelectorAll(
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
 * @returns {void}
 *
 * @since 1.0.0
 */
function iniciarValidacaoPalavraPasse() {
    const formulario =
        document.querySelector(
            SELETORES.formularioPalavraPasse,
        );

    if (!(formulario instanceof HTMLFormElement)) {
        return;
    }

    const campoPalavraPasseAtual =
        obterCampoEntradaFormulario(
            formulario,
            'palavra_passe_atual',
        );

    const campoNovaPalavraPasse =
        obterCampoEntradaFormulario(
            formulario,
            'nova_palavra_passe',
        );

    const campoConfirmacao =
        obterCampoEntradaFormulario(
            formulario,
            'confirmacao_nova_palavra_passe',
        );

    const maximoPalavraPasseAtual =
        obterComprimentoMaximoOpcional(
            campoPalavraPasseAtual,
        );

    const maximoNovaPalavraPasse =
        obterComprimentoMaximoOpcional(
            campoNovaPalavraPasse,
        );

    const maximoConfirmacao =
        obterComprimentoMaximoOpcional(
            campoConfirmacao,
        );

    const minimoNovaPalavraPasse =
        Number.isInteger(
            campoNovaPalavraPasse.minLength,
        )
        && campoNovaPalavraPasse.minLength > 0
            ? campoNovaPalavraPasse.minLength
            : COMPRIMENTO_MINIMO_PALAVRA_PASSE;

    new ValidadorFormulario(
        formulario,
        {
            regras: {
                palavra_passe_atual:
                    acrescentarRegraMaximo(
                        [
                            'obrigatorio',
                        ],
                        maximoPalavraPasseAtual,
                    ),

                nova_palavra_passe:
                    acrescentarRegraMaximo(
                        [
                            'obrigatorio',
                            `minimo:${minimoNovaPalavraPasse}`,
                            'maiuscula',
                            'minuscula',
                            'numero',
                            'simbolo',
                            'diferente:palavra_passe_atual',
                        ],
                        maximoNovaPalavraPasse,
                    ),

                confirmacao_nova_palavra_passe:
                    acrescentarRegraMaximo(
                        [
                            'obrigatorio',
                            'confirmado:nova_palavra_passe',
                        ],
                        maximoConfirmacao,
                    ),
            },

            mensagens: {
                palavra_passe_atual: {
                    obrigatorio:
                        'Por favor, insere a tua palavra-passe atual.',

                    maximo:
                        'A palavra-passe atual não é válida.',
                },

                nova_palavra_passe: {
                    obrigatorio:
                        'Por favor, insere a nova palavra-passe.',

                    minimo:
                        `A nova palavra-passe deve ter, pelo menos, ${minimoNovaPalavraPasse} caracteres.`,

                    maximo:
                        'A nova palavra-passe é demasiado longa.',

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

                    maximo:
                        'A confirmação da nova palavra-passe não é válida.',

                    confirmado:
                        'A confirmação da nova palavra-passe não coincide.',
                },
            },
        },
    );
}

/**
 * Inicia a apresentação e a ocultação das palavras-passe.
 *
 * @returns {void}
 *
 * @since 1.0.0
 */
function iniciarAlternadoresPalavraPasse() {
    const alternadores =
        document.querySelectorAll(
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
 * @returns {void}
 *
 * @since 1.0.0
 */
function iniciarTooltips() {
    new InicializadorTooltips(
        SELETORES.tooltip,
    );
}

/**
 * Inicia os comportamentos da página.
 *
 * @returns {void}
 *
 * @since 2.0.0
 */
function iniciarPaginaPerfil() {
    iniciarFotografiaPerfil();
    iniciarValidacaoPerfil();
    iniciarPermissoesEmail();
    iniciarValidacaoPalavraPasse();
    iniciarAlternadoresPalavraPasse();
    iniciarTooltips();
}

if (document.readyState === 'loading') {
    document.addEventListener(
        'DOMContentLoaded',
        iniciarPaginaPerfil,
        {
            once: true,
        },
    );
} else {
    iniciarPaginaPerfil();
}
