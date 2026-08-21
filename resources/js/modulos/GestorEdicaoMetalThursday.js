/**
 * Gere a edição apresentada no formulário de MetalThursday a partir da data.
 *
 * A associação persistida continua a ser determinada pelo servidor. Este
 * módulo limita-se a fornecer feedback imediato ao utilizador e a manter a
 * lista local de edições sincronizada com criações efetuadas pelo modal.
 *
 * @since 2.0.0
 */
class GestorEdicaoMetalThursday {
    /**
     * Inicializa o gestor.
     *
     * @param {object} opcoes Configuração do gestor.
     * @param {HTMLInputElement} opcoes.campoData Campo da data.
     * @param {HTMLInputElement} opcoes.campoEdicao Campo informativo da edição.
     * @param {HTMLElement} opcoes.elementoEstado Elemento de feedback.
     * @param {HTMLElement} opcoes.contentorDados Contentor dos dados das edições.
     *
     * @throws {TypeError} Quando a configuração não é válida.
     * @throws {Error} Quando os períodos recebidos se sobrepõem.
     *
     * @since 2.0.0
     */
    constructor({
        campoData,
        campoEdicao,
        elementoEstado,
        contentorDados,
    }) {
        if (!(campoData instanceof HTMLInputElement)) {
            throw new TypeError(
                'O campo da data da MetalThursday é inválido.',
            );
        }

        if (!(campoEdicao instanceof HTMLInputElement)) {
            throw new TypeError(
                'O campo informativo da edição é inválido.',
            );
        }

        if (!(elementoEstado instanceof HTMLElement)) {
            throw new TypeError(
                'O elemento de estado da edição é inválido.',
            );
        }

        if (!(contentorDados instanceof HTMLElement)) {
            throw new TypeError(
                'O contentor dos dados das edições é inválido.',
            );
        }

        this.campoData = campoData;
        this.campoEdicao = campoEdicao;
        this.elementoEstado = elementoEstado;
        this.dataReferencia = this.normalizarData(
            contentorDados.dataset.dataReferencia,
        );

        if (this.dataReferencia === null) {
            throw new TypeError(
                'A data de referência das edições é inválida.',
            );
        }

        this.edicoes = this.obterEdicoesDoDocumento(
            contentorDados,
        );

        this.validarIntegridadeTemporal();

        this.campoData.addEventListener(
            'input',
            () => this.atualizarApresentacao(),
        );

        this.campoData.addEventListener(
            'change',
            () => this.atualizarApresentacao(),
        );

        this.atualizarApresentacao();
    }

    /**
     * Valida se uma data possui uma edição correspondente.
     *
     * Datas vazias ou inválidas são deixadas para as regras específicas do
     * campo da data.
     *
     * @param {unknown} valor Data recebida.
     * @returns {true|string} Verdadeiro ou mensagem de erro.
     *
     * @since 2.0.0
     */
    validarData(valor) {
        const data = this.normalizarData(
            valor,
        );

        if (data === null) {
            return true;
        }

        return this.obterEdicaoParaData(
            data,
        ) !== null
            ? true
            : 'Não existe nenhuma edição que inclua a data selecionada.';
    }

    /**
     * Acrescenta ou atualiza uma edição criada durante a utilização da página.
     *
     * @param {unknown} dados Dados devolvidos pelo servidor.
     * @returns {void}
     *
     * @throws {TypeError} Quando os dados da edição são inválidos.
     * @throws {Error} Quando o novo período se sobrepõe a outro.
     *
     * @since 2.0.0
     */
    adicionarEdicao(dados) {
        const edicao = this.normalizarEdicao(
            dados,
        );

        if (edicao === null) {
            throw new TypeError(
                'Os dados da edição criada são inválidos.',
            );
        }

        this.edicoes = this.edicoes.filter(
            (existente) => existente.identificador
                !== edicao.identificador,
        );

        this.edicoes.push(
            edicao,
        );

        this.validarIntegridadeTemporal();
        this.atualizarApresentacao();

        this.campoData.dispatchEvent(
            new Event(
                'input',
                {
                    bubbles: true,
                },
            ),
        );
    }

