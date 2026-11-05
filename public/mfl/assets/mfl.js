/* Rank-over-time chart on /rankings. Drawn here rather than in PHP so it can
   measure its container and keep type at true size on a phone. Team names are
   set with textContent, because they come from Sleeper. */
(function () {
  var root = document.getElementById('trend');
  if (!root) return;
  var data = JSON.parse(document.getElementById('trend-data').textContent);
  var select = document.getElementById('trend-team');
  var NS = 'http://www.w3.org/2000/svg';
  var sets = data.sets, teams = data.teams, cols = sets.length;
  var geo = null;

  function el(name, attrs, parent, text) {
    var node = document.createElementNS(NS, name);
    Object.keys(attrs).forEach(function (k) { node.setAttribute(k, attrs[k]); });
    if (text !== undefined) node.textContent = text;
    if (parent) parent.appendChild(node);
    return node;
  }

  function latestRank(t) {
    for (var i = cols - 1; i >= 0; i--) if (t.ranks[i] !== null) return { rank: t.ranks[i], col: i };
    return null;
  }

  function shorten(name, max) { return name.length > max ? name.slice(0, max - 1) + '…' : name; }

  function draw() {
    var W = root.clientWidth, narrow = W < 560;
    var left = 24, right = narrow ? 112 : 190, top = 10, bottom = 28, row = narrow ? 24 : 28;
    var H = top + bottom + (data.n - 1) * row;
    var plot = W - left - right;
    var x = function (i) { return left + i * plot / (cols - 1); };
    var y = function (r) { return top + (r - 1) * row; };
    geo = { x: x, y: y, row: row, top: top, left: left, plot: plot, H: H };
    var focus = Number(select.value);

    root.textContent = '';
    var svg = el('svg', { viewBox: '0 0 ' + W + ' ' + H, height: H, role: 'img',
      'aria-label': 'Each team\'s power ranking over time. The table below has the same numbers.' }, root);

    for (var r = 1; r <= data.n; r++) {
      el('line', { x1: left, x2: left + plot, y1: y(r), y2: y(r), 'class': 'trend-grid' }, svg);
      el('text', { x: left - 10, y: y(r) + 4, 'text-anchor': 'end', 'class': 'trend-axis' }, svg, r);
    }

    /* Thin the week labels so they never collide. */
    var every = Math.ceil(cols / Math.max(1, Math.floor(plot / 48)));
    sets.forEach(function (s, i) {
      if (i % every && i !== cols - 1) return;
      el('text', { x: x(i), y: H - 8, 'text-anchor': 'middle', 'class': 'trend-axis' }, svg, s.short);
    });

    /* The highlighted line goes last so it sits on top. */
    var order = teams.map(function (t, i) { return i; }).filter(function (i) { return i !== focus; }).concat(focus);
    order.forEach(function (i) {
      var pts = [];
      teams[i].ranks.forEach(function (r, c) { if (r !== null) pts.push(x(c) + ',' + y(r)); });
      el('polyline', { points: pts.join(' '), 'class': 'trend-line' + (i === focus ? ' is-focus' : '') }, svg);
    });
    teams[focus].ranks.forEach(function (r, c) {
      if (r !== null) el('circle', { cx: x(c), cy: y(r), r: 4, 'class': 'trend-dot' }, svg);
    });

    /* Every team holds a different rank at the last column, so end labels can't overlap. */
    teams.forEach(function (t, i) {
      var end = latestRank(t);
      if (!end) return;
      var label = el('text', { x: x(end.col) + 10, y: y(end.rank) + 4,
        'class': 'trend-name' + (i === focus ? ' is-focus' : '') }, svg, shorten(t.name, narrow ? 13 : 24));
      label.addEventListener('click', function () { setFocus(i); });
    });

    geo.cross = el('line', { y1: top, y2: H - bottom, 'class': 'trend-cross', visibility: 'hidden' }, svg);
    var hit = el('rect', { x: left - 12, y: 0, width: plot + 24, height: H - bottom + 8, fill: 'transparent' }, svg);
    hit.addEventListener('pointermove', onHover);
    hit.addEventListener('pointerleave', hideTip);
    hit.addEventListener('click', onPick);
    root.appendChild(tip);
    tip.hidden = true;
  }

  var tip = document.createElement('div');
  tip.className = 'trend-tip';

  function nearestCol(e) {
    var px = e.clientX - root.getBoundingClientRect().left;
    return Math.max(0, Math.min(cols - 1, Math.round((px - geo.left) / (geo.plot / (cols - 1)))));
  }

  function onHover(e) {
    var col = nearestCol(e), focus = Number(select.value);
    geo.cross.setAttribute('x1', geo.x(col));
    geo.cross.setAttribute('x2', geo.x(col));
    geo.cross.setAttribute('visibility', 'visible');

    tip.textContent = '';
    var head = document.createElement('div');
    head.className = 'trend-tip-title mono-label';
    head.textContent = sets[col].full;
    tip.appendChild(head);
    teams.map(function (t, i) { return { name: t.name, rank: t.ranks[col], i: i }; })
      .filter(function (t) { return t.rank !== null; })
      .sort(function (a, b) { return a.rank - b.rank; })
      .forEach(function (t) {
        var line = document.createElement('div');
        line.className = 'trend-tip-row' + (t.i === focus ? ' is-focus' : '');
        var value = document.createElement('b');
        value.textContent = t.rank;
        var name = document.createElement('span');
        name.textContent = t.name;
        line.appendChild(value);
        line.appendChild(name);
        tip.appendChild(line);
      });
    tip.hidden = false;

    /* Flip to whichever side of the crosshair has room. */
    var cx = geo.x(col), w = tip.offsetWidth;
    tip.style.left = (cx + 12 + w > root.clientWidth ? Math.max(0, cx - w - 12) : cx + 12) + 'px';
  }

  function hideTip() {
    tip.hidden = true;
    if (geo.cross) geo.cross.setAttribute('visibility', 'hidden');
  }

  /* Clicking the plot highlights whichever team holds the nearest rank there. */
  function onPick(e) {
    var col = nearestCol(e);
    var py = e.clientY - root.getBoundingClientRect().top;
    var rank = Math.round((py - geo.top) / geo.row) + 1;
    teams.forEach(function (t, i) { if (t.ranks[col] === rank) setFocus(i); });
  }

  function setFocus(i) {
    select.value = String(i);
    draw();
  }

  select.addEventListener('change', draw);
  if (window.ResizeObserver) new ResizeObserver(function () { if (root.clientWidth) draw(); }).observe(root);
  draw();
})();
