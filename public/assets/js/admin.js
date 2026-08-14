/* Copy-letter button on the job edit page: copies the letter textarea and
   confirms inline. Hidden until we know the textarea has content. */
(function () {
  var btn = document.querySelector('.a-copy-letter');
  if (!btn) return;
  var area = document.getElementById(btn.getAttribute('data-copy-target'));
  if (!area) return;
  function sync() { btn.hidden = area.value.trim() === ''; }
  sync();
  area.addEventListener('input', sync);
  btn.addEventListener('click', function () {
    navigator.clipboard.writeText(area.value).then(function () {
      var old = btn.textContent;
      btn.textContent = 'Copied ✓';
      setTimeout(function () { btn.textContent = old; }, 1600);
    }, function () {
      area.focus();
      area.select(); /* clipboard API blocked: leave it selected for Cmd+C */
    });
  });
})();

/* Admin drawer — same pattern as the public site's mobile nav. */
(function () {
  var toggle = document.getElementById('a-nav-toggle');
  var overlay = document.getElementById('a-nav-overlay');
  if (!toggle) return;

  function setOpen(open) {
    document.body.classList.toggle('a-nav-open', open);
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
    if (overlay) overlay.hidden = !open;
  }

  toggle.addEventListener('click', function () {
    setOpen(!document.body.classList.contains('a-nav-open'));
  });
  if (overlay) overlay.addEventListener('click', function () { setOpen(false); });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') setOpen(false);
  });

  /* Kill the slide transition during resize so the drawer doesn't animate
     across the breakpoint (same fix as the public nav). */
  var t;
  window.addEventListener('resize', function () {
    document.body.classList.add('a-nav-resizing');
    clearTimeout(t);
    t = setTimeout(function () { document.body.classList.remove('a-nav-resizing'); }, 120);
  });
})();
