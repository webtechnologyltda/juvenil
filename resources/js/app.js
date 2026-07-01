import './bootstrap';
import './particles.js';
import './countdown.js';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { ScrollToPlugin } from 'gsap/ScrollToPlugin';

localStorage.theme = 'dark';

gsap.registerPlugin(ScrollTrigger, ScrollToPlugin);

const prefersReducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const isMobileViewport = () => window.matchMedia('(max-width: 767px)').matches;
const isTouchLayout = () => window.matchMedia('(max-width: 1023px)').matches;
let activeScrollTween = null;

const scrollToTarget = (target, duration = 1.05) => {
    const element = typeof target === 'string' ? document.querySelector(target) : target;

    if (!element) {
        return;
    }

    const previousHtmlScrollBehavior = document.documentElement.style.scrollBehavior;
    const previousBodyScrollBehavior = document.body.style.scrollBehavior;
    const hadSmoothClass = document.documentElement.classList.contains('scroll-smooth');
    const offsetY = isTouchLayout() ? 28 : 96;
    const targetY = Math.max(0, window.scrollY + element.getBoundingClientRect().top - offsetY);

    if (prefersReducedMotion() || isTouchLayout()) {
        if (activeScrollTween) {
            activeScrollTween.kill();
            activeScrollTween = null;
        }

        document.documentElement.classList.remove('scroll-smooth');
        document.documentElement.classList.remove('gsap-scrolling');
        document.documentElement.style.scrollBehavior = 'auto';
        document.body.style.scrollBehavior = 'auto';

        window.scrollTo({
            top: targetY,
            behavior: prefersReducedMotion() ? 'auto' : 'smooth',
        });

        window.setTimeout(() => {
            if (hadSmoothClass) {
                document.documentElement.classList.add('scroll-smooth');
            }

            document.documentElement.style.scrollBehavior = previousHtmlScrollBehavior;
            document.body.style.scrollBehavior = previousBodyScrollBehavior;
            ScrollTrigger.refresh();
        }, prefersReducedMotion() ? 0 : 560);

        return;
    }

    ScrollTrigger.refresh();

    document.documentElement.classList.remove('scroll-smooth');
    document.documentElement.classList.add('gsap-scrolling');
    document.documentElement.style.scrollBehavior = 'auto';
    document.body.style.scrollBehavior = 'auto';

    if (activeScrollTween) {
        activeScrollTween.kill();
    }

    const scrollState = { y: window.scrollY };

    activeScrollTween = gsap.to(scrollState, {
        y: targetY,
        duration,
        ease: 'power3.inOut',
        overwrite: true,
        onUpdate: () => window.scrollTo(0, scrollState.y),
        onComplete: () => {
            window.setTimeout(() => {
                const correction = element.getBoundingClientRect().top - offsetY;

                if (Math.abs(correction) > 2) {
                    window.scrollTo(0, window.scrollY + correction);
                }

                if (hadSmoothClass) {
                    document.documentElement.classList.add('scroll-smooth');
                }

                document.documentElement.style.scrollBehavior = previousHtmlScrollBehavior;
                document.body.style.scrollBehavior = previousBodyScrollBehavior;
                document.documentElement.classList.remove('gsap-scrolling');
                ScrollTrigger.refresh();
                activeScrollTween = null;
            }, 120);
        },
    });
};

document.addEventListener('livewire:init', () => {
    Livewire.on('inscricao-realizada', () => {
        scrollToTarget('#registration', 0.85);
    });
});

document.addEventListener('DOMContentLoaded', () => {
    initCampfireLoader(initPageMotion);
    initCountdown();
    initSmoothScroll();
});

function initPageMotion() {
    initMobileBottomNav();
    initPublicMotion();
}

function initCampfireLoader(onReady = () => {}) {
    const loader = document.querySelector('[data-campfire-loader]');

    if (!loader) {
        document.body.classList.remove('is-loading');
        window.requestAnimationFrame(onReady);

        return;
    }

    const progress = loader.querySelector('[data-loader-progress]');
    const flame = loader.querySelector('[data-loader-flame]');
    const reduced = prefersReducedMotion();

    document.body.classList.add('is-loading');

    if (!reduced) {
        gsap.fromTo(
            loader,
            { autoAlpha: 1 },
            { autoAlpha: 1, duration: 0.01 },
        );

        gsap.to(progress, {
            scaleX: 1,
            duration: 1.6,
            ease: 'power2.out',
        });

        gsap.to(flame, {
            y: -4,
            scale: 1.05,
            duration: 0.7,
            ease: 'sine.inOut',
            repeat: -1,
            yoyo: true,
        });

        gsap.from('.juvenil-page-loader__title, .juvenil-page-loader__text', {
            y: 12,
            opacity: 0,
            duration: 0.55,
            ease: 'power2.out',
            stagger: 0.08,
        });
    }

    const hideLoader = () => {
        const finish = () => {
            loader.setAttribute('aria-hidden', 'true');
            loader.classList.add('is-hidden');
            document.body.classList.remove('is-loading');

            window.requestAnimationFrame(() => {
                onReady();
                ScrollTrigger.refresh();
            });
        };

        if (reduced) {
            finish();

            return;
        }

        gsap.timeline({ onComplete: finish })
            .to(progress, {
                scaleX: 1,
                duration: 0.18,
                ease: 'power1.out',
            })
            .to(loader, {
                autoAlpha: 0,
                duration: 0.55,
                ease: 'power2.inOut',
            }, '+=0.12');
    };

    if (document.readyState === 'complete') {
        window.setTimeout(hideLoader, 450);
    } else {
        window.addEventListener('load', () => window.setTimeout(hideLoader, 450), { once: true });
    }
}

