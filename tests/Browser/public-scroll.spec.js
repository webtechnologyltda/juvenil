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
    expect(desktopVideoBox.width).toBeGreaterThan(640);
    expect(desktopVideoBox.height).toBeGreaterThan(500);
    expect(desktopCopyBox.x).toBeGreaterThan(desktopVideoBox.x + desktopVideoBox.width - 12);
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
