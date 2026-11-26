import { expect, test } from '@playwright/test';

test('carrega os ícones SVG do Bootstrap sob a CSP real da aplicação', async ({ page }) => {
    const resposta = await page.goto('/entrar');

    expect(resposta?.ok()).toBe(true);

    const politica = resposta.headers()['content-security-policy'];

    expect(politica).toBeTruthy();

    const diretivaImagens = politica
        .split(';')
        .map((diretiva) => diretiva.trim())
        .find((diretiva) => diretiva.startsWith('img-src '));

    expect(diretivaImagens).toBeTruthy();
    expect(diretivaImagens).not.toContain('data:');

    const resultado = await page.evaluate(async () => {
        const documento = globalThis.document;
        const violacoes = [];

        const registarViolacao = (evento) => {
            if (evento.effectiveDirective === 'img-src') {
                violacoes.push(evento.blockedURI);
            }
        };

        documento.addEventListener(
            'securitypolicyviolation',
            registarViolacao,
        );

        const contentor = documento.createElement('div');

        contentor.innerHTML = `
            <button type="button" class="btn-close" id="teste-fechar" aria-label="Fechar"></button>
            <select class="form-select" id="teste-selecao" aria-label="Seleção">
                <option>Opção</option>
            </select>
            <input type="checkbox" class="form-check-input" id="teste-marca" checked aria-label="Checkbox">
            <input type="checkbox" class="form-check-input" id="teste-indeterminada" aria-label="Checkbox indeterminada">
            <input type="radio" class="form-check-input" id="teste-radio" checked aria-label="Radio button">
            <div class="form-check form-switch">
                <input type="checkbox" class="form-check-input" id="teste-interruptor" role="switch" aria-label="Interruptor">
            </div>
        `;

        documento.body.append(contentor);

        const obter = (identificador) =>
            documento.getElementById(identificador);

        obter('teste-indeterminada').indeterminate = true;

        const resultados = [];

        const validarIcone = async (elemento, nome, estado) => {
            const fundo = globalThis.getComputedStyle(elemento)
                .backgroundImage;

            const urls = Array.from(
                fundo.matchAll(/url\(["']?([^"')]+)["']?\)/g),
                (correspondencia) => correspondencia[1],
            );

            const referencia = urls.find((url) =>
                new RegExp(`/${nome}(?:-[^/]+)?\\.svg(?:\\?.*)?$`).test(url),
            );

            if (!referencia) {
                throw new Error(`${estado}: imagem de fundo incorreta: ${fundo}`);
            }

            const endereco = new globalThis.URL(
                referencia,
                documento.baseURI,
            );

            const imagem = new globalThis.Image();

            imagem.src = endereco.href;

            await imagem.decode();

            resultados.push({
                estado,
                origem: endereco.origin,
                largura: imagem.naturalWidth,
                altura: imagem.naturalHeight,
            });
        };

        try {
            await validarIcone(obter('teste-fechar'), 'fechar', 'fechar');
            await validarIcone(obter('teste-selecao'), 'seta-selecao', 'selecao');
            await validarIcone(obter('teste-marca'), 'marca-selecao', 'checkbox');
            await validarIcone(obter('teste-indeterminada'), 'marca-indeterminada', 'indeterminada');
            await validarIcone(obter('teste-radio'), 'marca-radio', 'radio');

            const interruptor = obter('teste-interruptor');

            await validarIcone(interruptor, 'indicador-interruptor', 'desativado');

            interruptor.focus();

            await validarIcone(interruptor, 'indicador-interruptor', 'foco');

            interruptor.checked = true;
            interruptor.blur();

            await validarIcone(interruptor, 'indicador-interruptor', 'ativado');

            return { resultados, violacoes };
        } finally {
            contentor.remove();

            documento.removeEventListener(
                'securitypolicyviolation',
                registarViolacao,
            );
        }
    });

    expect(resultado.resultados).toHaveLength(8);

    for (const icone of resultado.resultados) {
        expect(icone.origem).toBe(new globalThis.URL(page.url()).origin);
        expect(icone.largura).toBeGreaterThan(0);
        expect(icone.altura).toBeGreaterThan(0);
    }

    expect(resultado.violacoes).toEqual([]);
});
