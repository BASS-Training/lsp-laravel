document.querySelectorAll('[data-carousel]').forEach((carousel) => {
    const track = carousel.querySelector('[data-carousel-track]');
    const previousButton = carousel.querySelector('[data-carousel-prev]');
    const nextButton = carousel.querySelector('[data-carousel-next]');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    let pointerIsOver = false;
    let pauseUntil = 0;

    const updateButtons = () => {
        previousButton.disabled = track.scrollLeft <= 1;
        nextButton.disabled = track.scrollLeft >= track.scrollWidth - track.clientWidth - 1;
    };

    const move = (direction) => {
        const firstItem = track.firstElementChild;

        if (!firstItem) {
            return;
        }

        const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
        const distance = firstItem.getBoundingClientRect().width + gap;

        track.scrollBy({
            left: direction * distance,
            behavior: reducedMotion.matches ? 'auto' : 'smooth',
        });
    };

    const advanceAutomatically = () => {
        if (reducedMotion.matches || document.hidden || pointerIsOver || carousel.contains(document.activeElement) || Date.now() < pauseUntil || track.scrollWidth <= track.clientWidth) {
            return;
        }

        if (track.scrollLeft >= track.scrollWidth - track.clientWidth - 1) {
            track.scrollTo({ left: 0, behavior: 'auto' });
        } else {
            move(1);
        }
    };

    previousButton.addEventListener('click', () => move(-1));
    nextButton.addEventListener('click', () => move(1));
    carousel.addEventListener('pointerenter', (event) => {
        if (event.pointerType === 'mouse') {
            pointerIsOver = true;
        }
    });
    carousel.addEventListener('pointerleave', () => { pointerIsOver = false; });
    carousel.addEventListener('pointerdown', () => { pauseUntil = Date.now() + 5000; });
    track.addEventListener('scroll', updateButtons, { passive: true });
    window.addEventListener('resize', updateButtons);
    window.setInterval(advanceAutomatically, 3500);
    updateButtons();
});
