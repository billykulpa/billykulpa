/* billykulpa.com — dependency-free image lightbox.
   Binds to case-study and archive figures. Click an image to view it
   full-size in an overlay; very tall images (full-page site captures)
   scroll vertically inside it. ESC, backdrop click, or the X closes. */
'use strict';

(function () {
  const targets = document.querySelectorAll(
    '.case-fig img, .prose--archive figure img, .gallery-track figure img'
  );
  if (!targets.length) return;

  // Build the overlay once, on demand.
  let overlay = null;
  let lastFocus = null;

  function ensureOverlay() {
    if (overlay) return overlay;
    overlay = document.createElement('div');
    overlay.className = 'lightbox';
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.setAttribute('aria-label', 'Image viewer');
    overlay.innerHTML =
      '<button type="button" class="lightbox-close" aria-label="Close image viewer">&times;</button>' +
      '<div class="lightbox-scroll"><img class="lightbox-img" alt=""></div>';
    document.body.appendChild(overlay);

    overlay.addEventListener('click', (e) => {
      // Close on backdrop or X — not on the image itself.
      if (e.target === overlay || e.target.classList.contains('lightbox-scroll')
          || e.target.classList.contains('lightbox-close')) close();
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && overlay.classList.contains('open')) close();
    });
    return overlay;
  }

  function open(src, alt) {
    lastFocus = document.activeElement;
    const el = ensureOverlay();
    const img = el.querySelector('.lightbox-img');
    img.src = src;
    img.alt = alt || '';
    el.querySelector('.lightbox-scroll').scrollTop = 0;
    el.classList.add('open');
    document.body.style.overflow = 'hidden';
    el.querySelector('.lightbox-close').focus();
  }

  function close() {
    overlay.classList.remove('open');
    document.body.style.overflow = '';
    if (lastFocus) lastFocus.focus();
  }

  targets.forEach((img) => {
    // If the image is wrapped in a link to a full-size version (the old
    // Spotlight pattern), use that; otherwise show the image itself.
    const link = img.closest('a');
    const full = link ? link.getAttribute('href') : img.currentSrc || img.src;
    const opener = (e) => { e.preventDefault(); open(full, img.alt); };
    if (link) {
      link.addEventListener('click', opener);
    } else {
      img.classList.add('lightbox-zoomable');
      img.setAttribute('tabindex', '0');
      img.setAttribute('role', 'button');
      img.addEventListener('click', opener);
      img.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') opener(e);
      });
    }
  });
})();
