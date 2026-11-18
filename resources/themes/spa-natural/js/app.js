import '../../default/js/app.js';

function initReveal() {
    const targets = Array.from(document.querySelectorAll('[data-reveal]'));

    if (targets.length === 0) {
        return;
    }

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        targets.forEach((target) => target.classList.add('is-visible'));

        return;
    }

    const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) {
                return;
            }

            entry.target.classList.add('is-visible');
            obs.unobserve(entry.target);
        });
    }, { threshold: 0.15 });

    targets.forEach((target) => observer.observe(target));
}

document.addEventListener('DOMContentLoaded', initReveal);
