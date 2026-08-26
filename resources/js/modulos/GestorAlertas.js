/**
 * Centraliza as mensagens e confirmações apresentadas através do SweetAlert2.
 *
 * O SweetAlert2 é carregado apenas na primeira utilização e a mesma promessa
 * é partilhada por todos os consumidores deste módulo.
 *
 * @since 2.0.0
 */

/**
 * Promessa partilhada do carregamento do SweetAlert2.
 *
 * @type {Promise<Function>|null}
 */
let promessaSweetAlert = null;

/**
 * Normaliza texto obrigatório.
 *
 * @param {unknown} valor Valor recebido.
 *
 * @returns {string|null} Texto normalizado ou nulo.
 *
 * @since 2.0.0
 */
function normalizarTexto(valor) {
    if (typeof valor !== 'string') {
        return null;
    }

    const texto =
        valor.trim();

    return texto !== ''
        ? texto
        : null;
}

/**
 * Carrega a instância comum do SweetAlert2.
 *
 * @returns {Promise<Function>} API configurada do SweetAlert2.
 *
 * @since 2.0.0
 */
function carregarSweetAlert() {
    if (promessaSweetAlert === null) {
        promessaSweetAlert =
            import(
                'sweetalert2'
            )
                .then(
                    (modulo) =>
                        modulo.default.mixin({
                            theme: 'dark',
                        }),
                )
                .catch(
                    (erro) => {
                        promessaSweetAlert =
                            null;

                        throw erro;
                    },
                );
    }

    return promessaSweetAlert;
}

/**
 * Gere as mensagens globais da aplicação.
 *
 * @since 2.0.0
 */
class GestorAlertas {
    /**
     * Apresenta uma mensagem de sucesso uniforme.
     *
     * @param {unknown} mensagem Mensagem apresentada.
     *
     * @returns {Promise<void>}
     *
     * @since 2.0.0
     */
    static async mostrarSucesso(
        mensagem,
    ) {
        const texto =
            normalizarTexto(
                mensagem,
            );

        if (texto === null) {
            return;
        }

        try {
            const Swal =
                await carregarSweetAlert();

            void Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                text: texto,
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        } catch {
            /*
             * A operação já terminou com sucesso. A mensagem é apenas
             * complementar e não deve provocar uma falsa indicação de erro.
             */
        }
    }

    /**
     * Apresenta uma mensagem de erro.
     *
     * @param {unknown} mensagem Mensagem apresentada.
     * @param {unknown} titulo Título apresentado.
     *
     * @returns {Promise<void>}
     *
     * @since 2.0.0
     */
    static async mostrarErro(
        mensagem,
        titulo = 'Erro',
    ) {
        await this.mostrarDialogo(
            'error',
            titulo,
            mensagem,
            'Ocorreu um erro ao processar a ação.',
        );
    }

    /**
     * Apresenta uma mensagem de aviso.
     *
     * @param {unknown} mensagem Mensagem apresentada.
     * @param {unknown} titulo Título apresentado.
     *
     * @returns {Promise<void>}
     *
     * @since 2.0.0
     */
    static async mostrarAviso(
        mensagem,
        titulo = 'Aviso',
    ) {
        await this.mostrarDialogo(
            'warning',
            titulo,
            mensagem,
            'A operação necessita da tua atenção.',
        );
    }

    /**
     * Apresenta uma mensagem informativa.
     *
     * @param {unknown} mensagem Mensagem apresentada.
     * @param {unknown} titulo Título apresentado.
     *
     * @returns {Promise<void>}
     *
     * @since 2.0.0
     */
    static async mostrarInformacao(
        mensagem,
        titulo = 'Informação',
    ) {
        await this.mostrarDialogo(
            'info',
            titulo,
            mensagem,
            'Não existem informações adicionais disponíveis.',
        );
    }

    /**
     * Solicita confirmação ao utilizador.
     *
     * @param {object} opcoes Opções da confirmação.
     * @param {unknown} opcoes.titulo Título apresentado.
     * @param {unknown} opcoes.mensagem Mensagem apresentada.
     * @param {unknown} opcoes.textoConfirmar Texto do botão de confirmação.
     * @param {unknown} opcoes.textoCancelar Texto do botão de cancelamento.
     *
     * @returns {Promise<boolean>} Verdadeiro quando existe confirmação.
     *
     * @since 2.0.0
     */
    static async confirmar({
        titulo = 'Confirmar ação',
        mensagem,
        textoConfirmar = 'Confirmar',
        textoCancelar = 'Cancelar',
    }) {
        const tituloNormalizado =
            normalizarTexto(
                titulo,
            )
            ?? 'Confirmar ação';

        const mensagemNormalizada =
            normalizarTexto(
                mensagem,
            )
            ?? 'Tens a certeza de que pretendes continuar?';

        const textoConfirmarNormalizado =
            normalizarTexto(
                textoConfirmar,
            )
            ?? 'Confirmar';

        const textoCancelarNormalizado =
            normalizarTexto(
                textoCancelar,
            )
            ?? 'Cancelar';

        try {
            const Swal =
                await carregarSweetAlert();

            const resultado =
                await Swal.fire({
                    title:
                        tituloNormalizado,

                    text:
                        mensagemNormalizada,

                    icon:
                        'warning',

                    showCancelButton:
                        true,

                    confirmButtonText:
                        textoConfirmarNormalizado,

                    cancelButtonText:
                        textoCancelarNormalizado,

                    focusCancel:
                        true,

                    buttonsStyling: false,

                    customClass: {
                        confirmButton: 'btn btn-primary me-2',
                        cancelButton: 'btn btn-secondary',
                    },
                });

            return resultado.isConfirmed
                === true;
        } catch {
            return window.confirm(
                mensagemNormalizada,
            );
        }
    }

    /**
     * Apresenta um diálogo não transitório.
     *
     * @param {'error'|'warning'|'info'} icone Ícone apresentado.
     * @param {unknown} titulo Título recebido.
     * @param {unknown} mensagem Mensagem recebida.
     * @param {string} mensagemPredefinida Mensagem alternativa.
     *
     * @returns {Promise<void>}
     *
     * @since 2.0.0
     */
    static async mostrarDialogo(
        icone,
        titulo,
        mensagem,
        mensagemPredefinida,
    ) {
        const tituloNormalizado =
            normalizarTexto(
                titulo,
            )
            ?? 'Informação';

        const mensagemNormalizada =
            normalizarTexto(
                mensagem,
            )
            ?? mensagemPredefinida;

        try {
            const Swal =
                await carregarSweetAlert();

            await Swal.fire({
                icon: icone,
                title:
                    tituloNormalizado,

                text:
                    mensagemNormalizada,
            });
        } catch {
            window.alert(
                mensagemNormalizada,
            );
        }
    }
}

export default GestorAlertas;
