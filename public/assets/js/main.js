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
