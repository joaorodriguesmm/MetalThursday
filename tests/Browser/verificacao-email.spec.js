import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

test('apresenta o formulário de reenvio de verificação com acessibilidade', async ({ page }) => {
    await page.goto('/verificacao-email/reenviar');

    await expect(page).toHaveURL(/\/verificacao-email\/reenviar$/u);
    await expect(page.getByRole('heading', {
        name: 'Reenviar verificação de e-mail',
        level: 1,
    })).toBeVisible();
    await expect(page.getByRole('textbox', { name: 'E-mail', exact: true })).toBeVisible();
    await expect(page.getByRole('button', {
        name: 'Enviar nova ligação de verificação',
    })).toBeVisible();

    const resultados = await new AxeBuilder({ page }).analyze();
    expect(resultados.violations).toEqual([]);
});
