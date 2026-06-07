import { expect, test } from '@playwright/test';
import { mkdir } from 'node:fs/promises';

test.use({
    channel: 'chrome',
    viewport: { width: 1440, height: 920 },
});

async function signIn(page) {
    await page.goto('http://juvenil.test/admin/login');
    await page.getByRole('textbox', { name: /e-mail/i }).fill('admin@admin.com');
    await page.getByRole('textbox', { name: /senha/i }).fill('admin');
    await page.getByRole('button', { name: /login|entrar/i }).click();
    await page.waitForURL('**/admin');
    await page.waitForSelector('body.fi-panel-admin:not(.juvenil-admin-auth-body)');
}

async function expectNoDocumentHorizontalOverflow(page) {
    const hasHorizontalOverflow = await page.evaluate(() => (
        document.documentElement.scrollWidth > window.innerWidth + 1
            || document.body.scrollWidth > window.innerWidth + 1
    ));

    expect(hasHorizontalOverflow).toBe(false);
}

async function expectNoFilamentTableHorizontalOverflow(page) {
    const hasTableOverflow = await page.evaluate(() => {
        const table = document.querySelector('.fi-ta-table');
        const wrapper = table?.parentElement;

        if (! table || ! wrapper) {
            return false;
        }

        return table.scrollWidth > wrapper.clientWidth + 2;
    });

    expect(hasTableOverflow).toBe(false);
}

test('authenticated Filament panel keeps branded layout clear of visual obstructions', async ({ page }) => {
    await mkdir('storage/app/screenshots', { recursive: true });

    await signIn(page);

    await expect(page.locator('h1')).toContainText('Início');
    await expect(page.locator('.js-cookie-consent')).toBeHidden();
    await expectNoDocumentHorizontalOverflow(page);

    await page.screenshot({
        path: 'storage/app/screenshots/playwright-admin-dashboard-layout.png',
        fullPage: false,
    });

    await page.goto('http://juvenil.test/admin/campistas');
    await page.waitForSelector('.fi-ta-row');
    await expect(page.locator('h1')).toContainText('Inscrições');
    await expect(page.locator('.js-cookie-consent')).toBeHidden();
    await expectNoDocumentHorizontalOverflow(page);

    const brokenVisibleTableImages = await page.locator('.fi-ta-image img').evaluateAll((images) => (
        images
            .filter((image) => {
                const rect = image.getBoundingClientRect();

                return rect.width > 0 && rect.height > 0;
            })
            .filter((image) => !image.complete || image.naturalWidth === 0 || image.naturalHeight === 0)
            .map((image) => image.getAttribute('src'))
    ));

    expect(brokenVisibleTableImages).toEqual([]);

    await page.screenshot({
        path: 'storage/app/screenshots/playwright-admin-campistas-layout.png',
        fullPage: false,
    });

    await page.goto('http://juvenil.test/admin/lancamentos');
    await page.waitForSelector('.fi-wi-stats-overview-stat');
    await expect(page.locator('h1')).toContainText('Lançamentos');

    const statsSurface = await page.evaluate(() => {
        const widget = document.querySelector('.fi-wi-stats-overview');
        const stat = document.querySelector('.fi-wi-stats-overview-stat');
        const widgetStyle = getComputedStyle(widget);
        const statStyle = getComputedStyle(stat);
        const statBackground = statStyle.backgroundColor.match(/rgba?\(([^)]+)\)/)?.[1]
            .split(',')
            .map((part) => part.trim());
        const statAlpha = statBackground?.[3] === undefined ? 1 : Number.parseFloat(statBackground[3]);

        return {
            widgetBackground: widgetStyle.backgroundColor,
            widgetBorderWidth: widgetStyle.borderTopWidth,
            widgetBoxShadow: widgetStyle.boxShadow,
            statBackgroundAlpha: statAlpha,
        };
    });

    expect(statsSurface.widgetBackground).toBe('rgba(0, 0, 0, 0)');
    expect(statsSurface.widgetBorderWidth).toBe('0px');
    expect(statsSurface.widgetBoxShadow).toBe('none');
    expect(statsSurface.statBackgroundAlpha).toBeLessThanOrEqual(0.55);

    await page.screenshot({
        path: 'storage/app/screenshots/playwright-admin-lancamentos-stats-layout.png',
        fullPage: false,
    });
});

test('authenticated Filament panel keeps mobile layout unobstructed', async ({ browser }) => {
    await mkdir('storage/app/screenshots', { recursive: true });

    const context = await browser.newContext({
        viewport: { width: 390, height: 920 },
        isMobile: true,
    });
    const page = await context.newPage();

    await signIn(page);

    await expect(page.locator('h1')).toContainText('Início');
    await expect(page.locator('.js-cookie-consent')).toBeHidden();
    await expectNoDocumentHorizontalOverflow(page);

    await page.screenshot({
        path: 'storage/app/screenshots/playwright-admin-dashboard-mobile-layout.png',
        fullPage: false,
    });

    await page.goto('http://juvenil.test/admin/campistas');
    await page.waitForSelector('.fi-ta-row');
    await expect(page.locator('h1')).toContainText('Inscrições');
    await expect(page.locator('.js-cookie-consent')).toBeHidden();
    await expectNoDocumentHorizontalOverflow(page);
    await expectNoFilamentTableHorizontalOverflow(page);

    await page.screenshot({
        path: 'storage/app/screenshots/playwright-admin-campistas-mobile-layout.png',
        fullPage: false,
    });

    await context.close();
});
