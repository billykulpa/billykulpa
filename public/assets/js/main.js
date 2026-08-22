/* billykulpa.com — vanilla JS.
   Mobile header menu, ported from Restreak: the hamburger slides the nav in
   as an overlay drawer. Desktop shows the same nav inline, so there's one
   set of links and labels. */
'use strict';

(function () {
  const toggle  = document.getElementById('nav-toggle');
  const nav     = document.getElementById('site-nav');
  const overlay = document.getElementById('nav-overlay');
  if (!toggle || !nav || !overlay) return;

  const isOpen = () => nav.classList.contains('open');

  function open() {
    nav.classList.add('open');
    overlay.hidden = false;
    toggle.setAttribute('aria-expanded', 'true');
    toggle.setAttribute('aria-label', 'Close menu');
    document.body.style.overflow = 'hidden';   // lock the page behind the drawer
    // Move focus into the drawer (for keyboard/AT users) without lighting up
    // the first link: focusing the nav container itself carries no focus ring,
    // and the user's first Tab lands on the first link with a proper one.
    nav.focus();
  }

  function close(returnFocus = true) {
    nav.classList.remove('open');
    overlay.hidden = true;
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Open menu');
    document.body.style.overflow = '';
    if (returnFocus) toggle.focus();
  }

  toggle.addEventListener('click', () => (isOpen() ? close() : open()));
  overlay.addEventListener('click', () => close());
  // Tapping a link navigates away; close so it isn't left open on return.
  nav.querySelectorAll('a').forEach((a) => a.addEventListener('click', () => close(false)));

  document.addEventListener('keydown', (e) => {
    if (!isOpen()) return;
    if (e.key === 'Escape') { close(); return; }
    if (e.key !== 'Tab') return;
    // Trap focus within the open drawer.
    const items = nav.querySelectorAll('a, button');
    const firstItem = items[0];
    const lastItem  = items[items.length - 1];
    if (e.shiftKey && document.activeElement === firstItem) {
      e.preventDefault(); lastItem.focus();
    } else if (!e.shiftKey && document.activeElement === lastItem) {
      e.preventDefault(); firstItem.focus();
    }
  });

  // On resize: suppress the drawer's slide transition (crossing the breakpoint
  // would otherwise animate it), and if the viewport grows back to desktop
  // while open, reset to the inline nav. 960 matches the CSS breakpoint.
  let resizeSettle;
  window.addEventListener('resize', () => {
    nav.classList.add('resizing');
    clearTimeout(resizeSettle);
    resizeSettle = setTimeout(() => nav.classList.remove('resizing'), 150);
    if (window.innerWidth > 960 && isOpen()) close(false);
  });
})();

/* ============================ Audio player ============================= */
// Custom chrome for case-study audio (.bk-player): the browser's built-in
// controls are rounded and gray-blue; the site is thin rules and square
// corners. Progressive: markup ships a bare <audio controls>; when this
// runs, the native controls come off and the square ones take over.
(() => {
  const fmt = (s) => {
    if (!isFinite(s)) return '0:00';
    s = Math.round(s);
    return Math.floor(s / 60) + ':' + String(s % 60).padStart(2, '0');
  };
  document.querySelectorAll('.bk-player').forEach((box) => {
    const audio = box.querySelector('audio');
    if (!audio) return;
    audio.removeAttribute('controls');

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'bk-play';
    btn.setAttribute('aria-label', 'Play');
    btn.innerHTML = '<svg viewBox="0 0 16 16" aria-hidden="true">'
      + '<path class="bk-ico-play" d="M4 2 L13 8 L4 14 Z"/>'
      + '<g class="bk-ico-pause"><rect x="4" y="2.5" width="3" height="11"/><rect x="9.5" y="2.5" width="3" height="11"/></g>'
      + '</svg>';

    const range = document.createElement('input');
    range.type = 'range';
    range.className = 'bk-seek';
    range.min = 0; range.max = 1000; range.value = 0;
    range.setAttribute('aria-label', 'Seek');

    const time = document.createElement('span');
    time.className = 'bk-time mono-label';
    time.textContent = '0:00 / 0:00';

    box.append(btn, range, time);
    box.classList.add('is-ready');

    const draw = () => {
      const d = audio.duration || 0;
      time.textContent = fmt(audio.currentTime) + ' / ' + fmt(d);
      if (!seeking && d) range.value = Math.round(audio.currentTime / d * 1000);
      box.classList.toggle('is-playing', !audio.paused);
      btn.setAttribute('aria-label', audio.paused ? 'Play' : 'Pause');
    };

    let seeking = false;
    btn.addEventListener('click', () => { audio.paused ? audio.play() : audio.pause(); });
    range.addEventListener('input', () => {
      seeking = true;
      if (audio.duration) time.textContent = fmt(range.value / 1000 * audio.duration) + ' / ' + fmt(audio.duration);
    });
    range.addEventListener('change', () => {
      if (audio.duration) audio.currentTime = range.value / 1000 * audio.duration;
      seeking = false;
    });
    ['play', 'pause', 'ended', 'timeupdate', 'loadedmetadata', 'durationchange'].forEach((ev) =>
      audio.addEventListener(ev, draw));
    draw();
  });
})();
