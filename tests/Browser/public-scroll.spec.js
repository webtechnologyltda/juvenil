import { expect, test } from '@playwright/test';
import { mkdir } from 'node:fs/promises';

test.use({
    channel: 'chrome',
    viewport: { width: 1440, height: 900 },
});

test('desktop uses a single document scrollbar and GSAP anchor scrolling reaches sections', async ({ page }) => {
    await page.goto('http://juvenil.test');
    await page.waitForSelector('.juvenil-hero-shell');
    await page.waitForTimeout(2500);

    const initialScrollState = await page.evaluate(() => {
        const verticalScrollers = [...document.querySelectorAll('*')]
            .filter((element) => {
                const style = getComputedStyle(element);
                return ['auto', 'scroll'].includes(style.overflowY)
                    && element.scrollHeight > element.clientHeight + 1;
            })
            .map((element) => ({
                tag: element.tagName.toLowerCase(),
                id: element.id,
                className: element.className,
                scrollHeight: element.scrollHeight,
                clientHeight: element.clientHeight,
                overflowY: getComputedStyle(element).overflowY,
            }));

        return {
            documentScrollable: document.documentElement.scrollHeight > window.innerHeight,
            bodyScrollable: document.body.scrollHeight > window.innerHeight,
            windowScrollY: window.scrollY,
            verticalScrollers,
        };
    });

    expect(initialScrollState.documentScrollable).toBe(true);
    expect(initialScrollState.verticalScrollers).toEqual([]);

    await page.click('header a[href="#registration"]');
    await page.waitForTimeout(1500);

    const afterRegistrationClick = await page.evaluate(() => {
        const registration = document.querySelector('#registration');
        const rect = registration.getBoundingClientRect();

        return {
            scrollY: window.scrollY,
            registrationTop: rect.top,
            htmlHasGsapScrolling: document.documentElement.classList.contains('gsap-scrolling'),
            htmlScrollBehavior: getComputedStyle(document.documentElement).scrollBehavior,
            bodyScrollBehavior: getComputedStyle(document.body).scrollBehavior,
        };
    });

    expect(afterRegistrationClick.scrollY).toBeGreaterThan(500);
    expect(Math.abs(afterRegistrationClick.registrationTop - 96)).toBeLessThan(140);
    expect(afterRegistrationClick.htmlHasGsapScrolling).toBe(false);
    expect(afterRegistrationClick.htmlScrollBehavior).toBe('auto');
    expect(afterRegistrationClick.bodyScrollBehavior).toBe('auto');

    await page.click('header a[href="#contact"]');
    await page.waitForTimeout(1500);

    const afterContactClick = await page.evaluate(() => {
        const contact = document.querySelector('#contact');
        const rect = contact.getBoundingClientRect();

        return {
            scrollY: window.scrollY,
            contactTop: rect.top,
            htmlHasGsapScrolling: document.documentElement.classList.contains('gsap-scrolling'),
        };
    });

    expect(afterContactClick.scrollY).toBeGreaterThan(afterRegistrationClick.scrollY);
    expect(Math.abs(afterContactClick.contactTop - 96)).toBeLessThan(160);
    expect(afterContactClick.htmlHasGsapScrolling).toBe(false);
});

