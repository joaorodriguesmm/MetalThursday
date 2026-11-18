import { describe, expect, it } from 'vitest';

import AnalisadorUrlYouTube
    from '../../../resources/js/modulos/AnalisadorUrlYouTube.js';

describe('AnalisadorUrlYouTube', () => {
    it('identifica vídeos individuais do YouTube e YouTube Music', () => {
        for (const url of [
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'https://music.youtube.com/watch?v=M7lc1UVf-VE',
            'https://youtu.be/dQw4w9WgXcQ',
        ]) {
            const resultado = AnalisadorUrlYouTube.analisarUrl(url);

            expect(resultado.video).toMatch(/^[A-Za-z0-9_-]{11}$/u);
            expect(resultado.playlist).toBe(false);
        }
    });

    it('identifica playlists e vídeos associados a listas', () => {
        for (const url of [
            'https://music.youtube.com/playlist?list=OLAK5uy_nVA-m-f0bq5IZK5Gv1-sPtuxSzMYN5_xI',
            'https://www.youtube.com/playlist?list=PL1234567890',
            'https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=PL1234567890',
            'https://youtu.be/dQw4w9WgXcQ?list=PL1234567890',
            'https://youtube.com/watch?v=dQw4w9WgXcQ&list=',
        ]) {
            expect(AnalisadorUrlYouTube.analisarUrl(url).playlist).toBe(true);
        }
    });

    it('não interpreta videoseries como identificador de vídeo', () => {
        const resultado = AnalisadorUrlYouTube.analisarUrl(
            'https://www.youtube-nocookie.com/embed/videoseries?list=PL1234567890',
        );

        expect(resultado.video).toBeNull();
        expect(resultado.playlist).toBe(true);
    });

    it('rejeita domínios semelhantes, credenciais e URLs inválidos', () => {
        for (const url of [
            'https://youtube.com.exemplo.test/watch?v=dQw4w9WgXcQ',
            'https://youtube.com@exemplo.test/watch?v=dQw4w9WgXcQ',
            'https://utilizador@youtube.com/watch?v=dQw4w9WgXcQ',
            'javascript:alert(1)',
            'https://youtube.com/watch?v=invalido',
        ]) {
            const resultado = AnalisadorUrlYouTube.analisarUrl(url);

            expect(resultado.video).toBeNull();
            expect(resultado.playlist).toBe(false);
        }
    });

    it('não confunde vídeos individuais com playlists', () => {
        expect(AnalisadorUrlYouTube.analisarUrl(
            'https://youtube.com/watch?v=dQw4w9WgXcQ',
        )).toEqual({ video: 'dQw4w9WgXcQ', playlist: false });
    });
});
