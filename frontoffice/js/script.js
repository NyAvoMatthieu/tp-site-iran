/**
 * IranWatch – script.js
 * Lightweight progressive enhancements (no frameworks)
 */

'use strict';

/* ── Skip-to-content link ── */
(function injectSkipLink() {
  const skip = document.createElement('a');
  skip.href      = '#main-content';
  skip.className = 'skip-link';
  skip.textContent = 'Skip to main content';
  document.body.insertBefore(skip, document.body.firstChild);
})();

/* ── Highlight active nav link based on current path ── */
(function markActiveNav() {
  const links    = document.querySelectorAll('.nav-link');
  const path     = window.location.pathname;
  const search   = window.location.search;

  links.forEach(function (link) {
    const href = link.getAttribute('href') || '';

    if (href === '/' && path === '/' && !search) {
      link.classList.add('active');
      link.setAttribute('aria-current', 'page');
    } else if (href !== '/' && (path + search).startsWith(href)) {
      link.classList.add('active');
      link.setAttribute('aria-current', 'page');
    }
  });
})();

/* ── Lazy-load images observer ── */
(function lazyImages() {
  if (!('IntersectionObserver' in window)) return;

  const imgs = document.querySelectorAll('img[loading="lazy"]');
  const observer = new IntersectionObserver(function (entries, obs) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        const img = entry.target;
        if (img.dataset.src) {
          img.src = img.dataset.src;
          delete img.dataset.src;
        }
        obs.unobserve(img);
      }
    });
  }, { rootMargin: '200px' });

  imgs.forEach(function (img) { observer.observe(img); });
})();

/* ── Animate article cards on scroll ── */
(function animateCards() {
  if (!('IntersectionObserver' in window)) return;

  const cards = document.querySelectorAll('.article-card');

  // Set initial style
  cards.forEach(function (card) {
    card.style.opacity   = '0';
    card.style.transform = 'translateY(16px)';
    card.style.transition = 'opacity .4s ease, transform .4s ease';
  });

  const obs = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.style.opacity   = '1';
        entry.target.style.transform = 'translateY(0)';
        obs.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });

  cards.forEach(function (card) { obs.observe(card); });
})();

/* ── Smooth scroll for anchor links ── */
document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
  anchor.addEventListener('click', function (e) {
    const target = document.querySelector(this.getAttribute('href'));
    if (target) {
      e.preventDefault();
      target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });
});