test('mobile uses stable native anchor scrolling without GSAP layout overlap', async ({ browser }) => {
    await mkdir('storage/app/screenshots', { recursive: true });

    const mobile = await browser.newPage({ viewport: { width: 390, height: 920 }, isMobile: true });
    await mobile.goto('http://juvenil.test');
    await mobile.waitForSelector('.juvenil-hero-shell');
    await mobile.waitForTimeout(3000);

    const initialMobileState = await mobile.evaluate(() => {
        const heroDate = document.querySelector('.juvenil-poster-title');
        const heroDateRect = heroDate.getBoundingClientRect();
        const cookieDialog = document.querySelector('.js-cookie-consent')?.getBoundingClientRect();
        const bottomNav = document.querySelector('[data-mobile-bottom-nav]')?.getBoundingClientRect();

        return {
            activeMobileNav: document.querySelector('[data-mobile-nav-item].is-active')?.getAttribute('href'),
            cookieClearsBottomNav: cookieDialog && bottomNav
                ? cookieDialog.bottom <= bottomNav.top + 1
                : true,
            heroDate: {
                centerDelta: Math.abs((heroDateRect.left + heroDateRect.width / 2) - window.innerWidth / 2),
                textAlign: getComputedStyle(heroDate).textAlign,
            },
            heroMotion: [...document.querySelectorAll('.juvenil-hero-copy > *:not(.sr-only)')].map((element) => ({
                opacity: Number.parseFloat(getComputedStyle(element).opacity),
                transform: getComputedStyle(element).transform,
            })),
            htmlHasGsapScrolling: document.documentElement.classList.contains('gsap-scrolling'),
        };
    });

    expect(initialMobileState.activeMobileNav).toBe('#top');
    expect(initialMobileState.cookieClearsBottomNav).toBe(true);
    expect(initialMobileState.heroDate.textAlign).toBe('center');
    expect(initialMobileState.heroDate.centerDelta).toBeLessThan(4);
    expect(initialMobileState.htmlHasGsapScrolling).toBe(false);
    expect(initialMobileState.heroMotion.every((item) => item.opacity > 0.98)).toBe(true);

    await mobile.screenshot({
        path: 'storage/app/screenshots/playwright-mobile-hero-date-centered.png',
        fullPage: false,
    });

    await mobile.click('[data-mobile-bottom-nav] a[href="#juvenil-details"]');
    await mobile.waitForTimeout(1200);

    const afterDetailsClick = await mobile.evaluate(() => {
        const heroCopy = document.querySelector('.juvenil-hero-copy').getBoundingClientRect();
        const section = document.querySelector('#juvenil-details').getBoundingClientRect();
        const video = document.querySelector('.juvenil-experience-video').getBoundingClientRect();
        const copy = document.querySelector('.juvenil-experience-copy').getBoundingClientRect();

        return {
            activeMobileNav: document.querySelector('[data-mobile-nav-item].is-active')?.getAttribute('href'),
            htmlHasGsapScrolling: document.documentElement.classList.contains('gsap-scrolling'),
            sectionTop: section.top,
            heroCopyBottom: heroCopy.bottom,
            copyBelowVideo: copy.top > video.bottom + 20,
            visibleMotionOpacity: [...document.querySelectorAll('#juvenil-details [data-motion-heading], #juvenil-details [data-motion-card], #juvenil-details [data-gsap-image], #juvenil-details [data-reveal-word]')]
                .filter((element) => {
                    const rect = element.getBoundingClientRect();

                    return rect.bottom > 0 && rect.top < window.innerHeight;
                })
                .map((element) => Number.parseFloat(getComputedStyle(element).opacity)),
        };
    });

    expect(afterDetailsClick.activeMobileNav).toBe('#juvenil-details');
    expect(afterDetailsClick.htmlHasGsapScrolling).toBe(false);
    expect(Math.abs(afterDetailsClick.sectionTop - 28)).toBeLessThan(90);
    expect(afterDetailsClick.heroCopyBottom).toBeLessThan(0);
    expect(afterDetailsClick.copyBelowVideo).toBe(true);
    expect(afterDetailsClick.visibleMotionOpacity.every((opacity) => opacity > 0.94)).toBe(true);

    await mobile.click('[data-mobile-bottom-nav] a[href="#registration"]');
    await mobile.waitForTimeout(1200);

    const registrationActiveState = await mobile.evaluate(() => {
        const registrationItem = document.querySelector('[data-mobile-bottom-nav] a[href="#registration"]');
        const itemStyle = getComputedStyle(registrationItem);

        return {
            activeMobileNav: document.querySelector('[data-mobile-nav-item].is-active')?.getAttribute('href'),
            className: registrationItem.className,
            color: itemStyle.color,
            background: itemStyle.backgroundColor,
            border: itemStyle.borderColor,
        };
    });

    expect(registrationActiveState.activeMobileNav).toBe('#registration');
    expect(registrationActiveState.className).toContain('is-active');
    expect(registrationActiveState.color).toBe('rgb(255, 255, 255)');
    expect(registrationActiveState.background).toBe('rgba(7, 61, 69, 0.84)');
    expect(registrationActiveState.border).toBe('rgba(157, 219, 239, 0.22)');

    await mobile.screenshot({
        path: 'storage/app/screenshots/playwright-mobile-gsap-scroll-fixed.png',
        fullPage: false,
    });

    await mobile.close();
});

