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

async function expectPageHeadingIsCompact(page) {
    const headingFontSize = await page.locator('h1').evaluate((heading) => (
        Number.parseFloat(getComputedStyle(heading).fontSize)
    ));

    expect(headingFontSize).toBeLessThanOrEqual(44);
}

async function expectSectionsUsePageWidth(page) {
    const widthUsage = await page.evaluate(() => {
        const pageElement = document.querySelector('.fi-page');
        const sections = [...document.querySelectorAll('.fi-page .fi-section')]
            .map((section) => section.getBoundingClientRect())
            .filter((rect) => rect.width > 0 && rect.height > 0);

        if (! pageElement || sections.length === 0) {
            return 1;
        }

        const pageRect = pageElement.getBoundingClientRect();
        const left = Math.min(...sections.map((rect) => rect.left));
        const right = Math.max(...sections.map((rect) => rect.right));

        return (right - left) / pageRect.width;
    });

    expect(widthUsage).toBeGreaterThanOrEqual(0.84);
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

test('authenticated Filament form pages use compact headings and the available width', async ({ page }) => {
    await mkdir('storage/app/screenshots', { recursive: true });

    await signIn(page);

    for (const path of [
        '/admin/lancamentos/10/edit',
        '/admin/users/1/edit',
        '/admin/campistas/10/edit',
    ]) {
        await page.goto(`http://juvenil.test${path}`);
        await page.waitForSelector('h1');
        await page.waitForSelector('.fi-page .fi-section');

        await expectPageHeadingIsCompact(page);
        await expectSectionsUsePageWidth(page);
    }

    await page.goto('http://juvenil.test/admin/lancamentos/10/edit');
    await page.screenshot({
        path: 'storage/app/screenshots/playwright-admin-lancamento-edit-form-layout.png',
        fullPage: false,
    });
});

test('authenticated Filament user menu opens downward without orange trigger highlight', async ({ page }) => {
    await mkdir('storage/app/screenshots', { recursive: true });

    await signIn(page);

    const trigger = page.locator('.fi-user-menu-trigger').first();

    await trigger.click();
    await page.waitForSelector('.fi-dropdown-panel:visible');

    const menuGeometry = await page.evaluate(() => {
        const triggerElement = document.querySelector('.fi-user-menu-trigger');
        const panelElement = [...document.querySelectorAll('.fi-dropdown-panel')]
            .find((element) => {
                const rect = element.getBoundingClientRect();

                return rect.width > 0 && rect.height > 0;
            });

        const triggerRect = triggerElement.getBoundingClientRect();
        const panelRect = panelElement.getBoundingClientRect();
        const triggerStyle = getComputedStyle(triggerElement);

        return {
            opensDown: panelRect.top >= triggerRect.bottom,
            panelTop: panelRect.top,
            triggerBottom: triggerRect.bottom,
            triggerBorderColor: triggerStyle.borderTopColor,
            triggerBackgroundColor: triggerStyle.backgroundColor,
        };
    });

    expect(menuGeometry.opensDown).toBe(true);
    expect(menuGeometry.triggerBorderColor).not.toBe('rgb(244, 107, 18)');
    expect(menuGeometry.triggerBackgroundColor).not.toContain('244, 107, 18');

    await page.screenshot({
        path: 'storage/app/screenshots/playwright-admin-user-menu-downward.png',
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
