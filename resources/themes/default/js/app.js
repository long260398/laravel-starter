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

function initLightbox() {
    const triggers = Array.from(document.querySelectorAll('[data-lightbox]'));

    if (triggers.length === 0) {
        return;
    }

    let overlay = null;
    let imageEl = null;
    let captionEl = null;
    let closeButton = null;
    let prevButton = null;
    let nextButton = null;
    let currentGroup = [];
    let currentIndex = 0;
    let lastFocusedElement = null;

    const buildOverlay = () => {
        overlay = document.createElement('div');
        overlay.setAttribute('role', 'dialog');
        overlay.setAttribute('aria-modal', 'true');
        overlay.setAttribute('aria-label', 'Xem ảnh lớn');
        overlay.className = 'fixed inset-0 z-50 hidden items-center justify-center bg-black/80 p-4';
        overlay.innerHTML = `
            <button type="button" data-lightbox-close aria-label="Đóng" class="absolute right-4 top-4 rounded-full bg-black/40 p-2 text-white">&times;</button>
            <button type="button" data-lightbox-prev aria-label="Ảnh trước" class="absolute left-4 top-1/2 -translate-y-1/2 rounded-full bg-black/40 p-2 text-white">&lsaquo;</button>
            <figure class="max-h-full max-w-full">
                <img data-lightbox-image class="mx-auto max-h-[85vh] max-w-full object-contain" alt="">
                <figcaption data-lightbox-caption class="mt-2 text-center text-sm text-white"></figcaption>
            </figure>
            <button type="button" data-lightbox-next aria-label="Ảnh sau" class="absolute right-4 top-1/2 -translate-y-1/2 rounded-full bg-black/40 p-2 text-white">&rsaquo;</button>
        `;
        document.body.appendChild(overlay);

        imageEl = overlay.querySelector('[data-lightbox-image]');
        captionEl = overlay.querySelector('[data-lightbox-caption]');
        closeButton = overlay.querySelector('[data-lightbox-close]');
        prevButton = overlay.querySelector('[data-lightbox-prev]');
        nextButton = overlay.querySelector('[data-lightbox-next]');

        closeButton.addEventListener('click', close);
        prevButton.addEventListener('click', () => show(currentIndex - 1));
        nextButton.addEventListener('click', () => show(currentIndex + 1));
        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                close();
            }
        });
    };

    const show = (index) => {
        currentIndex = (index + currentGroup.length) % currentGroup.length;

        const trigger = currentGroup[currentIndex];
        const caption = trigger.dataset.lightboxCaption || '';

        imageEl.src = trigger.getAttribute('href');
        imageEl.alt = caption;
        captionEl.textContent = caption;

        const hasMultiple = currentGroup.length > 1;
        prevButton.classList.toggle('hidden', !hasMultiple);
        nextButton.classList.toggle('hidden', !hasMultiple);
    };

    function onKeydown(event) {
        if (event.key === 'Escape') {
            close();

            return;
        }

        if (event.key === 'ArrowLeft') {
            show(currentIndex - 1);

            return;
        }

        if (event.key === 'ArrowRight') {
            show(currentIndex + 1);

            return;
        }

        if (event.key !== 'Tab') {
            return;
        }

        const focusable = [closeButton, prevButton, nextButton].filter((el) => !el.classList.contains('hidden'));
        const first = focusable[0];
        const last = focusable[focusable.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    }

    function close() {
        overlay.classList.add('hidden');
        overlay.classList.remove('flex');
        document.removeEventListener('keydown', onKeydown);
        lastFocusedElement?.focus();
    }

    const open = (trigger) => {
        if (!overlay) {
            buildOverlay();
        }

        const groupName = trigger.dataset.lightbox;
        currentGroup = triggers.filter((item) => item.dataset.lightbox === groupName);
        show(currentGroup.indexOf(trigger));

        lastFocusedElement = document.activeElement;
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
        document.addEventListener('keydown', onKeydown);
        closeButton.focus();
    };

    triggers.forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();
            open(trigger);
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initMobileMenu();
    initBannerSliders();
    initLightbox();
});
