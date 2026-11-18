/**
 * Analisa URLs do YouTube e identifica vídeos individuais e playlists.
 *
 * @since 2.0.0
 */
class AnalisadorUrlYouTube {
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
     * @returns {{video: string|null, playlist: boolean}}
     */
    static analisarUrl(valor) {
        const vazio = {
            video: null,
            playlist: false,
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

        const playlist = url.searchParams.has('list')
            || segmentos[0]?.toLowerCase() === 'playlist'
            || (
                segmentos[0]?.toLowerCase() === 'embed'
                && segmentos[1]?.toLowerCase() === 'videoseries'
            );

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
}

export default AnalisadorUrlYouTube;
