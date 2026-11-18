/**
 * Testa incorporações do YouTube numa linha do editor de ligações.
 *
 * O navegador não consegue garantir que uma incorporação é reproduzível:
 * a confirmação cabe ao utilizador. A origem do iframe é construída apenas
 * a partir de identificadores validados, nunca de HTML fornecido pelo editor.
 *
 * @since 2.0.0
 */
class TestadorIncorporacoesYouTube {
    static HOSTS = new Set([
        'youtube.com',
        'www.youtube.com',
        'm.youtube.com',
        'music.youtube.com',
        'youtube-nocookie.com',
        'www.youtube-nocookie.com',
        'youtu.be',
        'www.youtu.be',
    ]);

    /**
     * Extrai identificadores sem aceitar hosts parecidos ou parâmetros inválidos.
     *
     * @param {string} valor Endereço do YouTube.
     * @returns {{video: string|null, playlist: string|null}}
     */
    static analisarUrl(valor) {
        const vazio = {
            video: null,
            playlist: null,
        };

        let url;

        try {
            url = new URL(valor);
        } catch {
            return vazio;
        }

        const host = url.hostname
            .toLowerCase()
            .replace(/\.$/u, '');

        if (
            !['http:', 'https:'].includes(url.protocol)
            || !this.HOSTS.has(host)
            || url.username !== ''
            || url.password !== ''
        ) {
            return vazio;
        }

        const segmentos = url.pathname
            .split('/')
            .filter(Boolean);

        const listas = url.searchParams.getAll('list');
        const candidatoLista = listas.at(-1) ?? null;

        const playlist =
            typeof candidatoLista === 'string'
            && /^[A-Za-z0-9_-]{10,150}$/u.test(candidatoLista)
                ? candidatoLista
                : null;

        const videos = url.searchParams.getAll('v');
        let candidatoVideo = videos.at(-1) ?? null;

        if (['youtu.be', 'www.youtu.be'].includes(host)) {
            candidatoVideo = segmentos[0] ?? null;
        } else {
            const videoConsultaValido =
                typeof candidatoVideo === 'string'
                && /^[A-Za-z0-9_-]{11}$/u.test(candidatoVideo);

            if (
                !videoConsultaValido
                && ['embed', 'shorts', 'live'].includes(segmentos[0])
            ) {
                candidatoVideo = segmentos[1] ?? null;
            }
        }

        if (
            segmentos[0] === 'embed'
            && candidatoVideo === 'videoseries'
        ) {
            candidatoVideo = null;
        }

        const video =
            typeof candidatoVideo === 'string'
            && /^[A-Za-z0-9_-]{11}$/u.test(candidatoVideo)
                ? candidatoVideo
                : null;

        return { video, playlist };
    }

    /**
     * Constrói um iframe com o mesmo formato utilizado pelo servidor.
     *
     * @param {string} url Ligação original ou alternativa.
     * @param {string} tipo Tipo escolhido ou vazio para deteção automática.
     * @returns {string|null} Origem segura ou nulo.
     */
    static criarOrigem(url, tipo = '') {
        const identificadores = this.analisarUrl(url);

        if (
            (tipo === '' || tipo === 'video')
            && identificadores.video !== null
        ) {
            return `https://www.youtube-nocookie.com/embed/${identificadores.video}?rel=0`;
        }

        if (
            (tipo === '' || tipo === 'playlist')
            && identificadores.playlist !== null
        ) {
            return 'https://www.youtube-nocookie.com/embed/videoseries'
                + `?list=${identificadores.playlist}&rel=0`;
        }

        return null;
    }

