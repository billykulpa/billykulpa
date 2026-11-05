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

/* Generic copy buttons: <button data-copy="#selector">. Used for the
   tagged application link on the job editor. */
document.querySelectorAll('[data-copy]').forEach(function (b) {
  b.addEventListener('click', function () {
    var el = document.querySelector(b.getAttribute('data-copy'));
    if (!el) return;
    var text = el.value !== undefined && el.tagName !== 'CODE' ? el.value : el.textContent;
    var done = function () { var t = b.textContent; b.textContent = 'Copied'; setTimeout(function () { b.textContent = t; }, 1400); };
    if (navigator.clipboard) navigator.clipboard.writeText(text).then(done, function () {});
  });
});

/* MFL rankings editor. Reordering moves the list items; the hidden order[]
   inputs travel with them, so the form posts the ranking in DOM order. */
(function () {
  var list = document.getElementById('a-rank-list');
  if (!list) return;
  var form = document.getElementById('a-ranking-form');
  var status = document.getElementById('a-rank-status');
  var compact = document.getElementById('a-rank-compact');
  var dirty = false;

  function items() { return Array.prototype.slice.call(list.children); }
  function teamName(li) { return li.querySelector('.a-rank-team').textContent; }

  function renumber() {
    var all = items();
    all.forEach(function (li, i) {
      li.querySelector('.a-rank-no').textContent = i + 1;
      li.querySelector('[data-dir="-1"]').disabled = i === 0;
      li.querySelector('[data-dir="1"]').disabled = i === all.length - 1;
    });
  }

  function moved(li) {
    dirty = true;
    renumber();
    status.textContent = teamName(li) + ' is now number ' + (items().indexOf(li) + 1);
  }

  list.addEventListener('click', function (e) {
    var btn = e.target.closest('.a-rank-move');
    if (!btn) return;
    var li = btn.closest('.a-rank-item');
    if (btn.getAttribute('data-dir') === '-1') list.insertBefore(li, li.previousElementSibling);
    else list.insertBefore(li.nextElementSibling, li);
    moved(li);
    (btn.disabled ? li.querySelector('.a-rank-move:not(:disabled)') : btn).focus();
  });

  /* Pointer events rather than HTML5 drag-and-drop, which doesn't fire on touch. */
  list.addEventListener('pointerdown', function (e) {
    var handle = e.target.closest('.a-rank-handle');
    if (!handle) return;
    e.preventDefault();
    var li = handle.closest('.a-rank-item');
    handle.setPointerCapture(e.pointerId);
    li.classList.add('is-dragging');

    function onMove(ev) {
      /* A loop, because a fast drag can cross several rows in one event. */
      var prev, next;
      while ((prev = li.previousElementSibling) && ev.clientY < prev.getBoundingClientRect().top + prev.offsetHeight / 2) {
        list.insertBefore(li, prev);
      }
      while ((next = li.nextElementSibling) && ev.clientY > next.getBoundingClientRect().top + next.offsetHeight / 2) {
        list.insertBefore(next, li);
      }
      renumber();
    }
    function onEnd() {
      handle.removeEventListener('pointermove', onMove);
      handle.removeEventListener('pointerup', onEnd);
      handle.removeEventListener('pointercancel', onEnd);
      li.classList.remove('is-dragging');
      moved(li);
    }
    handle.addEventListener('pointermove', onMove);
    handle.addEventListener('pointerup', onEnd);
    handle.addEventListener('pointercancel', onEnd);
  });

  /* Twelve open write-ups make the list too tall to drag across. */
  compact.addEventListener('click', function () {
    var on = list.classList.toggle('is-compact');
    compact.textContent = on ? 'Show write-ups' : 'Hide write-ups';
    compact.setAttribute('aria-pressed', String(on));
  });

  form.addEventListener('input', function () { dirty = true; });
  form.addEventListener('submit', function () { dirty = false; });
  window.addEventListener('beforeunload', function (e) { if (dirty) e.preventDefault(); });
  setInterval(function () { fetch('/admin/mfl/keepalive', { credentials: 'same-origin' }); }, 300000);

  renumber();
})();
