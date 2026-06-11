import { expect, test } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { mkdir } from 'node:fs/promises';

test.use({
    channel: 'chrome',
    viewport: { width: 1440, height: 920 },
});

const adminBaseUrl = 'http://juvenil.test';
let adminAuthCookies = [];

async function isAdminPanelVisible(page) {
    return page.locator('body.fi-panel-admin:not(.juvenil-admin-auth-body)').isVisible().catch(() => false);
}

async function rememberAdminSession(page) {
    adminAuthCookies = await page.context().cookies(`${adminBaseUrl}/admin`);
}

async function waitForFilamentClient(page) {
    await page.waitForFunction(() => Boolean(window.Alpine && window.Livewire), null, { timeout: 15000 });
}

async function signIn(page) {
    const panelBody = page.locator('body.fi-panel-admin:not(.juvenil-admin-auth-body)');

    if (adminAuthCookies.length > 0) {
        await page.context().addCookies(adminAuthCookies);
        await page.goto(`${adminBaseUrl}/admin`, { waitUntil: 'domcontentloaded' });

        if (await isAdminPanelVisible(page)) {
            await waitForFilamentClient(page);

            return;
        }
    }

    await page.goto(`${adminBaseUrl}/admin/login`, { waitUntil: 'domcontentloaded' });
    const cookieButton = page.getByRole('button', { name: /aceitar cookies/i });

    if (await cookieButton.isVisible().catch(() => false)) {
        await cookieButton.click();
    }

    await page.getByRole('textbox', { name: /e-mail/i }).fill('admin@admin.com');
    await page.getByRole('textbox', { name: /senha/i }).fill('admin');

    for (let attempt = 0; attempt < 2; attempt += 1) {
        const loginButton = page.locator('button[type="submit"]').filter({ hasText: /login|entrar/i }).first();

        if (await isAdminPanelVisible(page)) {
            await waitForFilamentClient(page);
            await rememberAdminSession(page);

            return;
        }

        await expect(loginButton).toBeEnabled({ timeout: 5000 });
        await loginButton.click({ timeout: 5000 }).catch(async (error) => {
            if (! await isAdminPanelVisible(page)) {
                throw error;
            }
        });

        await panelBody.waitFor({ state: 'visible', timeout: 5000 }).catch(() => {});
    }

    await panelBody.waitFor({ state: 'visible', timeout: 15000 });
    await waitForFilamentClient(page);
    await rememberAdminSession(page);
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

function firstRecordId(modelClass) {
    const output = execFileSync('php', [
        'artisan',
        'tinker',
        '--execute',
        `echo ${modelClass}::query()->orderBy('id')->value('id');`,
    ], { encoding: 'utf8' }).trim();

    const id = output.match(/\d+/)?.[0];
    expect(id).toBeTruthy();

    return id;
}

function firstOrCreateTriboId() {
    const output = execFileSync('php', [
        'artisan',
        'tinker',
        '--execute',
        "echo App\\Models\\Tribo::query()->firstOrCreate(['cor' => 'Teste ColorPicker'], ['cor_hex' => '#2563eb'])->id;",
    ], { encoding: 'utf8' }).trim();

    const id = output.match(/\d+/)?.[0];
    expect(id).toBeTruthy();

    return id;
}

function deleteColorPickerTestTribo() {
    execFileSync('php', [
        'artisan',
        'tinker',
        '--execute',
        "App\\Models\\Tribo::query()->where('cor', 'Teste ColorPicker')->delete();",
    ], { encoding: 'utf8' });
}

function createLancamentoClientTotalRecord() {
    const output = execFileSync('php', [
        'artisan',
        'tinker',
        '--execute',
        [
            "$category = App\\Models\\CategoriaLancamento::query()->create(['nome' => uniqid('Categoria browser total '), 'tipo' => 0, 'cor' => '#10b981', 'icone' => 'heroicon-o-banknotes', 'ativo' => true]);",
            "$lancamento = App\\Models\\Lancamento::query()->create(['nome' => 'Browser total client-side', 'descricao' => 'Registro temporário para teste de total no cliente.', 'comprador' => null, 'data' => '2026-07-01', 'valor' => 0, 'tipo' => 0, 'status' => 1, 'forma_pagamento' => 1, 'comprovante' => [], 'batch_code' => null, 'user_id' => null]);",
            "$lancamento->items()->create(['nome' => 'Item temporário', 'valor' => 0, 'categoria_lancamento_id' => $category->id, 'registration_type' => null, 'registration_id' => null, 'descricao' => null]);",
            "echo $lancamento->id.':'.$category->id;",
        ].join(' '),
    ], { encoding: 'utf8' }).trim();

    const ids = output.match(/(\d+):(\d+)/);
    expect(ids).toBeTruthy();

    return {
        categoryId: ids[2],
        lancamentoId: ids[1],
    };
}

function deleteLancamentoClientTotalRecord(lancamentoId, categoryId) {
    execFileSync('php', [
        'artisan',
        'tinker',
        '--execute',
        `App\\Models\\LancamentoItem::query()->where('lancamento_id', ${Number(lancamentoId)})->delete(); App\\Models\\Lancamento::query()->where('id', ${Number(lancamentoId)})->delete(); App\\Models\\CategoriaLancamento::query()->where('id', ${Number(categoryId)})->delete();`,
    ], { encoding: 'utf8' });
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

    const tableSurface = await page.evaluate(() => {
        const table = document.querySelector('.fi-ta');
        const content = document.querySelector('.fi-ta-content-ctn');
        const head = document.querySelector('.fi-ta-table thead');
        const tableStyle = getComputedStyle(table);
        const contentStyle = getComputedStyle(content);
        const headStyle = getComputedStyle(head);

        return {
            tableBackground: tableStyle.backgroundColor,
            tableBackgroundImage: tableStyle.backgroundImage,
            contentBackground: contentStyle.backgroundColor,
            headBackground: headStyle.backgroundColor,
            headBackgroundImage: headStyle.backgroundImage,
        };
    });

    expect(tableSurface.tableBackground).toBe('rgb(4, 31, 35)');
    expect(tableSurface.tableBackgroundImage).toContain('rgba(4, 31, 35, 0.96)');
    expect(tableSurface.contentBackground).toBe('rgba(4, 31, 35, 0.66)');
    expect(tableSurface.headBackground).toBe('rgb(4, 31, 35)');
    expect(tableSurface.headBackgroundImage).toContain('rgba(7, 61, 69, 0.86)');

    await page.screenshot({
        path: 'storage/app/screenshots/playwright-admin-campistas-layout.png',
        fullPage: false,
    });

    await page.goto('http://juvenil.test/admin/lancamentos');
    await page.waitForSelector('.fi-wi-stats-overview-stat');
    await expect(page.locator('h1')).toContainText('Lançamentos');
    await expect(page.getByRole('link', { name: /categorias de lançamento/i })).toBeVisible();

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

test('authenticated dashboard filters use Filament custom select controls', async ({ page }) => {
    await signIn(page);

    for (const path of ['/admin', '/admin/financeiro']) {
        await page.goto(`${adminBaseUrl}${path}`, { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('[wire\\:partial="table-filters-form"] .fi-fo-field');

        const filterControls = await page.evaluate(() => {
            const filtersForm = document.querySelector('[wire\\:partial="table-filters-form"]');

            return {
                nativeSelectCount: filtersForm?.querySelectorAll('.fi-fo-select-wrp select').length ?? 0,
                customSelectCount: filtersForm?.querySelectorAll('.fi-fo-select-wrp .fi-select-input-btn').length ?? 0,
            };
        });

        expect(filterControls.nativeSelectCount).toBe(0);
        expect(filterControls.customSelectCount).toBeGreaterThan(0);
    }

    await page.goto(`${adminBaseUrl}/admin`, { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('[wire\\:partial="table-filters-form"] .fi-fo-field');

    const filtersForm = page.locator('[wire\\:partial="table-filters-form"]');
    const filterFields = filtersForm.locator('.fi-fo-field');
    const parishField = filterFields.filter({ hasText: 'Paróquia' }).first();

    await expect(filterFields.filter({ hasText: 'Comunidade' })).toHaveCount(0);

    await parishField.locator('.fi-select-input-btn').click();
    await parishField.getByRole('option', { name: 'Santa Luzia' }).click();

    const communitySelectField = filterFields
        .filter({ hasText: 'Comunidade' })
        .filter({ has: page.locator('.fi-select-input-btn') })
        .first();

    await expect(communitySelectField).toBeVisible();
    await communitySelectField.locator('.fi-select-input-btn').click();
    await expect(communitySelectField.getByRole('option', { name: 'Santa Teresinha' })).toBeVisible();
    await expect(communitySelectField.getByRole('option', { name: 'Nossa Senhora Aparecida' })).toBeVisible();

    await page.keyboard.press('Escape');
    await parishField.locator('.fi-select-input-btn').click();
    await parishField.getByRole('option', { name: 'Outra paróquia' }).click();

    const communityTextField = filterFields
        .filter({ hasText: 'Comunidade' })
        .filter({ has: page.locator('input[placeholder="Digite parte do nome"]') })
        .first();

    await expect(communityTextField).toBeVisible();
    await expect(communityTextField.locator('.fi-select-input-btn')).toHaveCount(0);
    await communityTextField.locator('input[placeholder="Digite parte do nome"]').fill('São Pedro');
});

test('authenticated Filament table column manager opens outside the table and applies columns live', async ({ page }) => {
    await mkdir('storage/app/screenshots', { recursive: true });

    await signIn(page);
    await page.goto('http://juvenil.test/admin/campistas');
    await page.waitForSelector('.fi-ta-row');

    await page.locator('.fi-ta-col-manager-dropdown .fi-dropdown-trigger').first().click();
    await page.locator('.fi-ta-col-manager-dropdown > .fi-dropdown-panel').waitFor({ state: 'visible' });

    const dropdownGeometry = await page.evaluate(() => {
        const table = document.querySelector('.fi-ta');
        const panel = document.querySelector('.fi-ta-col-manager-dropdown > .fi-dropdown-panel');
        const tableRect = table.getBoundingClientRect();
        const panelRect = panel.getBoundingClientRect();
        const tableStyle = getComputedStyle(table);
        const panelStyle = getComputedStyle(panel);

        return {
            panelHasRoomForScrollableContent: panelRect.height >= 320,
            panelIsInsideViewport: panelRect.bottom <= window.innerHeight,
            panelOverflowY: panelStyle.overflowY,
            panelZIndex: Number.parseInt(panelStyle.zIndex, 10),
            tableOverflow: tableStyle.overflow,
        };
    });

    expect(dropdownGeometry.tableOverflow).toBe('visible');
    expect(dropdownGeometry.panelHasRoomForScrollableContent).toBe(true);
    expect(dropdownGeometry.panelIsInsideViewport).toBe(true);
    expect(dropdownGeometry.panelOverflowY).toBe('auto');
    expect(dropdownGeometry.panelZIndex).toBeGreaterThanOrEqual(80);

    const ageHeader = page.locator('.fi-ta-table thead .fi-ta-header-cell').filter({ hasText: 'Idade' });
    const ageToggle = page.locator('.fi-ta-col-manager-label').filter({ hasText: 'Idade' }).first();
    const ageCheckbox = ageToggle.locator('input[type="checkbox"]').first();

    if (! await ageCheckbox.isChecked()) {
        await ageToggle.click();
    }

    await expect(ageHeader).toBeVisible({ timeout: 5000 });
    await expect(page.getByRole('button', { name: /aplicar colunas/i })).toHaveCount(0);

    await page.screenshot({
        path: 'storage/app/screenshots/playwright-admin-column-manager-dropdown.png',
        fullPage: false,
    });
});

test('authenticated tribe color picker opens above relation tables', async ({ page }) => {
    const triboId = firstOrCreateTriboId();

    try {
        await signIn(page);
        await page.goto(`${adminBaseUrl}/admin/tribos/${triboId}/edit`);
        await page.waitForSelector('.fi-fo-color-picker');
        await page.waitForSelector('.fi-ta');

        const colorPicker = page.locator('.fi-fo-color-picker').first();
        await colorPicker.locator('input').focus();
        await expect(page.locator('.fi-fo-color-picker-panel')).toBeVisible();

        const colorPickerLayer = await page.evaluate(() => {
            const panel = [...document.querySelectorAll('.fi-fo-color-picker-panel')]
                .find((element) => {
                    const rect = element.getBoundingClientRect();
                    const style = getComputedStyle(element);

                    return rect.width > 0 && rect.height > 0 && style.display !== 'none';
                });
            const section = panel?.closest('.fi-section, .fi-sc');

            if (! panel || ! section) {
                return null;
            }

            const panelRect = panel.getBoundingClientRect();
            const topElement = document.elementFromPoint(
                panelRect.left + (panelRect.width / 2),
                panelRect.top + (panelRect.height / 2),
            );

            return {
                panelZIndex: Number.parseInt(getComputedStyle(panel).zIndex, 10),
                sectionZIndex: Number.parseInt(getComputedStyle(section).zIndex, 10),
                topElementInsidePanel: panel.contains(topElement),
            };
        });

        expect(colorPickerLayer).not.toBeNull();
        expect(colorPickerLayer.sectionZIndex).toBeGreaterThanOrEqual(70);
        expect(colorPickerLayer.panelZIndex).toBeGreaterThanOrEqual(100);
        expect(colorPickerLayer.topElementInsidePanel).toBe(true);
    } finally {
        deleteColorPickerTestTribo();
    }
});

test('authenticated Filament notifications render above the branded topbar', async ({ page }) => {
    await mkdir('storage/app/screenshots', { recursive: true });

    await signIn(page);
    await page.goto('http://juvenil.test/admin');
    await page.waitForSelector('.fi-topbar');
    await page.waitForFunction(() => typeof window.FilamentNotification === 'function');

    await page.evaluate(() => {
        new window.FilamentNotification()
            .title('Notificação de teste')
            .body('Validação de empilhamento visual.')
            .success()
            .persistent()
            .send();
    });

    await page.waitForSelector('.fi-no-notification');

    const notificationStacking = await page.evaluate(() => {
        const topbar = document.querySelector('.fi-topbar');
        const notifications = document.querySelector('.fi-no');
        const notification = document.querySelector('.fi-no-notification');

        if (! topbar || ! notifications || ! notification) {
            return null;
        }

        const topbarRect = topbar.getBoundingClientRect();
        const notificationRect = notification.getBoundingClientRect();
        const probeX = Math.min(notificationRect.right - 16, window.innerWidth - 16);
        const probeY = Math.max(notificationRect.top + 24, topbarRect.top + Math.min(36, topbarRect.height - 12));
        const topElement = document.elementFromPoint(probeX, probeY);

        return {
            topbarZIndex: Number.parseInt(getComputedStyle(topbar).zIndex, 10),
            notificationsZIndex: Number.parseInt(getComputedStyle(notifications).zIndex, 10),
            notificationOverlapsTopbar: notificationRect.top < topbarRect.bottom,
            topElementInsideNotification: notification.contains(topElement),
            topElementClassName: String(topElement?.className ?? ''),
        };
    });

    expect(notificationStacking).not.toBeNull();
    expect(notificationStacking.notificationOverlapsTopbar).toBe(true);
    expect(notificationStacking.notificationsZIndex).toBeGreaterThan(notificationStacking.topbarZIndex);
    expect(notificationStacking.topElementInsideNotification).toBe(true);

    await page.screenshot({
        path: 'storage/app/screenshots/playwright-admin-notification-topbar-stacking.png',
        fullPage: false,
    });
});

test('authenticated permission groups list includes the finance role', async ({ page }) => {
    await signIn(page);

    await page.goto(`${adminBaseUrl}/admin/roles`, { waitUntil: 'domcontentloaded' });
    await waitForFilamentClient(page);

    await expect(page.locator('h1')).toContainText(/Grupos de permissões/i);
    await expect(page.getByRole('cell', { name: 'Financeiro', exact: true })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Super Administrador', exact: true })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Administrador', exact: true })).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Enfermaria', exact: true })).toBeVisible();
});

test('authenticated user role attach modal select opens above the modal footer', async ({ page }) => {
    await signIn(page);

    const userId = firstRecordId('App\\Models\\User');

    await page.goto(`${adminBaseUrl}/admin/users/${userId}/edit`, { waitUntil: 'domcontentloaded' });
    await waitForFilamentClient(page);

    await page.getByRole('button', { name: /vincular/i }).first().click();
    await expect(page.getByRole('heading', { name: /vincular grupo de permissão/i })).toBeVisible();

    const attachModal = page.locator('.fi-modal-window:visible').filter({ hasText: /Vincular Grupo De Permissão/i }).first();
    const selectButton = attachModal.locator('.fi-select-input-btn').first();

    await selectButton.click();
    await expect(attachModal.locator('.fi-dropdown-panel[role="listbox"]:visible')).toBeVisible();

    const modalLayering = await page.evaluate(() => {
        const modal = [...document.querySelectorAll('.fi-modal-window')]
            .find((element) => element.getBoundingClientRect().width > 0 && element.textContent?.includes('Vincular Grupo De Permissão'));
        const panel = modal?.querySelector('.fi-dropdown-panel[role="listbox"]');
        const footer = modal?.querySelector('.fi-modal-footer');

        if (! modal || ! panel || ! footer) {
            return null;
        }

        const panelRect = panel.getBoundingClientRect();
        const footerRect = footer.getBoundingClientRect();
        const probeX = Math.min(panelRect.left + 24, panelRect.right - 8);
        const probeY = Math.max(footerRect.top + 10, panelRect.top + 10);
        const topElement = document.elementFromPoint(probeX, probeY);

        return {
            panelOverlapsFooter: panelRect.bottom > footerRect.top,
            footerCoversPanel: panelRect.bottom > footerRect.top && ! panel.contains(topElement),
            topElementInsidePanel: panel.contains(topElement),
            topElementClassName: String(topElement?.className ?? ''),
        };
    });

    expect(modalLayering).not.toBeNull();
    expect(modalLayering.footerCoversPanel).toBe(false);
});

test('authenticated Filament sidebar flyouts stay above table surfaces when collapsed', async ({ page }) => {
    await mkdir('storage/app/screenshots', { recursive: true });

    await signIn(page);
    await page.goto('http://juvenil.test/admin/campistas');
    await page.waitForSelector('.fi-ta-row');

    const collapseButton = page.getByRole('button', { name: /recolher barra lateral/i }).first();

    if (await collapseButton.isVisible().catch(() => false)) {
        await collapseButton.click();
    }

    const settingsGroup = page.locator('.fi-sidebar .fi-sidebar-group').filter({ hasText: 'Configurações' }).last();

    await settingsGroup.locator('.fi-sidebar-group-dropdown-trigger-btn').click();

    const settingsFlyout = page.locator('.fi-sidebar .fi-dropdown-panel').filter({ hasText: 'Configurações Gerais' }).first();

    await settingsFlyout.waitFor({ state: 'visible' });

    const flyoutStacking = await settingsFlyout.evaluate((panel) => {
        const sidebar = panel.closest('.fi-sidebar');
        const panelRect = panel.getBoundingClientRect();
        const probePoints = [
            [panelRect.left + 40, panelRect.top + 20],
            [panelRect.right - 8, panelRect.top + 20],
            [panelRect.right - 8, panelRect.bottom - 10],
        ].map(([x, y]) => {
            const topElement = document.elementFromPoint(x, y);

            return {
                x,
                y,
                topElementClassName: String(topElement?.className ?? ''),
                topElementInsidePanel: topElement ? panel.contains(topElement) : false,
            };
        });

        return {
            panelZIndex: getComputedStyle(panel).zIndex,
            sidebarZIndex: sidebar ? getComputedStyle(sidebar).zIndex : null,
            probePoints,
        };
    });

    expect(Number.parseInt(flyoutStacking.sidebarZIndex, 10)).toBeGreaterThanOrEqual(90);
    expect(Number.parseInt(flyoutStacking.panelZIndex, 10)).toBeGreaterThanOrEqual(100);
    expect(flyoutStacking.probePoints.every((point) => point.topElementInsidePanel)).toBe(true);

    await page.screenshot({
        path: 'storage/app/screenshots/playwright-admin-sidebar-flyout-stacking.png',
        fullPage: false,
    });
});

test('authenticated Filament form pages use compact headings and the available width', async ({ page }) => {
    await mkdir('storage/app/screenshots', { recursive: true });

    await signIn(page);

    const lancamentoId = firstRecordId('App\\Models\\Lancamento');
    const userId = firstRecordId('App\\Models\\User');
    const campistaId = firstRecordId('App\\Models\\Campista');

    for (const path of [
        `/admin/lancamentos/${lancamentoId}/edit`,
        '/admin/categoria-lancamentos/create',
        `/admin/users/${userId}/edit`,
        '/admin/general-settings-page',
        `/admin/campistas/${campistaId}/edit`,
    ]) {
        await page.goto(`http://juvenil.test${path}`);
        await page.waitForSelector('h1');
        await page.waitForSelector('.fi-page .fi-section');

        await expectPageHeadingIsCompact(page);
        await expectSectionsUsePageWidth(page);

        if (path === '/admin/general-settings-page') {
            await expect(page.getByText('Idade mínima')).toBeVisible();
            await expect(page.getByText('Idade máxima')).toBeVisible();
            await expect(page.getByText('Use 0 para liberar inscrições de qualquer idade nesse limite.').first()).toBeVisible();
            await expect(page.getByText('Mensagem de Bloqueio')).toHaveCount(0);
            await expect(page.getByText('Conteúdo bloco de inscrições dos campistas')).toHaveCount(0);
        }
    }

    await page.goto(`http://juvenil.test/admin/lancamentos/${lancamentoId}/edit`);
    await page.screenshot({
        path: 'storage/app/screenshots/playwright-admin-lancamento-edit-form-layout.png',
        fullPage: false,
    });
});

test('authenticated launch edit form uses a balanced operational workspace', async ({ page }) => {
    await mkdir('storage/app/screenshots', { recursive: true });

    await signIn(page);

    const lancamentoId = firstRecordId('App\\Models\\Lancamento');

    await page.goto(`http://juvenil.test/admin/lancamentos/${lancamentoId}/edit`);
    await page.waitForSelector('.fi-page .fi-section');

    const launchWorkspace = await page.evaluate(() => {
        const pageElement = document.querySelector('.fi-page');
        const sections = [...document.querySelectorAll('.fi-page .fi-section')]
            .map((section) => ({
                text: section.textContent?.replace(/\s+/g, ' ').trim() ?? '',
                rect: section.getBoundingClientRect(),
            }))
            .filter(({ rect }) => rect.width > 0 && rect.height > 0);

        const byText = (text) => sections.find((section) => section.text.includes(text));
        const pageRect = pageElement?.getBoundingClientRect();
        const launch = byText('Lançamento');
        const linkedRegistrations = byText('Itens do lançamento');
        const receipts = byText('Comprovantes');

        if (! pageRect || ! launch || ! linkedRegistrations || ! receipts) {
            return null;
        }

        return {
            pageWidth: pageRect.width,
            launchWidthRatio: launch.rect.width / pageRect.width,
            linkedRegistrationsWidthRatio: linkedRegistrations.rect.width / pageRect.width,
            receiptsWidthRatio: receipts.rect.width / pageRect.width,
            secondaryTopDelta: Math.abs(linkedRegistrations.rect.top - receipts.rect.top),
            receiptsRightDelta: Math.abs(pageRect.right - receipts.rect.right),
            linkedBeforeReceipts: linkedRegistrations.rect.right <= receipts.rect.left + 2,
        };
    });

    expect(launchWorkspace).not.toBeNull();
    expect(launchWorkspace.launchWidthRatio).toBeGreaterThanOrEqual(0.84);
    expect(launchWorkspace.linkedRegistrationsWidthRatio).toBeGreaterThan(0.5);
    expect(launchWorkspace.receiptsWidthRatio).toBeGreaterThan(0.24);
    expect(launchWorkspace.secondaryTopDelta).toBeLessThanOrEqual(8);
    expect(launchWorkspace.receiptsRightDelta).toBeLessThanOrEqual(2);
    expect(launchWorkspace.linkedBeforeReceipts).toBe(true);

    await page.getByRole('button', { name: /dúvidas/i }).click();
    const helpDialog = page.getByRole('dialog');

    await expect(helpDialog.getByText('Guia do lançamento financeiro')).toBeVisible();
    await expect(helpDialog.getByText('Como editar um lançamento financeiro')).toBeVisible();
    await expect(helpDialog.getByText('Exemplos de preenchimento')).toBeVisible();
    await expect(helpDialog.locator('img[src*="lancamento-help-"]')).toHaveCount(3);
    await helpDialog.locator('.fi-modal-footer').getByRole('button', { name: /fechar/i }).click();
    await expect(page.locator('.fi-modal.fi-modal-open')).toHaveCount(0);

    await page.screenshot({
        path: 'storage/app/screenshots/playwright-admin-lancamento-edit-workspace.png',
        fullPage: false,
    });
});

test('launch item total updates in the browser without Livewire requests from value typing', async ({ page }) => {
    await signIn(page);

    const { categoryId, lancamentoId } = createLancamentoClientTotalRecord();

    try {
        await page.goto(`${adminBaseUrl}/admin/lancamentos/${lancamentoId}/edit`, { waitUntil: 'domcontentloaded' });
        await waitForFilamentClient(page);
        await page.waitForSelector('[data-lancamento-total]');

        const valueInput = page.locator('input[id^="form.items."][id$=".valor"]').first();

        await expect(valueInput).toBeVisible();

        const livewireRequests = [];

        page.on('request', (request) => {
            if (request.method() !== 'GET' && request.url().includes('/livewire')) {
                livewireRequests.push(request.url());
            }
        });

        await valueInput.fill('');
        await valueInput.pressSequentially('12345');
        await page.waitForTimeout(500);

        await expect(valueInput).toHaveValue('123,45');
        await expect(page.locator('[data-lancamento-total]')).toContainText('R$ 123,45');
        expect(livewireRequests).toEqual([]);
    } finally {
        deleteLancamentoClientTotalRecord(lancamentoId, categoryId);
    }
});

test('general settings date picker opens above the following sections', async ({ page }) => {
    await mkdir('storage/app/screenshots', { recursive: true });

    await signIn(page);

    await page.goto('http://juvenil.test/admin/general-settings-page');
    await page.waitForSelector('.fi-page .fi-section');

    await page.locator('.fi-fo-date-time-picker-trigger').first().click();
    await page.waitForSelector('.fi-fo-date-time-picker-panel');

    const datePickerStacking = await page.evaluate(() => {
        const panel = document.querySelector('.fi-fo-date-time-picker-panel');
        const section = panel?.closest('.fi-section');

        if (! panel || ! section) {
            return null;
        }

        const panelRect = panel.getBoundingClientRect();
        const sectionRect = section.getBoundingClientRect();
        const probeX = panelRect.left + Math.min(80, panelRect.width / 2);
        const probeY = Math.min(panelRect.bottom - 12, window.innerHeight - 12);
        const topElement = document.elementFromPoint(probeX, probeY);

        return {
            panelBottom: panelRect.bottom,
            sectionBottom: sectionRect.bottom,
            sectionZIndex: getComputedStyle(section).zIndex,
            topElementInsidePanel: panel.contains(topElement),
        };
    });

    expect(datePickerStacking).not.toBeNull();
    expect(datePickerStacking.panelBottom).toBeGreaterThan(datePickerStacking.sectionBottom);
    expect(datePickerStacking.sectionZIndex).not.toBe('auto');
    expect(datePickerStacking.topElementInsidePanel).toBe(true);

    await page.screenshot({
        path: 'storage/app/screenshots/playwright-admin-general-settings-datepicker.png',
        fullPage: false,
    });
});

test('authenticated Filament select dropdowns open above sibling sections', async ({ page }) => {
    await mkdir('storage/app/screenshots', { recursive: true });

    await signIn(page);

    const lancamentoId = firstRecordId('App\\Models\\Lancamento');

    await page.goto(`http://juvenil.test/admin/lancamentos/${lancamentoId}/edit`);
    await page.waitForSelector('.fi-page .fi-section');

    const paymentField = page.locator('.fi-fo-field').filter({ hasText: 'Forma de Pagamento' });

    await paymentField.locator('.fi-select-input-btn').click();
    await expect(paymentField.locator('.fi-dropdown-panel[role="listbox"]')).toBeVisible();

    const selectStacking = await paymentField.evaluate((field) => {
        const section = field.closest('.fi-section');
        const panel = field.querySelector('.fi-dropdown-panel[role="listbox"]');

        if (! section || ! panel) {
            return null;
        }

        const panelRect = panel.getBoundingClientRect();
        const sectionRect = section.getBoundingClientRect();
        const probePoints = [
            panelRect.top + 24,
            panelRect.top + panelRect.height / 2,
            Math.min(panelRect.bottom - 12, window.innerHeight - 12),
        ].map((probeY) => {
            const probeX = panelRect.left + Math.min(120, panelRect.width / 2);
            const topElement = document.elementFromPoint(probeX, probeY);

            return {
                probeY,
                topElementTag: topElement?.tagName ?? null,
                topElementClassName: String(topElement?.className ?? ''),
                topElementInsidePanel: panel.contains(topElement),
            };
        });

        return {
            panelBottom: panelRect.bottom,
            sectionBottom: sectionRect.bottom,
            sectionPosition: getComputedStyle(section).position,
            sectionZIndex: getComputedStyle(section).zIndex,
            panelZIndex: getComputedStyle(panel).zIndex,
            probePoints,
        };
    });

    expect(selectStacking).not.toBeNull();
    expect(selectStacking.sectionPosition).toBe('relative');
    expect(Number.parseInt(selectStacking.sectionZIndex, 10)).toBeGreaterThanOrEqual(60);
    expect(Number.parseInt(selectStacking.panelZIndex, 10)).toBeGreaterThanOrEqual(70);
    expect(selectStacking.probePoints.every((point) => point.topElementInsidePanel)).toBe(true);

    await page.screenshot({
        path: 'storage/app/screenshots/playwright-admin-select-dropdown-stacking.png',
        fullPage: false,
    });
});

test('authenticated reports page uses native Filament filters with help and same-tab preview', async ({ page }) => {
    test.setTimeout(120000);

    await mkdir('storage/app/screenshots', { recursive: true });

    await signIn(page);

    await page.goto('http://juvenil.test/admin/relatorios');
    await page.waitForSelector('.juvenil-report-page');
    await page.waitForSelector('.juvenil-report-form .fi-section');

    await expect(page.locator('h1')).toContainText('Relatórios dinâmicos');
    await expect(page.getByRole('link', { name: /abrir prévia/i })).toBeVisible();
    await expect(page.getByRole('button', { name: /dúvidas/i })).toBeVisible();
    await expect(page.locator('.juvenil-report-form select')).toHaveCount(0);
    await expect(page.locator('.juvenil-report-card')).toHaveCount(0);
    await expectNoDocumentHorizontalOverflow(page);

    const layoutState = await page.evaluate(() => {
        const pageElement = document.querySelector('.fi-page');
        const brief = document.querySelector('.juvenil-report-brief');
        const meter = document.querySelector('.juvenil-report-brief__meter');
        const form = document.querySelector('.juvenil-report-form .fi-section');
        const loading = document.querySelector('[data-report-preview-loading]');

        if (! pageElement || ! brief || ! form || ! meter || ! loading) {
            return null;
        }

        const pageRect = pageElement.getBoundingClientRect();
        const briefRect = brief.getBoundingClientRect();
        const formRect = form.getBoundingClientRect();
        const meterRect = meter.getBoundingClientRect();

        return {
            briefWidthRatio: briefRect.width / pageRect.width,
            formWidthRatio: formRect.width / pageRect.width,
            formIsBelowBrief: formRect.top > briefRect.bottom,
            meterIsInsideBrief: meterRect.right <= briefRect.right && meterRect.left >= briefRect.left,
            loadingStartsHidden: loading.hidden,
        };
    });

    expect(layoutState).not.toBeNull();
    expect(layoutState.briefWidthRatio).toBeGreaterThanOrEqual(0.84);
    expect(layoutState.formWidthRatio).toBeGreaterThanOrEqual(0.84);
    expect(layoutState.formIsBelowBrief).toBe(true);
    expect(layoutState.meterIsInsideBrief).toBe(true);
    expect(layoutState.loadingStartsHidden).toBe(true);

    await page.getByRole('button', { name: /dúvidas/i }).click();
    const helpDialog = page.getByRole('dialog');
    await expect(page.locator('.fi-modal.fi-modal-open')).toBeVisible();
    await expect(helpDialog.getByText('Como usar a central de relatórios')).toBeVisible();
    await expect(helpDialog.getByText('Fichas de inscrição')).toBeVisible();
    await helpDialog.locator('.fi-modal-footer').getByRole('button', { name: /fechar/i }).click();
    await expect(page.locator('.fi-modal.fi-modal-open')).toHaveCount(0);

    const reportTypeField = page.locator('.juvenil-report-form .fi-fo-field').filter({ hasText: 'Tipo de relatório' }).first();
    const searchInput = page.getByPlaceholder('Nome, responsável, bairro ou cidade');

    await searchInput.fill('Ana Costa 101');
    await page.waitForFunction(() => (
        [...document.querySelectorAll('a')]
            .find((link) => link.textContent?.includes('Abrir prévia'))
            ?.href
            .includes('search=Ana')
    ));

    await reportTypeField.locator('.fi-select-input-btn').click();
    await expect(reportTypeField.locator('.fi-dropdown-panel[role="listbox"]')).toBeVisible();

    const dropdownState = await reportTypeField.evaluate((field) => {
        const section = field.closest('.fi-section');
        const panel = field.querySelector('.fi-dropdown-panel[role="listbox"]');

        if (! section || ! panel) {
            return null;
        }

        const panelRect = panel.getBoundingClientRect();
        const probeX = panelRect.left + Math.min(120, panelRect.width / 2);
        const probeY = Math.min(panelRect.bottom - 12, window.innerHeight - 12);
        const topElement = document.elementFromPoint(probeX, probeY);

        return {
            sectionPosition: getComputedStyle(section).position,
            sectionZIndex: getComputedStyle(section).zIndex,
            panelZIndex: getComputedStyle(panel).zIndex,
            topElementInsidePanel: panel.contains(topElement),
        };
    });

    expect(dropdownState).not.toBeNull();
    expect(dropdownState.sectionPosition).toBe('relative');
    expect(Number.parseInt(dropdownState.sectionZIndex, 10)).toBeGreaterThanOrEqual(60);
    expect(Number.parseInt(dropdownState.panelZIndex, 10)).toBeGreaterThanOrEqual(70);
    expect(dropdownState.topElementInsidePanel).toBe(true);

    await page.keyboard.press('Escape');

    const previewAction = page.locator('[data-report-preview-link="true"]').first();
    const previewLink = page.getByRole('link', { name: /abrir prévia/i });
    const sensitiveHealthToggle = page.getByRole('switch', { name: /exibir dados médicos/i });

    await sensitiveHealthToggle.check({ force: true });
    await expect(page.getByText('Dados médicos sensíveis', { exact: true })).toBeVisible();
    await expect(previewAction).toHaveAttribute('aria-disabled', 'true');

    await page.getByRole('checkbox', {
        name: /confirmo que desejo exibir dados médicos sensíveis/i,
    }).check({ force: true });

    await expect(previewAction).not.toHaveAttribute('aria-disabled', 'true');
    await expect(previewAction).toHaveAttribute('href', /show_sensitive_health=1/);
    await expect(previewAction).toHaveAttribute('href', /confirm_sensitive_health=1/);

    const reportTypeChecks = [
        ['Fichas de inscrição', 'registration_fichas', 'Fichas de inscrição'],
        ['Quadrante por tribo', 'tribe_quadrant', 'Quadrante das inscrições por tribo'],
        ['Lista médica da enfermaria', 'sensitive_health', 'Lista médica da enfermaria'],
        ['Contatos e endereços', 'mission_contacts', 'Contatos e endereços para missão'],
    ];

    for (const [optionLabel, expectedType, expectedTitle] of reportTypeChecks) {
        await reportTypeField.locator('.fi-select-input-btn').click();
        await page.getByRole('option', { name: optionLabel, exact: true }).click();

        await page.waitForFunction((type) => (
            [...document.querySelectorAll('a')]
                .find((link) => link.textContent?.includes('Abrir prévia'))
                ?.href
                .includes(`type=${type}`)
        ), expectedType);

        const previewResponsePromise = page.waitForResponse((response) => (
            response.url().includes('/admin/relatorios/imprimir')
        ));

        await previewLink.click();

        const previewResponse = await previewResponsePromise;
        const previewHeaders = previewResponse.headers();

        expect(previewResponse.status()).toBe(200);
        expect(previewHeaders['content-type']).toContain('text/html');
        await page.waitForURL(/\/admin\/relatorios\/imprimir/);
        expect(page.url()).toContain(`type=${expectedType}`);
        await expect(page.locator('.report-print-toolbar')).toBeVisible();
        await expect(page.locator('.report-print-toolbar [data-report-action-icon]')).toHaveCount(3);
        await expect(page.locator('.report-print-toolbar .report-print-action__icon')).toHaveCount(3);
        await expect(page.getByText(expectedTitle).first()).toBeVisible();
        await expect(page.getByRole('button', { name: /salvar pdf/i })).toBeVisible();
        await expect(page.getByRole('button', { name: /imprimir/i })).toBeVisible();

        await page.goBack({ waitUntil: 'domcontentloaded' });
        await page.waitForSelector('.juvenil-report-page');
        await page.waitForSelector('.juvenil-report-form .fi-section');
    }

    await page.evaluate(() => window.scrollTo(0, 0));

    await page.screenshot({
        path: 'storage/app/screenshots/playwright-admin-reports-filters-layout.png',
        fullPage: false,
    });
});

test('authenticated settings page previews PIX QR code as a square image upload', async ({ page }) => {
    await mkdir('storage/app/screenshots', { recursive: true });

    await signIn(page);

    await page.goto('http://juvenil.test/admin/general-settings-page');
    await page.waitForSelector('.fi-page .fi-section');
    await page.waitForSelector('.juvenil-pix-qr-upload .filepond--root');

    const emptyUploadState = await page.evaluate(() => {
        const paymentSection = [...document.querySelectorAll('.fi-section')]
            .find((section) => section.textContent.includes('Pagamento PIX'));
        const root = paymentSection?.querySelector('.filepond--root')?.getBoundingClientRect();

        return {
            rootWidth: root?.width ?? null,
            rootHeight: root?.height ?? null,
            rootAspectDelta: root ? Math.abs((root.width / root.height) - 1) : 1,
        };
    });

    expect(emptyUploadState.rootWidth).toBeGreaterThan(180);
    expect(emptyUploadState.rootWidth).toBeLessThanOrEqual(260);
    expect(emptyUploadState.rootHeight).toBeLessThanOrEqual(260);
    expect(emptyUploadState.rootAspectDelta).toBeLessThan(0.08);

    await page.locator('input[type="file"]').first().setInputFiles('/home/lucas/code/juvenil/public/img/hero-desktop.png');
    await page.waitForSelector('.filepond--item');
    await page.waitForTimeout(1800);

    const previewState = await page.evaluate(() => {
        const paymentSection = [...document.querySelectorAll('.fi-section')]
            .find((section) => section.textContent.includes('Pagamento PIX'));
        const root = paymentSection?.querySelector('.filepond--root')?.getBoundingClientRect();
        const item = paymentSection?.querySelector('.filepond--item')?.getBoundingClientRect();
        const imagePreview = paymentSection?.querySelector('.filepond--image-preview-wrapper, .filepond--image-preview')?.getBoundingClientRect();
        const editor = document.querySelector('.fi-fo-file-upload-editor');

        return {
            hasImagePreview: Boolean(imagePreview),
            rootWidth: root?.width ?? null,
            rootHeight: root?.height ?? null,
            rootAspectDelta: root ? Math.abs((root.width / root.height) - 1) : 1,
            itemAspectDelta: item ? Math.abs((item.width / item.height) - 1) : 1,
            editorCount: document.querySelectorAll('.fi-fo-file-upload-editor').length,
            editorVisible: editor ? getComputedStyle(editor).display !== 'none' : false,
            horizontalOverflow: document.documentElement.scrollWidth > window.innerWidth + 1
                || document.body.scrollWidth > window.innerWidth + 1,
        };
    });

    expect(previewState.hasImagePreview).toBe(true);
    expect(previewState.rootWidth).toBeGreaterThan(180);
    expect(previewState.rootWidth).toBeLessThanOrEqual(260);
    expect(previewState.rootHeight).toBeLessThanOrEqual(260);
    expect(previewState.rootAspectDelta).toBeLessThan(0.08);
    expect(previewState.itemAspectDelta).toBeLessThan(0.08);
    expect(previewState.editorCount).toBe(0);
    expect(previewState.editorVisible).toBe(false);
    expect(previewState.horizontalOverflow).toBe(false);

    await page.screenshot({
        path: 'storage/app/screenshots/playwright-admin-pix-qrcode-upload-preview.png',
        fullPage: false,
    });
});

test('authenticated Campista view renders as a ficha before editing', async ({ page }) => {
    await mkdir('storage/app/screenshots', { recursive: true });

    await signIn(page);

    const campistaId = firstRecordId('App\\Models\\Campista');

    await page.goto(`http://juvenil.test/admin/campistas/${campistaId}`);
    await page.waitForSelector('.juvenil-registration-card');

    await expect(page.locator('h1')).toContainText('Ficha de inscrição');
    await expect(page.locator('.juvenil-registration-card')).toContainText('Ficha oficial');
    await expect(page.locator('.juvenil-registration-card')).toContainText('Dados pessoais');
    await expect(page.locator('.juvenil-registration-card')).toContainText('Controle da inscrição');
    await expectNoDocumentHorizontalOverflow(page);

    const editableControlsInsideFicha = await page.locator(
        '.juvenil-registration-card input, .juvenil-registration-card textarea, .juvenil-registration-card select',
    ).count();

    expect(editableControlsInsideFicha).toBe(0);

    await page.screenshot({
        path: 'storage/app/screenshots/playwright-admin-campista-ficha-layout.png',
        fullPage: false,
    });

    await page.locator('.juvenil-registration-header-edit').click();
    await expect(page).toHaveURL(new RegExp(`/admin/campistas/${campistaId}/edit`));
    await page.waitForSelector('.fi-page .fi-section');
});

test('authenticated Filament user menu opens downward without orange trigger highlight', async ({ page }) => {
    await mkdir('storage/app/screenshots', { recursive: true });

    await signIn(page);

    const trigger = page.locator('.fi-user-menu .fi-dropdown-trigger').first();

    await trigger.dispatchEvent('mousedown', { button: 0 });
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

test('authenticated Filament user menu opens Breezy profile page', async ({ page }) => {
    await signIn(page);

    await page.locator('.fi-user-menu .fi-dropdown-trigger').first().dispatchEvent('mousedown', { button: 0 });
    await page.locator('.fi-dropdown-panel:visible').getByText('Meu perfil').click();

    await expect(page).toHaveURL(/\/admin\/meu-perfil/);
    await expect(page.locator('h1')).toContainText(/Meu perfil|Perfil/i);
    await expect(page.getByText('Sessões de Navegador').first()).toBeVisible();
    await expect(page.getByText('Chaves de acesso').first()).toBeVisible();
    await expect(page.locator('body')).not.toContainText('filament-breezy::');

    const profileLayout = await page.evaluate(() => {
        const profilePage = document.querySelector('.fi-page[wire\\:name="Jeffgreco13\\\\FilamentBreezy\\\\Pages\\\\MyProfilePage"]');
        const section = profilePage?.querySelector('.fi-section.fi-aside');
        const header = section?.querySelector('.fi-section-header');
        const content = section?.querySelector('.fi-section-content-ctn');
        const headerStyle = header ? getComputedStyle(header) : null;
        const contentStyle = content ? getComputedStyle(content) : null;

        return {
            headerBackground: headerStyle?.backgroundColor,
            contentBackground: contentStyle?.backgroundColor,
            contentRadius: contentStyle?.borderTopLeftRadius,
        };
    });

    expect(profileLayout.headerBackground).toBe('rgba(0, 0, 0, 0)');
    expect(profileLayout.contentBackground).toBe('rgba(3, 24, 28, 0.46)');
    expect(profileLayout.contentRadius).toBe('0px');
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
