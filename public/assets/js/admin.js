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
    renumber();
    status.textContent = teamName(li) + ' is now number ' + (items().indexOf(li) + 1);
    form.dispatchEvent(new Event('input', { bubbles: true }));
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

  /* Autosave reorders the list when it restores a copy. */
  form.addEventListener('mfl:restored', renumber);

  renumber();
})();

/* Poll editor: add and remove answer rows. Blank rows are ignored on save. */
(function () {
  var box = document.getElementById('a-poll-answers');
  var add = document.getElementById('a-poll-add');
  if (!box || !add) return;

  function renumber() {
    Array.prototype.forEach.call(box.querySelectorAll('input'), function (input, i) {
      input.setAttribute('aria-label', 'Answer ' + (i + 1));
    });
  }

  add.addEventListener('click', function () {
    var row = box.lastElementChild.cloneNode(true);
    row.querySelector('input').value = '';
    box.appendChild(row);
    renumber();
    row.querySelector('input').focus();
  });

  box.addEventListener('click', function (e) {
    var btn = e.target.closest('.a-poll-remove');
    if (!btn) return;
    var row = btn.closest('.a-poll-answer');
    /* Keep one row to clone from. */
    if (box.children.length > 1) row.remove();
    else row.querySelector('input').value = '';
    renumber();
  });
})();

/* Autosave for the MFL editors, so a lost sign-in or connection can't cost
   anyone their writing. Each change is copied to this device until the server
   confirms a save (the page comes back with data-saved). Before every save the
   editor checks the sign-in and collects a fresh token, so a save can't vanish
   into a redirect to the login page. */
(function () {
  var form = document.querySelector('form[data-autosave]');
  if (!form) return;

  var key = 'mfl-draft:' + form.getAttribute('data-autosave');
  var timer = null;
  var dirty = false;
  var checked = false;
  var banner = null;

  function read() {
    var fields = [];
    new FormData(form).forEach(function (value, name) {
      if (name !== '_csrf' && name !== 'action') fields.push([name, value]);
    });
    return fields;
  }

  function write() {
    clearTimeout(timer);
    timer = null;
    try {
      localStorage.setItem(key, JSON.stringify({ at: Date.now(), fields: read() }));
    } catch (e) { /* storage full or blocked: the save button still works */ }
  }

  function forget(name) {
    try { localStorage.removeItem(name); } catch (e) { /* nothing to clear */ }
  }

  function stored() {
    try { return JSON.parse(localStorage.getItem(key)); } catch (e) { return null; }
  }

  function showBanner(kind, text, actions) {
    hideBanner();
    banner = document.createElement('div');
    banner.className = 'a-flash a-flash--' + kind + ' a-autosave-banner';
    banner.setAttribute('role', 'alert');
    banner.appendChild(document.createTextNode(text + ' '));
    (actions || []).forEach(function (action) { banner.appendChild(action); });
    form.insertBefore(banner, form.firstChild);
  }

  function hideBanner() {
    if (banner) banner.remove();
    banner = null;
  }

  function button(label, onClick) {
    var b = document.createElement('button');
    b.type = 'button';
    b.className = 'a-link-btn';
    b.textContent = label;
    b.addEventListener('click', onClick);
    return b;
  }

  function restore(fields) {
    var values = {};
    fields.forEach(function (f) { (values[f[0]] = values[f[0]] || []).push(f[1]); });

    /* Rankings: put the teams back in the saved order. */
    var list = document.getElementById('a-rank-list');
    if (list && values['order[]']) {
      values['order[]'].forEach(function (id) {
        var input = list.querySelector('input[name="order[]"][value="' + id + '"]');
        if (input) list.appendChild(input.closest('.a-rank-item'));
      });
    }

    /* Polls: match the number of answer rows, then fill them. */
    var answers = document.getElementById('a-poll-answers');
    var add = document.getElementById('a-poll-add');
    if (answers && add && values['answers[]']) {
      while (answers.children.length < values['answers[]'].length) add.click();
      while (answers.children.length > values['answers[]'].length) answers.lastElementChild.remove();
      answers.querySelectorAll('input').forEach(function (input, i) { input.value = values['answers[]'][i]; });
    }

    fields.forEach(function (f) {
      if (f[0] === 'order[]' || f[0] === 'answers[]') return;
      var el = form.elements[f[0]];
      if (el) el.value = f[1];
    });
    form.dispatchEvent(new Event('mfl:restored'));
    dirty = true;
  }

  /* 'ok', 'out' or 'offline'. A fresh token also rescues a form left open
     across a sign-out and sign-in in another tab. */
  function check() {
    return fetch('/admin/mfl/keepalive', { credentials: 'same-origin', redirect: 'manual', cache: 'no-store' })
      .then(function (r) {
        if (!r.ok) return 'out';
        return r.json().then(function (data) {
          document.querySelectorAll('input[name="_csrf"]').forEach(function (input) { input.value = data.csrf; });
          return 'ok';
        });
      })
      .catch(function () { return 'offline'; });
  }

  function signedOut() {
    var link = document.createElement('a');
    link.className = 'a-link-btn';
    link.textContent = 'Sign in';
    link.href = '/admin/login?next=' + encodeURIComponent(location.pathname + location.search.replace(/&revision=\d+/, ''));
    link.addEventListener('click', function () { write(); dirty = false; });
    showBanner('err', "You've been signed out. Your work is saved on this device, and you can restore it after you sign in.", [link]);
  }

  function watch() {
    check().then(function (state) {
      if (state === 'out') signedOut();
      else if (state === 'ok' && banner && banner.classList.contains('a-flash--err')) hideBanner();
    });
  }

  /* A confirmed save ends the need for the copy. */
  if (form.hasAttribute('data-saved')) {
    forget(key);
    (form.getAttribute('data-clear') || '').split(' ').forEach(function (name) { if (name) forget('mfl-draft:' + name); });
  }

  var copy = stored();
  if (copy && !form.hasAttribute('data-no-restore')) {
    if (JSON.stringify(copy.fields) === JSON.stringify(read())) {
      forget(key);
    } else {
      showBanner('ok', 'You have unsaved work on this device from ' + new Date(copy.at).toLocaleString() + '.', [
        button('Restore it', function () { restore(copy.fields); write(); hideBanner(); }),
        button('Discard it', function () { forget(key); hideBanner(); })
      ]);
    }
  }

  form.addEventListener('input', function () {
    dirty = true;
    clearTimeout(timer);
    timer = setTimeout(write, 800);
  });

  form.addEventListener('submit', function (e) {
    if (checked) { dirty = false; return; }
    e.preventDefault();
    var submitter = e.submitter;
    write();
    check().then(function (state) {
      if (state === 'ok') {
        checked = true;
        form.requestSubmit(submitter);
      } else if (state === 'out') {
        signedOut();
      } else {
        showBanner('err', "Can't reach the site right now. Your work is saved on this device, so try again in a moment.");
      }
    });
  });

  function flush() { if (timer) write(); }
  window.addEventListener('pagehide', flush);
  document.addEventListener('visibilitychange', function () {
    flush();
    if (document.visibilityState === 'visible') watch();
  });
  window.addEventListener('beforeunload', function (e) {
    flush();
    if (dirty) e.preventDefault();
  });

  setInterval(watch, 60000);
})();