test('experience section has distinct desktop and mobile video layouts', async ({ browser }) => {
    await mkdir('storage/app/screenshots', { recursive: true });

    const desktop = await browser.newPage({ viewport: { width: 1440, height: 980 } });
    await desktop.goto('http://juvenil.test');
    await desktop.waitForSelector('.juvenil-experience-section');
    await desktop.waitForTimeout(2500);
    await desktop.locator('#juvenil-details').scrollIntoViewIfNeeded();
    await desktop.waitForTimeout(900);

    const desktopVideo = desktop.locator('.juvenil-experience-video');
    const desktopCopy = desktop.locator('.juvenil-experience-copy');
    await expect(desktop.locator('.juvenil-site-video')).toHaveAttribute('autoplay', '');
    await expect(desktop.locator('.juvenil-site-video')).toHaveAttribute('muted', '');
    await expect(desktop.locator('.juvenil-site-video')).toHaveAttribute('loop', '');
    await expect(desktop.locator('.juvenil-site-video')).toHaveAttribute('playsinline', '');
    await expect(desktop.locator('.juvenil-site-video')).not.toHaveAttribute('controls', '');

    const desktopVideoBox = await desktopVideo.boundingBox();
    const desktopCopyBox = await desktopCopy.boundingBox();
    const desktopCtaBox = await desktop.locator('.juvenil-experience-video a[href="#registration"]').boundingBox();
    expect(desktopVideoBox.width).toBeGreaterThan(640);
    expect(desktopVideoBox.height).toBeGreaterThan(500);
    expect(desktopCopyBox.x).toBeGreaterThan(desktopVideoBox.x + desktopVideoBox.width - 12);
    expect(desktopVideoBox.x + desktopVideoBox.width - (desktopCtaBox.x + desktopCtaBox.width)).toBeGreaterThan(60);
    await desktop.locator('#juvenil-details').screenshot({
        path: 'storage/app/screenshots/playwright-experience-section-desktop.png',
    });
    await desktop.close();

    const mobile = await browser.newPage({ viewport: { width: 390, height: 920 }, isMobile: true });
    await mobile.goto('http://juvenil.test');
    await mobile.waitForSelector('.juvenil-experience-section');
    await mobile.waitForTimeout(2500);
    await mobile.locator('#juvenil-details').scrollIntoViewIfNeeded();
    await mobile.waitForTimeout(900);

    const mobileVideoBox = await mobile.locator('.juvenil-experience-video').boundingBox();
    const mobileCopyBox = await mobile.locator('.juvenil-experience-copy').boundingBox();
    expect(mobileVideoBox.width).toBeGreaterThan(330);
    expect(mobileVideoBox.y).toBeLessThan(mobileCopyBox.y);
    await mobile.locator('#juvenil-details').screenshot({
        path: 'storage/app/screenshots/playwright-experience-section-mobile.png',
    });
    await mobile.close();
});

