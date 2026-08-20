/**
 * Gere a limpeza dos formulários existentes nas janelas modais do Bootstrap.
 *
 * O evento `hidden.bs.modal` permanece em inglês por corresponder ao evento
 * disponibilizado pelo Bootstrap.
 *
 * @since 1.0.0
 */
class LimpadorFormulariosModais {
    /**
     * Cria e inicializa um limpador de formulários de janelas modais.
     *
     * O evento de fecho do Bootstrap propaga-se pela DOM, permitindo utilizar
     * um único listener delegado para todas as janelas modais.
     *
     * @param {string} seletorModais Seletor CSS das janelas modais.
     *
     * @throws {TypeError} Quando o seletor é inválido.
     *
     * @since 1.0.0
     */
    constructor(seletorModais = '.modal') {
        if (
            typeof seletorModais !== 'string'
            || seletorModais.trim() === ''
        ) {
            throw new TypeError(
                'O seletor das janelas modais é obrigatório.',
            );
        }

        const seletorNormalizado =
            seletorModais.trim();

        try {
            document.querySelector(
                seletorNormalizado,
            );
        } catch {
            throw new TypeError(
                `O seletor CSS "${seletorNormalizado}" é inválido.`,
            );
        }

        /**
         * Seletor das janelas modais abrangidas.
         *
         * @type {string}
         *
         * @since 2.0.0
         */
        this.seletorModais =
            seletorNormalizado;

        document.addEventListener(
            'hidden.bs.modal',
            (evento) => {
                this.limparFormulariosModal(
                    evento,
                );
            },
        );
    }

    /**
     * Limpa todos os formulários da janela modal que foi fechada.
     *
     * @param {Event} evento Evento de fecho da janela modal.
     *
     * @returns {void}
     *
     * @since 1.0.0
     */
    limparFormulariosModal(evento) {
        const modal =
            evento.target;

        if (
            !(modal instanceof HTMLElement)
            || !modal.matches(
                this.seletorModais,
            )
        ) {
            return;
        }

        if (
            modal.hasAttribute(
                'data-preservar-formularios-ao-fechar',
            )
        ) {
            modal.removeAttribute(
                'data-preservar-formularios-ao-fechar',
            );

            return;
        }

        modal.querySelectorAll(
            'form',
        ).forEach((formulario) => {
            if (
                formulario
                instanceof HTMLFormElement
            ) {
                this.limparFormulario(
                    formulario,
                );
            }
        });
    }

    /**
     * Limpa um formulário e os respetivos estados de validação.
     *
     * @param {HTMLFormElement} formulario Formulário a limpar.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    limparFormulario(formulario) {
        formulario.reset();

        this.sincronizarTomSelect(
            formulario,
        );

        this.limparCamposInvalidos(
            formulario,
        );

        this.limparMensagensValidacao(
            formulario,
        );
    }

    /**
     * Sincroniza as instâncias Tom Select com os valores repostos.
     *
     * A pesquisa é feita pelos campos `<select>` reais em vez de depender de
     * classes internas adicionadas pela biblioteca.
     *
     * @param {HTMLFormElement} formulario Formulário limpo.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    sincronizarTomSelect(formulario) {
        formulario
            .querySelectorAll(
                'select',
            )
            .forEach((selecao) => {
                if (
                    !(selecao
                        instanceof HTMLSelectElement)
                ) {
                    return;
                }

                const instancia =
                    selecao.tomselect;

                if (!instancia) {
                    return;
                }

                if (
                    typeof instancia.sync
                    === 'function'
                ) {
                    instancia.sync();

                    return;
                }

                if (
                    typeof instancia.setValue
                    !== 'function'
                ) {
                    return;
                }

                const valores = Array.from(
                    selecao.selectedOptions,
                    (opcao) => opcao.value,
                );

                instancia.setValue(
                    selecao.multiple
                        ? valores
                        : valores[0] ?? '',
                    true,
                );
            });
    }

    /**
     * Remove o estado inválido dos campos do formulário.
     *
     * @param {HTMLFormElement} formulario Formulário limpo.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    limparCamposInvalidos(formulario) {
        formulario
            .querySelectorAll(
                '.is-invalid',
            )
            .forEach((elemento) => {
                elemento.classList.remove(
                    'is-invalid',
                );

                elemento.removeAttribute(
                    'aria-invalid',
                );
            });
    }

    /**
     * Limpa as mensagens de validação do formulário.
     *
     * @param {HTMLFormElement} formulario Formulário limpo.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    limparMensagensValidacao(formulario) {
        formulario
            .querySelectorAll(
                '.invalid-feedback',
            )
            .forEach((elemento) => {
                elemento.textContent =
                    '';

                elemento.classList.remove(
                    'd-block',
                );

                elemento.style.removeProperty(
                    'display',
                );
            });
    }
}

export default LimpadorFormulariosModais;