    /**
     * Lê as edições preparadas no documento.
     *
     * @param {HTMLElement} contentor Contentor dos dados.
     * @returns {Array<object>} Edições normalizadas.
     *
     * @throws {TypeError} Quando uma entrada é inválida.
     *
     * @since 2.0.0
     */
    obterEdicoesDoDocumento(contentor) {
        return Array.from(
            contentor.querySelectorAll(
                '[data-edicao-identificador]',
            ),
        ).map((elemento) => {
            if (!(elemento instanceof HTMLElement)) {
                throw new TypeError(
                    'Foi encontrado um registo de edição inválido.',
                );
            }

            const edicao = this.normalizarEdicao({
                id:
                    elemento.dataset.edicaoIdentificador,

                nome:
                    elemento.dataset.edicaoNome,

                data_inicio:
                    elemento.dataset.edicaoInicio,

                data_fim:
                    elemento.dataset.edicaoFim,
            });

            if (edicao === null) {
                throw new TypeError(
                    'Foi encontrado um registo de edição inválido.',
                );
            }

            return edicao;
        });
    }

    /**
     * Normaliza uma edição.
     *
     * @param {unknown} dados Dados recebidos.
     * @returns {{
     *     identificador: number,
     *     nome: string,
     *     dataInicio: string,
     *     dataFim: string|null
     * }|null} Edição normalizada ou nulo.
     *
     * @since 2.0.0
     */
    normalizarEdicao(dados) {
        if (
            typeof dados !== 'object'
            || dados === null
            || Array.isArray(dados)
        ) {
            return null;
        }

        const identificador = Number(
            dados.id,
        );

        const nome = typeof dados.nome === 'string'
            ? dados.nome.trim()
            : '';

        const dataInicio = this.normalizarData(
            dados.data_inicio,
        );

        const dataFimRecebida =
            dados.data_fim;

        const dataFim = (
            dataFimRecebida === null
            || dataFimRecebida === undefined
            || dataFimRecebida === ''
        )
            ? null
            : this.normalizarData(
                dataFimRecebida,
            );

        if (
            !Number.isInteger(identificador)
            || identificador < 1
            || nome === ''
            || dataInicio === null
            || (
                dataFimRecebida !== null
                && dataFimRecebida !== undefined
                && dataFimRecebida !== ''
                && dataFim === null
            )
            || (
                dataFim !== null
                && dataFim < dataInicio
            )
        ) {
            return null;
        }

        return {
            identificador,
            nome,
            dataInicio,
            dataFim,
        };
    }

    /**
     * Normaliza e valida uma data ISO sem componente temporal.
     *
     * @param {unknown} valor Valor recebido.
     * @returns {string|null} Data AAAA-MM-DD ou nulo.
     *
     * @since 2.0.0
     */
    normalizarData(valor) {
        if (
            typeof valor !== 'string'
            || !/^\d{4}-\d{2}-\d{2}$/u.test(
                valor,
            )
        ) {
            return null;
        }

        const [ano, mes, dia] = valor
            .split('-')
            .map(Number);

        const data = new Date(
            Date.UTC(
                ano,
                mes - 1,
                dia,
            ),
        );

        if (
            data.getUTCFullYear() !== ano
            || data.getUTCMonth() !== mes - 1
            || data.getUTCDate() !== dia
        ) {
            return null;
        }

        return valor;
    }

    /**
     * Obtém a edição que contém uma determinada data.
     *
     * @param {string} data Data normalizada.
     * @returns {object|null} Edição encontrada ou nulo.
     *
     * @throws {Error} Quando mais do que uma edição contém a data.
     *
     * @since 2.0.0
     */
    obterEdicaoParaData(data) {
        const correspondencias = this.edicoes.filter(
            (edicao) => edicao.dataInicio <= data
                && (
                    edicao.dataFim === null
                    || edicao.dataFim >= data
                ),
        );

        if (correspondencias.length > 1) {
            throw new Error(
                'Existe mais do que uma edição para a data indicada.',
            );
        }

        return correspondencias[0]
            ?? null;
    }