test('footer uses the correct Juvenil logo and stays responsive', async ({ browser }) => {
    await mkdir('storage/app/screenshots', { recursive: true });

    const desktop = await browser.newPage({ viewport: { width: 1440, height: 920 } });
    await desktop.goto('http://juvenil.test');
    await desktop.waitForSelector('.juvenil-footer');
    await desktop.locator('.juvenil-footer').scrollIntoViewIfNeeded();
    await desktop.waitForTimeout(900);

    await expect(desktop.locator('.juvenil-footer img[alt="Logo do Acampamento Juvenil"]')).toHaveAttribute('src', /\/img\/logo\.png$/);

    const desktopFooterState = await desktop.evaluate(() => {
        const footer = document.querySelector('.juvenil-footer').getBoundingClientRect();
        const logo = document.querySelector('.juvenil-footer img[alt="Logo do Acampamento Juvenil"]').getBoundingClientRect();

        return {
            logoWidth: logo.width,
            footerInsideViewport: footer.left >= 0 && footer.right <= window.innerWidth,
            horizontalOverflow: document.documentElement.scrollWidth > window.innerWidth + 1,
        };
    });

    expect(desktopFooterState.logoWidth).toBeGreaterThan(120);
    expect(desktopFooterState.footerInsideViewport).toBe(true);
    expect(desktopFooterState.horizontalOverflow).toBe(false);

    await desktop.locator('.juvenil-footer').screenshot({
        path: 'storage/app/screenshots/playwright-footer-desktop.png',
    });
    await desktop.close();

    const mobile = await browser.newPage({ viewport: { width: 390, height: 920 }, isMobile: true });
    await mobile.goto('http://juvenil.test');
    await mobile.waitForSelector('.juvenil-footer');
    await mobile.locator('.juvenil-footer').scrollIntoViewIfNeeded();
    await mobile.waitForTimeout(900);

    const mobileFooterState = await mobile.evaluate(() => {
        const footer = document.querySelector('.juvenil-footer');
        const footerStyle = getComputedStyle(footer);
        const logo = document.querySelector('.juvenil-footer img[alt="Logo do Acampamento Juvenil"]').getBoundingClientRect();

        return {
            logoWidth: logo.width,
            footerBottomPadding: Number.parseFloat(footerStyle.paddingBottom),
            horizontalOverflow: document.documentElement.scrollWidth > window.innerWidth + 1,
        };
    });

    expect(mobileFooterState.logoWidth).toBeGreaterThan(100);
    expect(mobileFooterState.footerBottomPadding).toBeGreaterThanOrEqual(90);
    expect(mobileFooterState.horizontalOverflow).toBe(false);

    await mobile.screenshot({
        path: 'storage/app/screenshots/playwright-footer-mobile.png',
        fullPage: false,
    });
    await mobile.close();
});

test('public Filament form uses the orange primary theme', async ({ page }) => {
    await mkdir('storage/app/screenshots', { recursive: true });

    await page.goto('http://juvenil.test');
    await page.waitForSelector('.filament-registration-shell');
    await page.waitForTimeout(2500);
    await page.locator('#registration').scrollIntoViewIfNeeded();
    await page.waitForTimeout(900);

    const colors = await page.evaluate(() => {
        const shell = document.querySelector('.filament-registration-shell');
        const submitButton = document.querySelector('button[type="submit"]');
        const nativeChoice = shell.querySelector('input[type="checkbox"], input[type="radio"]');

        return {
            primary500: getComputedStyle(shell).getPropertyValue('--primary-500').trim(),
            submitBackground: getComputedStyle(submitButton).backgroundColor,
            nativeChoiceColor: getComputedStyle(nativeChoice).color,
            toggleButtonCount: shell.querySelectorAll('.fi-fo-toggle-buttons').length,
            radioGroupCount: shell.querySelectorAll('.fi-fo-radio').length,
            fileAccept: shell.querySelector('input[type="file"]')?.getAttribute('accept'),
        };
    });

    expect(colors.primary500).toBe('oklch(0.68270588235294 0.17009090909091 45.756)');
    expect(colors.submitBackground).toBe('rgb(244, 107, 18)');
    expect(colors.nativeChoiceColor).toBe('rgb(244, 107, 18)');
    expect(colors.toggleButtonCount).toBeGreaterThan(0);
    expect(colors.radioGroupCount).toBe(0);
    expect(colors.fileAccept).toBe('image/jpeg,image/png,image/webp');

    await page.getByText('Feminino', { exact: true }).click();
    await page.waitForFunction(() => {
        const label = [...document.querySelectorAll('.fi-fo-toggle-buttons label')]
            .find((element) => element.textContent.trim() === 'Feminino');

        return label?.previousElementSibling?.checked === true;
    });
    await page.waitForTimeout(200);

    const femaleSexState = await page.evaluate(() => {
        const label = [...document.querySelectorAll('.fi-fo-toggle-buttons label')]
            .find((element) => element.textContent.trim() === 'Feminino');
        const style = getComputedStyle(label);

        return {
            background: style.backgroundColor,
            color500: style.getPropertyValue('--color-500').trim(),
        };
    });

    await page.getByText('Masculino', { exact: true }).click();
    await page.waitForFunction(() => {
        const label = [...document.querySelectorAll('.fi-fo-toggle-buttons label')]
            .find((element) => element.textContent.trim() === 'Masculino');

        return label?.previousElementSibling?.checked === true;
    });
    await page.waitForTimeout(200);

    const maleSexState = await page.evaluate(() => {
        const label = [...document.querySelectorAll('.fi-fo-toggle-buttons label')]
            .find((element) => element.textContent.trim() === 'Masculino');
        const style = getComputedStyle(label);

        return {
            background: style.backgroundColor,
            color500: style.getPropertyValue('--color-500').trim(),
        };
    });

    expect(femaleSexState.background).toBe('oklch(0.656 0.241 354.308)');
    expect(femaleSexState.color500).toBe('oklch(0.656 0.241 354.308)');
    expect(maleSexState.background).toBe('oklch(0.623 0.214 259.815)');
    expect(maleSexState.color500).toBe('oklch(0.623 0.214 259.815)');

    await page.locator('.filament-registration-shell').screenshot({
        path: 'storage/app/screenshots/playwright-filament-form-primary.png',
    });
});

