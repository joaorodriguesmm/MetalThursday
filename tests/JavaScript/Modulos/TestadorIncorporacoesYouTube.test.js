import {
    afterEach,
    describe,
    expect,
    it,
} from 'vitest';

import TestadorIncorporacoesYouTube
    from '../../../resources/js/modulos/TestadorIncorporacoesYouTube.js';

describe('TestadorIncorporacoesYouTube', () => {
    afterEach(() => {
        document.body.replaceChildren();
    });

    it('identifica vídeos e playlists do YouTube Music', () => {
        expect(
            TestadorIncorporacoesYouTube.criarOrigem(
                'https://music.youtube.com/watch?v=dQw4w9WgXcQ',
            ),
        ).toBe('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ?rel=0');

        expect(
            TestadorIncorporacoesYouTube.criarOrigem(
                'https://music.youtube.com/playlist?list=OLAK5uy_nVA-m-f0bq5IZK5Gv1-sPtuxSzMYN5_xI',
            ),
        ).toContain('/embed/videoseries?list=OLAK5uy_nVA-m-f0bq5IZK5Gv1-sPtuxSzMYN5_xI');
    });

    it('aplica a política de referer ao testar vídeos e playlists', () => {
        const urls = [
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://music.youtube.com/playlist?list=OLAK5uy_nVA-m-f0bq5IZK5Gv1-sPtuxSzMYN5_xI',
        ];

        for (const url of urls) {
            const linha = document.createElement('div');

            linha.innerHTML = `
                <input data-campo-url-ligacao value="${url}">
                <div data-resultado-teste-incorporacao hidden></div>
                <div data-estado-teste-incorporacao></div>
                <div data-previsualizacao-incorporacao></div>
            `;

            document.body.append(linha);

            expect(TestadorIncorporacoesYouTube.testar(linha)).toBe(true);

            const iframe = linha.querySelector('iframe');

            expect(iframe).toBeInstanceOf(HTMLIFrameElement);
            expect(iframe.referrerPolicy).toBe('strict-origin-when-cross-origin');

            linha.remove();
        }
    });

    it('respeita a escolha manual quando existem vídeo e playlist', () => {
        const url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=PL1234567890';

        expect(TestadorIncorporacoesYouTube.criarOrigem(url))
            .toContain('/embed/dQw4w9WgXcQ?rel=0');

        expect(TestadorIncorporacoesYouTube.criarOrigem(url, 'playlist'))
            .toContain('/embed/videoseries?list=PL1234567890');

        expect(TestadorIncorporacoesYouTube.criarOrigem(url, 'video'))
            .toContain('/embed/dQw4w9WgXcQ?rel=0');
    });

    it('rejeita domínios semelhantes, credenciais e identificadores inválidos', () => {
        const urls = [
            'https://youtube.com.exemplo.test/watch?v=dQw4w9WgXcQ',
            'https://youtube.com@exemplo.test/watch?v=dQw4w9WgXcQ',
            'https://utilizador@youtube.com/watch?v=dQw4w9WgXcQ',
            'javascript:alert(1)',
            'https://youtube.com/watch?v=invalido',
        ];

        for (const url of urls) {
            expect(TestadorIncorporacoesYouTube.criarOrigem(url)).toBeNull();
        }
    });

    it('não interpreta videoseries como identificador de vídeo', () => {
        expect(
            TestadorIncorporacoesYouTube.criarOrigem(
                'https://www.youtube-nocookie.com/embed/videoseries?list=PL1234567890',
                'video',
            ),
        ).toBeNull();

        expect(
            TestadorIncorporacoesYouTube.criarOrigem(
                'https://www.youtube-nocookie.com/embed/videoseries?list=PL1234567890',
                'playlist',
            ),
        ).toContain('/embed/videoseries?list=PL1234567890');
    });
});