    /**
     * Ajusta a visibilidade e as escolhas à ligação atualmente introduzida.
     *
     * @param {HTMLElement} linha Linha editável.
     * @param {boolean} visivel Estado da incorporação do YouTube.
     * @returns {void}
     */
    static atualizarLinha(linha, visivel) {
        const contentor = linha.querySelector(
            '[data-configuracao-incorporacao-youtube]',
        );

        if (!(contentor instanceof HTMLElement)) {
            return;
        }

        contentor.hidden = !visivel;

        const seletor = linha.querySelector(
            '[data-campo-tipo-incorporacao]',
        );

        const alternativa = linha.querySelector(
            '[data-campo-url-incorporacao-alternativa]',
        );

        const campoOriginal = linha.querySelector(
            '[data-campo-url-ligacao]',
        );

        if (seletor instanceof HTMLSelectElement) {
            seletor.disabled = !visivel;
        }

        if (alternativa instanceof HTMLInputElement) {
            alternativa.disabled = !visivel;
        }

        if (!visivel) {
            this.invalidarTeste(linha);

            return;
        }

        if (
            !(seletor instanceof HTMLSelectElement)
            || !(campoOriginal instanceof HTMLInputElement)
        ) {
            return;
        }

        const url = alternativa instanceof HTMLInputElement
            && alternativa.value.trim() !== ''
            ? alternativa.value.trim()
            : campoOriginal.value.trim();

        const { video, playlist } = this.analisarUrl(url);

        for (const opcao of seletor.options) {
            if (opcao.value === 'video') {
                opcao.disabled = video === null;
            } else if (opcao.value === 'playlist') {
                opcao.disabled = playlist === null;
            }
        }

        if (
            (seletor.value === 'video' && video === null)
            || (seletor.value === 'playlist' && playlist === null)
        ) {
            seletor.value = '';
        }
    }

    /**
     * Remove o leitor anterior após uma alteração ou desativação.
     *
     * @param {HTMLElement} linha Linha editável.
     * @returns {void}
     */
    static invalidarTeste(linha) {
        const resultado = linha.querySelector(
            '[data-resultado-teste-incorporacao]',
        );

        const previsualizacao = linha.querySelector(
            '[data-previsualizacao-incorporacao]',
        );

        if (resultado instanceof HTMLElement) {
            resultado.hidden = true;
        }

        if (previsualizacao instanceof HTMLElement) {
            previsualizacao.replaceChildren();
        }
    }

    /**
     * Apresenta um leitor para o utilizador verificar manualmente.
     *
     * @param {HTMLElement} linha Linha editável.
     * @returns {boolean} Indica se foi possível construir o leitor.
     */
    static testar(linha) {
        const original = linha.querySelector(
            '[data-campo-url-ligacao]',
        );

        const alternativa = linha.querySelector(
            '[data-campo-url-incorporacao-alternativa]',
        );

        const seletor = linha.querySelector(
            '[data-campo-tipo-incorporacao]',
        );

        const resultado = linha.querySelector(
            '[data-resultado-teste-incorporacao]',
        );

        const mensagem = linha.querySelector(
            '[data-estado-teste-incorporacao]',
        );

        const previsualizacao = linha.querySelector(
            '[data-previsualizacao-incorporacao]',
        );

        if (
            !(original instanceof HTMLInputElement)
            || !(resultado instanceof HTMLElement)
            || !(mensagem instanceof HTMLElement)
            || !(previsualizacao instanceof HTMLElement)
        ) {
            return false;
        }

        this.invalidarTeste(linha);

        const url = alternativa instanceof HTMLInputElement
            && alternativa.value.trim() !== ''
            ? alternativa.value.trim()
            : original.value.trim();

        const tipo = seletor instanceof HTMLSelectElement
            ? seletor.value
            : '';

        const origem = this.criarOrigem(url, tipo);

        resultado.hidden = false;

        if (origem === null) {
            mensagem.textContent = 'Não foi encontrado um vídeo ou uma playlist válida neste endereço. '
                + 'Verifica o URL ou utiliza apenas a ligação externa.';

            return false;
        }

        const iframe = document.createElement('iframe');

        iframe.src = origem;
        iframe.title = 'Teste de incorporação do YouTube';
        iframe.className = 'w-100 h-100 border-0';
        iframe.loading = 'lazy';
        iframe.referrerPolicy = 'strict-origin-when-cross-origin';
        iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; '
            + 'gyroscope; picture-in-picture; web-share';
        iframe.setAttribute(
            'sandbox',
            'allow-scripts allow-same-origin allow-presentation allow-popups',
        );
        iframe.allowFullscreen = true;

        previsualizacao.append(iframe);

        mensagem.textContent = 'Confirma que o conteúdo correto é reproduzido. '
            + 'Se o YouTube bloquear o leitor, utiliza um URL alternativo '
            + 'ou desativa «Incorporar».';

        return true;
    }
}

export default TestadorIncorporacoesYouTube;