test('public Filament upload editor stays fixed to the viewport and crops square images', async ({ page }) => {
    await mkdir('storage/app/screenshots', { recursive: true });

    await page.goto('http://juvenil.test');
    await page.waitForSelector('.filament-registration-shell');
    await page.locator('#registration').scrollIntoViewIfNeeded();
    await page.waitForTimeout(2500);

    await page.locator('.filament-registration-shell input[type="file"]').first().setInputFiles('/home/lucas/code/juvenil/public/img/hero-desktop.png');
    await page.waitForSelector('.fi-fo-file-upload-editor', { state: 'visible' });
    await page.waitForTimeout(1200);

    const editorState = await page.evaluate(() => {
        const editor = document.querySelector('.fi-fo-file-upload-editor').getBoundingClientRect();
        const windowBox = document.querySelector('.fi-fo-file-upload-editor-window').getBoundingClientRect();
        const imageBox = document.querySelector('.fi-fo-file-upload-editor-image-ctn').getBoundingClientRect();
        const cropBox = document.querySelector('.cropper-crop-box').getBoundingClientRect();
        const cropperImage = document.querySelector('.cropper-container img');
        const cropperImageStyle = getComputedStyle(cropperImage);
        const cookieDialog = document.querySelector('.js-cookie-consent');

        return {
            viewport: {
                width: window.innerWidth,
                height: window.innerHeight,
            },
            editor: {
                left: editor.left,
                top: editor.top,
                right: editor.right,
                bottom: editor.bottom,
                width: editor.width,
                height: editor.height,
            },
            windowInsideViewport: windowBox.left >= 0
                && windowBox.top >= 0
                && windowBox.right <= window.innerWidth
                && windowBox.bottom <= window.innerHeight,
            cropBoxInsideImage: cropBox.left >= imageBox.left - 1
                && cropBox.top >= imageBox.top - 1
                && cropBox.right <= imageBox.right + 1
                && cropBox.bottom <= imageBox.bottom + 1,
            cookieVisible: cookieDialog ? getComputedStyle(cookieDialog).display !== 'none' : false,
            cropAspectDelta: Math.abs((cropBox.width / cropBox.height) - 1),
            cropperImageMaxWidth: cropperImageStyle.maxWidth,
        };
    });

    expect(Math.abs(editorState.editor.left)).toBeLessThan(2);
    expect(Math.abs(editorState.editor.top)).toBeLessThan(2);
    expect(Math.abs(editorState.editor.right - editorState.viewport.width)).toBeLessThan(2);
    expect(Math.abs(editorState.editor.bottom - editorState.viewport.height)).toBeLessThan(2);
    expect(editorState.windowInsideViewport).toBe(true);
    expect(editorState.cropBoxInsideImage).toBe(true);
    expect(editorState.cookieVisible).toBe(false);
    expect(editorState.cropAspectDelta).toBeLessThan(0.01);
    expect(editorState.cropperImageMaxWidth).toBe('none');

    await page.screenshot({
        path: 'storage/app/screenshots/playwright-filament-upload-editor.png',
        fullPage: false,
    });
});

