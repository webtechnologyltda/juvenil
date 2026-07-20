export function initRegistrationCountdowns() {
    document.querySelectorAll('[data-registration-countdown]').forEach((countdown) => {
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
            const values = {
                days: Math.floor(seconds / 86400),
                hours: Math.floor((seconds % 86400) / 3600),
                minutes: Math.floor((seconds % 3600) / 60),
                seconds: seconds % 60,
            };

            Object.entries(parts).forEach(([name, element]) => {
                if (element) {
                    element.textContent = name === 'days'
                        ? String(values[name])
                        : String(values[name]).padStart(2, '0');
                }
            });

            if (distance === 0 && countdown.dataset.finished !== 'true') {
                countdown.dataset.finished = 'true';
                window.setTimeout(() => window.location.reload(), 900);
            }
        };

        render();
        window.setInterval(render, 1000);
    });
}
