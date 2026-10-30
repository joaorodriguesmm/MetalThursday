import { expect, test } from '@playwright/test';

test('permite carregar fotografias locais sob a CSP real da aplicação', async ({ page }) => {
    await page.goto('/entrar');

    const resultado = await page.evaluate(async () => {
        const violacoes = [];
        globalThis.document.addEventListener('securitypolicyviolation', (evento) => {
            violacoes.push(evento.effectiveDirective);
        });

        const png = Uint8Array.from(
            globalThis.atob('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII='),
            (caractere) => caractere.charCodeAt(0),
        );
        const url = globalThis.URL.createObjectURL(new globalThis.Blob([png], { type: 'image/png' }));
        const imagem = new globalThis.Image();

        try {
            const carregou = await new Promise((resolve) => {
                imagem.onload = () => resolve(true);
                imagem.onerror = () => resolve(false);
                imagem.src = url;
            });

            return { carregou, largura: imagem.naturalWidth, violacoes };
        } finally {
            globalThis.URL.revokeObjectURL(url);
        }
    });

    expect(resultado.carregou).toBe(true);
    expect(resultado.largura).toBe(1);
    expect(resultado.violacoes).not.toContain('img-src');
});