test('admin login, legal pages, and public errors use the Juvenil theme', async ({ browser }) => {
    await mkdir('storage/app/screenshots', { recursive: true });

    const desktopContext = await browser.newContext({ viewport: { width: 1440, height: 920 } });
    const desktop = await desktopContext.newPage();

    await desktop.goto('http://juvenil.test/admin/login');
    await desktop.waitForSelector('.juvenil-admin-login-card');
    await expect(desktop.locator('.juvenil-admin-login')).toBeVisible();
    await expect(desktop.locator('.juvenil-admin-ops-panel')).toBeVisible();
    await expect(desktop.locator('.juvenil-admin-login-card')).toContainText('Entrar no painel');
    await expect(desktop.locator('.juvenil-admin-ops-panel')).toContainText('Operação do evento');
    await expect(desktop.locator('.juvenil-admin-ops-panel')).toContainText('Inscrições organizadas');
    await expect(desktop.locator('.juvenil-admin-ops-panel')).toContainText('22-26');
    await expect(desktop.locator('.js-cookie-consent')).toContainText('Cookies do Juvenil');
    await expect(desktop.locator('text=Faça login')).toHaveCount(0);

    const adminLoginLayout = await desktop.evaluate(() => {
        const loginCard = document.querySelector('.juvenil-admin-login-card').getBoundingClientRect();
        const opsPanel = document.querySelector('.juvenil-admin-ops-panel').getBoundingClientRect();
        const cookieDialog = document.querySelector('.js-cookie-consent').getBoundingClientRect();
        const overlaps = !(
            cookieDialog.right <= loginCard.left ||
            cookieDialog.left >= loginCard.right ||
            cookieDialog.bottom <= loginCard.top ||
            cookieDialog.top >= loginCard.bottom
        );
        const overlapsOpsPanel = !(
            cookieDialog.right <= opsPanel.left ||
            cookieDialog.left >= opsPanel.right ||
            cookieDialog.bottom <= opsPanel.top ||
            cookieDialog.top >= opsPanel.bottom
        );

        return {
            cookiePosition: getComputedStyle(document.querySelector('.js-cookie-consent')).position,
            cookieInViewport: cookieDialog.bottom <= window.innerHeight && cookieDialog.top >= 0,
            cookieOverlapsLoginCard: overlaps,
            cookieOverlapsOpsPanel: overlapsOpsPanel,
        };
    });

    expect(adminLoginLayout.cookiePosition).toBe('fixed');
    expect(adminLoginLayout.cookieInViewport).toBe(true);
    expect(adminLoginLayout.cookieOverlapsLoginCard).toBe(false);
    expect(adminLoginLayout.cookieOverlapsOpsPanel).toBe(false);

    await desktop.screenshot({
        path: 'storage/app/screenshots/playwright-admin-login-themed.png',
        fullPage: false,
    });

    await desktop.goto('http://juvenil.test/termos-inscricao');
    await desktop.waitForSelector('.juvenil-legal-content');
    await expect(desktop.locator('.juvenil-legal-page')).toBeVisible();
    await expect(desktop.locator('h1')).toContainText('Termos de Inscrição');
    await expect(desktop.locator('.js-cookie-consent')).toContainText('Cookies do Juvenil');
    await desktop.screenshot({
        path: 'storage/app/screenshots/playwright-terms-themed.png',
        fullPage: false,
    });

    const response = await desktop.goto('http://juvenil.test/pagina-inexistente-playwright');
    expect(response.status()).toBe(404);
    await desktop.waitForSelector('text=Página fora da trilha');
    await expect(desktop.locator('.juvenil-poster-title')).toContainText('404');
    await desktop.screenshot({
        path: 'storage/app/screenshots/playwright-error-404-themed.png',
        fullPage: false,
    });

    await desktopContext.close();

    const mobileContext = await browser.newContext({ viewport: { width: 390, height: 920 }, isMobile: true });
    const mobile = await mobileContext.newPage();
    await mobile.goto('http://juvenil.test/politica-privacidade');
    await mobile.waitForSelector('.juvenil-legal-content');
    await expect(mobile.locator('h1')).toContainText('Política de Privacidade');
    await expect(mobile.locator('.js-cookie-consent')).toBeVisible();
    await mobile.screenshot({
        path: 'storage/app/screenshots/playwright-privacy-mobile-themed.png',
        fullPage: false,
    });
    await mobileContext.close();
});
