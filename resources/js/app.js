function initMobileMenu() {
    const toggle = document.querySelector('[data-mobile-menu-toggle]');
    const menu = document.querySelector('[data-mobile-menu]');

    if (!toggle || !menu) {
        return;
    }

    toggle.addEventListener('click', () => {
        const isOpen = menu.classList.toggle('hidden') === false;
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
}

function initBannerSliders() {
    document.querySelectorAll('[data-banner-slider]').forEach((slider) => {
        const track = slider.querySelector('[data-banner-track]');
        const slides = track ? Array.from(track.children) : [];

        if (!track || slides.length < 2) {
            return;
        }

        const dots = Array.from(slider.querySelectorAll('[data-banner-dot]'));
        const prevButton = slider.querySelector('[data-banner-prev]');
        const nextButton = slider.querySelector('[data-banner-next]');
        const intervalMs = Number(slider.dataset.bannerInterval || 5000);
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        let currentIndex = 0;
        let autoplayTimer = null;

        const goTo = (index) => {
            currentIndex = (index + slides.length) % slides.length;
            slides[currentIndex].scrollIntoView({ behavior: 'smooth', inline: 'start', block: 'nearest' });

            dots.forEach((dot, dotIndex) => {
                dot.setAttribute('aria-current', dotIndex === currentIndex ? 'true' : 'false');
            });
        };

        const startAutoplay = () => {
            if (prefersReducedMotion || autoplayTimer !== null) {
                return;
            }

            autoplayTimer = window.setInterval(() => goTo(currentIndex + 1), intervalMs);
        };

        const stopAutoplay = () => {
            if (autoplayTimer === null) {
                return;
            }

            window.clearInterval(autoplayTimer);
            autoplayTimer = null;
        };

        prevButton?.addEventListener('click', () => goTo(currentIndex - 1));
        nextButton?.addEventListener('click', () => goTo(currentIndex + 1));

        dots.forEach((dot, dotIndex) => {
            dot.addEventListener('click', () => goTo(dotIndex));
        });

        slider.addEventListener('mouseenter', stopAutoplay);
        slider.addEventListener('mouseleave', startAutoplay);
        slider.addEventListener('focusin', stopAutoplay);
        slider.addEventListener('focusout', startAutoplay);

        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                startAutoplay();
            } else {
                stopAutoplay();
            }
        });

        startAutoplay();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initMobileMenu();
    initBannerSliders();
});
