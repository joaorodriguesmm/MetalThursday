import TratadorFormularioAjax from './TratadorFormularioAjax';
import ValidadorFormulario from './ValidadorFormulario';

/**
 * Inicializa a validação e a submissão assíncrona dos formulários de
 * comentários e respostas existentes num contentor.
 *
 * Após uma publicação bem-sucedida, a página é recarregada para que o
 * servidor apresente o comentário através do componente Blade e aplique as
 * autorizações correspondentes ao utilizador autenticado.
 *
 * @since 3.0.0
 * @version 1.0.0
 */
class InicializadorComentarios {
    /**
     * Seletor dos formulários suportados.
     *
     * @type {string}
     *
     * @since 3.0.0
     * @version 1.0.0
     */
    static SELETOR_FORMULARIOS = [
        'form.formulario-comentario',
        'form.formulario-resposta-comentario',
    ].join(', ');

    /**
     * Comprimento máximo utilizado quando o campo não declara `maxlength`.
     *
     * @type {number}
     *
     * @since 3.0.0
     * @version 1.0.0
     */
    static COMPRIMENTO_MAXIMO_PREDEFINIDO =
        2000;

    /**
     * Cria o inicializador de comentários.
     *
     * @param {Document|Element} contentor Contentor utilizado na pesquisa.
     *
     * @throws {TypeError} Quando o contentor não é válido.
     *
     * @since 3.0.0
     * @version 1.0.0
     */
    constructor(contentor = document) {
        if (
            !(contentor instanceof Document)
            && !(contentor instanceof Element)
        ) {
            throw new TypeError(
                'O contentor dos formulários de comentário é inválido.',
            );
        }

        this.contentor =
            contentor;

        this.validadores =
            new Map();

        this.iniciar();
    }

    /**
     * Inicializa os formulários encontrados no contentor.
     *
     * @returns {void}
     *
     * @since 3.0.0
     * @version 1.0.0
     */
    iniciar() {
        this.contentor.querySelectorAll(
            InicializadorComentarios.SELETOR_FORMULARIOS,
        ).forEach((formulario) => {
            if (
                formulario instanceof HTMLFormElement
                && formulario.dataset
                    .formularioComentarioInicializado
                    !== 'true'
            ) {
                this.inicializarFormulario(
                    formulario,
                );
            }
        });
    }

    /**
     * Inicializa um formulário de comentário ou resposta.
     *
     * @param {HTMLFormElement} formulario Formulário recebido.
     *
     * @returns {void}
     *
     * @throws {Error} Quando faltam contratos obrigatórios no formulário.
     *
     * @since 3.0.0
     * @version 1.0.0
     */
    inicializarFormulario(formulario) {
        const identificador =
            formulario.id.trim();

        const endereco =
            formulario.action.trim();

        const campoConteudo =
            formulario.elements.namedItem(
                'conteudo',
            );

        if (
            identificador === ''
            || endereco === ''
            || !(campoConteudo instanceof HTMLTextAreaElement)
        ) {
            throw new Error(
                'Cada formulário de comentário deve possuir identificador, endereço e campo de conteúdo.',
            );
        }

        const comprimentoMaximo =
            Number.isInteger(
                campoConteudo.maxLength,
            )
            && campoConteudo.maxLength > 0
                ? campoConteudo.maxLength
                : InicializadorComentarios
                    .COMPRIMENTO_MAXIMO_PREDEFINIDO;

        const tratadorAjax =
            new TratadorFormularioAjax(
                identificador,
                endereco,
                () => {
                    window.location.reload();
                },
            );

        const validador =
            new ValidadorFormulario(
                formulario,
                {
                    regras: {
                        conteudo: [
                            'obrigatorio',
                            `maximo:${comprimentoMaximo}`,
                        ],
                    },

                    mensagens: {
                        conteudo: {
                            obrigatorio:
                                'Por favor, insere o texto do comentário.',

                            maximo:
                                `O comentário não pode ter mais de ${comprimentoMaximo} caracteres.`,
                        },
                    },

                    aoSucesso: () => {
                        /*
                         * A promessa não é devolvida porque o validador exige
                         * uma função de sucesso síncrona.
                         */
                        tratadorAjax.submeter();
                    },
                },
            );

        formulario.dataset
            .formularioComentarioInicializado =
                'true';

        this.validadores.set(
            formulario,
            validador,
        );
    }

    /**
     * Remove os eventos associados aos formulários inicializados.
     *
     * @returns {void}
     *
     * @since 3.0.0
     * @version 1.0.0
     */
    destruir() {
        this.validadores.forEach(
            (
                validador,
                formulario,
            ) => {
                validador.destruir();

                delete formulario.dataset
                    .formularioComentarioInicializado;
            },
        );

        this.validadores.clear();
    }
}

export default InicializadorComentarios;