function initCountdown() {
    const registrationCountdowns = document.querySelectorAll('[data-registration-countdown]');

    registrationCountdowns.forEach((countdown) => {
        const target = Date.parse(countdown.dataset.target || '');

        if (Number.isNaN(target)) {
            return;
        }

        const parts = {
            days: countdown.querySelector('[data-countdown-days]'),
            hours: countdown.querySelector('[data-countdown-hours]'),
            minutes: countdown.querySelector('[data-countdown-minutes]'),
            seconds: countdown.querySelector('[data-countdown-seconds]'),
        };

        const render = () => {
            const distance = Math.max(0, target - Date.now());
            const seconds = Math.floor(distance / 1000);
            const days = Math.floor(seconds / 86400);
            const hours = Math.floor((seconds % 86400) / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            const remainingSeconds = seconds % 60;

            if (parts.days) {
                parts.days.textContent = String(days);
            }

            if (parts.hours) {
                parts.hours.textContent = String(hours).padStart(2, '0');
            }

            if (parts.minutes) {
                parts.minutes.textContent = String(minutes).padStart(2, '0');
            }

            if (parts.seconds) {
                parts.seconds.textContent = String(remainingSeconds).padStart(2, '0');
            }

            if (distance === 0 && countdown.dataset.finished !== 'true') {
                countdown.dataset.finished = 'true';
                window.setTimeout(() => window.location.reload(), 900);
            }
        };

        render();
        window.setInterval(render, 1000);
    });

    if (!window.jQuery || !document.getElementById('clockForm')) {
        return;
    }

    window.jQuery('#clockForm').countdown('2024/08/23 19:00', function (event) {
        window.jQuery(this).html(event.strftime('' +
            '<div style="margin: 0px; padding: 0px; width: 85px;" class="time-entry days"><span>%-D</span> Dia(s)</div> ' +
            '<div style="margin-top: 50px; padding: 0px; width: 85px;" class="time-entry hours"><span>%H</span> Hora(s)</div> ' +
            '<div style="margin-top: 50px; padding: 0px; width: 85px;" class="time-entry minutes"><span>%M</span> Minuto(s)</div> ' +
            '<div style="margin-top: 50px; padding: 0px; width: 85px;" class="time-entry seconds"><span>%S</span> Segundo(s)</div> '));
    });
}

function initSmoothScroll() {
    document.addEventListener('click', (event) => {
        const anchor = event.target.closest('[data-anchor-scroll][href^="#"]');

        if (!anchor) {
            return;
        }

        const hash = anchor.getAttribute('href');
        const target = hash ? document.querySelector(hash) : null;

        if (!target) {
            return;
        }

        event.preventDefault();

        if (anchor.matches('[data-mobile-nav-item]') && !prefersReducedMotion()) {
            gsap.fromTo(
                anchor,
                { scale: 0.94 },
                { scale: 1, duration: 0.28, ease: 'back.out(2)' },
            );
        }

        scrollToTarget(target);
        window.history.pushState(null, '', hash);
    }, true);
}

function initMobileBottomNav() {
    const nav = document.querySelector('[data-mobile-bottom-nav]');

    if (!nav) {
        return;
    }

    const items = gsap.utils.toArray('[data-mobile-nav-item]');

    if (!items.length) {
        return;
    }

    const setActive = (hash) => {
        items.forEach((item) => {
            const isActive = item.getAttribute('href') === hash;

            item.classList.toggle('is-active', isActive);
            item.setAttribute('aria-current', isActive ? 'page' : 'false');
        });
    };

    items.forEach((item) => {
        const hash = item.getAttribute('href');
        const target = hash ? document.querySelector(hash) : null;

        if (!target) {
            return;
        }

        ScrollTrigger.create({
            trigger: target,
            start: 'top 52%',
            end: 'bottom 52%',
            onEnter: () => setActive(hash),
            onEnterBack: () => setActive(hash),
        });
    });

    setActive(window.location.hash || '#top');
    window.addEventListener('hashchange', () => setActive(window.location.hash || '#top'));
}

function initPublicMotion() {
    if (prefersReducedMotion()) {
        return;
    }

    initTypographyMotion();
    initImageMotion();
    initScrubbedText();
    initPinnedSplit();
    initComponentMotion();
    initExperienceParallax();

    ScrollTrigger.refresh();
}

function initTypographyMotion() {
    const mobile = isMobileViewport();

    gsap.from('[data-motion-word]', {
        yPercent: mobile ? 24 : 38,
        rotate: mobile ? 0 : -2,
        opacity: 0,
        duration: mobile ? 0.72 : 0.95,
        ease: 'power4.out',
        stagger: mobile ? 0.06 : 0.09,
        delay: mobile ? 0.1 : 0.18,
    });

    gsap.utils.toArray('[data-motion-heading]').forEach((element) => {
        if (element.closest('.juvenil-hero-copy')) {
            return;
        }

        gsap.from(element, {
            y: mobile ? 18 : 34,
            opacity: 0,
            filter: mobile ? 'blur(3px)' : 'blur(8px)',
            duration: mobile ? 0.62 : 0.85,
            ease: 'power3.out',
            scrollTrigger: {
                trigger: element,
                start: mobile ? 'top 91%' : 'top 86%',
                once: true,
            },
        });
    });

    gsap.from('.juvenil-hero-copy > *:not(.juvenil-poster-title)', {
        y: mobile ? 16 : 28,
        opacity: 0,
        duration: mobile ? 0.65 : 0.9,
        ease: 'power3.out',
        stagger: mobile ? 0.08 : 0.11,
    });
}

function initImageMotion() {
    const mobile = isMobileViewport();

    gsap.utils.toArray('[data-gsap-image]').forEach((element) => {
        if (mobile) {
            gsap.fromTo(
                element,
                { opacity: 0.82, y: 24, scale: 0.98 },
                {
                    opacity: 1,
                    y: 0,
                    scale: 1,
                    duration: 0.68,
                    ease: 'power2.out',
                    scrollTrigger: {
                        trigger: element,
                        start: 'top 92%',
                        once: true,
                    },
                },
            );

            return;
        }

        gsap.fromTo(
            element,
            { opacity: 0.55, scale: 0.88, filter: 'brightness(0.72)' },
            {
                opacity: 1,
                scale: 1,
                filter: 'brightness(1)',
                ease: 'none',
                scrollTrigger: {
                    trigger: element,
                    start: 'top 92%',
                    end: 'bottom 18%',
                    scrub: true,
                },
            },
        );
    });
}

function initScrubbedText() {
    const mobile = isMobileViewport();

    gsap.utils.toArray('[data-scrub-reveal]').forEach((element) => {
        const text = element.textContent.trim();

        if (!text) {
            return;
        }

        element.setAttribute('aria-label', text);
        element.innerHTML = text
            .split(/\s+/)
            .map((word) => `<span aria-hidden="true" data-reveal-word>${word}</span>`)
            .join(' ');

        if (mobile) {
            gsap.set(element.querySelectorAll('[data-reveal-word]'), {
                opacity: 1,
                y: 0,
            });

            return;
        }

        gsap.to(element.querySelectorAll('[data-reveal-word]'), {
            opacity: 1,
            y: 0,
            stagger: 0.05,
            ease: 'none',
            scrollTrigger: {
                trigger: element,
                start: 'top 82%',
                end: 'bottom 42%',
                scrub: true,
            },
        });
    });
}

function initPinnedSplit() {
    gsap.utils.toArray('[data-pin-split]').forEach((section) => {
        const title = section.querySelector('[data-pin-title]');

        if (!title || window.innerWidth < 1024) {
            return;
        }

        ScrollTrigger.create({
            trigger: section,
            start: 'top 96px',
            end: 'bottom 55%',
            pin: title,
            pinSpacing: false,
            invalidateOnRefresh: true,
        });
    });
}

function initComponentMotion() {
    const mobile = isMobileViewport();

    gsap.utils.toArray('[data-motion-card]').forEach((element, index) => {
        gsap.from(element, {
            y: mobile ? 24 : 52,
            opacity: 0,
            rotateX: mobile ? 0 : index % 2 === 0 ? 4 : -4,
            duration: mobile ? 0.58 : 0.82,
            ease: 'power3.out',
            clearProps: 'transform',
            onComplete: () => {
                element.style.willChange = 'auto';
            },
            scrollTrigger: {
                trigger: element,
                start: mobile ? 'top 91%' : 'top 86%',
                once: true,
            },
        });
    });
}

function initExperienceParallax() {
    const section = document.querySelector('.juvenil-experience-section');

    if (!section) {
        return;
    }

    const mobile = isMobileViewport();
    const video = section.querySelector('.juvenil-experience-video');
    const copy = section.querySelector('.juvenil-experience-copy');

    if (mobile) {
        return;
    }

    if (video) {
        gsap.fromTo(video, {
            y: 38,
            scale: 0.965,
        }, {
            y: -24,
            scale: 1,
            ease: 'none',
            scrollTrigger: {
                trigger: section,
                start: 'top 88%',
                end: 'bottom 18%',
                scrub: true,
            },
        });
    }

    if (copy) {
        gsap.fromTo(copy, {
            y: 30,
        }, {
            y: -18,
            ease: 'none',
            scrollTrigger: {
                trigger: section,
                start: 'top 82%',
                end: 'bottom 24%',
                scrub: true,
            },
        });
    }
}