    /**
     * Garante que os períodos carregados não se sobrepõem.
     *
     * @returns {void}
     *
     * @throws {Error} Quando existem períodos sobrepostos.
     *
     * @since 2.0.0
     */
    validarIntegridadeTemporal() {
        const ordenadas = [
            ...this.edicoes,
        ].sort(
            (primeira, segunda) => primeira.dataInicio.localeCompare(
                segunda.dataInicio,
            ),
        );

        for (
            let indice = 1;
            indice < ordenadas.length;
            indice += 1
        ) {
            const anterior = ordenadas[indice - 1];
            const atual = ordenadas[indice];

            if (
                anterior.dataFim === null
                || anterior.dataFim >= atual.dataInicio
            ) {
                throw new Error(
                    'Os períodos das edições apresentados no formulário sobrepõem-se.',
                );
            }
        }
    }

    /**
     * Atualiza o campo informativo e respetivo feedback.
     *
     * @returns {void}
     *
     * @since 2.0.0
     */
    atualizarApresentacao() {
        const valorData =
            this.campoData.value;

        const dataSelecionada = this.normalizarData(
            valorData,
        );

        if (dataSelecionada !== null) {
            const edicao = this.obterEdicaoParaData(
                dataSelecionada,
            );

            if (edicao !== null) {
                this.apresentarEdicao(
                    edicao,
                    'Edição determinada automaticamente pela data selecionada.',
                );

                return;
            }

            this.apresentarErro(
                'Não existe nenhuma edição que inclua a data selecionada.',
            );

            return;
        }

        if (valorData !== '') {
            this.apresentarEstadoNeutro(
                '',
                'A edição será determinada quando a data for válida.',
            );

            return;
        }

        const edicaoAtual = this.obterEdicaoParaData(
            this.dataReferencia,
        );

        if (edicaoAtual !== null) {
            this.apresentarEdicao(
                edicaoAtual,
                'Edição atualmente em curso. Será ajustada automaticamente à data escolhida.',
            );

            return;
        }

        this.apresentarAviso(
            'Não existe nenhuma edição atualmente em curso. A edição será determinada pela data escolhida.',
        );
    }

    /**
     * Apresenta uma edição válida.
     *
     * @param {object} edicao Edição apresentada.
     * @param {string} mensagem Mensagem auxiliar.
     * @returns {void}
     *
     * @since 2.0.0
     */
    apresentarEdicao(
        edicao,
        mensagem,
    ) {
        this.apresentarEstadoNeutro(
            edicao.nome,
            mensagem,
        );
    }

    /**
     * Apresenta o estado neutro do campo.
     *
     * @param {string} valor Valor apresentado.
     * @param {string} mensagem Mensagem auxiliar.
     * @returns {void}
     *
     * @since 2.0.0
     */
    apresentarEstadoNeutro(
        valor,
        mensagem,
    ) {
        this.campoEdicao.value = valor;
        this.campoEdicao.classList.remove(
            'is-invalid',
        );
        this.campoEdicao.removeAttribute(
            'aria-invalid',
        );

        this.elementoEstado.className =
            'form-text';
        this.elementoEstado.textContent =
            mensagem;
    }

    /**
     * Apresenta um aviso não bloqueante.
     *
     * @param {string} mensagem Mensagem apresentada.
     * @returns {void}
     *
     * @since 2.0.0
     */
    apresentarAviso(mensagem) {
        this.apresentarEstadoNeutro(
            '',
            mensagem,
        );

        this.elementoEstado.classList.add(
            'text-warning-emphasis',
        );
    }

    /**
     * Apresenta o erro correspondente a uma data sem edição.
     *
     * @param {string} mensagem Mensagem apresentada.
     * @returns {void}
     *
     * @since 2.0.0
     */
    apresentarErro(mensagem) {
        this.campoEdicao.value = '';
        this.campoEdicao.classList.add(
            'is-invalid',
        );
        this.campoEdicao.setAttribute(
            'aria-invalid',
            'true',
        );

        this.elementoEstado.className =
            'invalid-feedback d-block';
        this.elementoEstado.textContent =
            mensagem;
    }
}

export default GestorEdicaoMetalThursday;
