/* Trade machine on /trades. Runs entirely in the browser from the values the
   page embeds (see mfl_trade_machine_data() in app/mfl/values.php), so trying
   trades costs no round trips. Names come from Sleeper, so they're only ever
   set with textContent or escaped. */
(() => {
  const root = document.getElementById('trade-machine');
  if (!root) return;
  const data = JSON.parse(document.getElementById('tm-data').textContent);
  const results = root.querySelector('.tm-results');
  const SIDES = ['a', 'b'];
  const NS = 'http://www.w3.org/2000/svg';
  const EVEN = 0.05; // within 5% counts as even

  const teams = new Map(data.teams.map(t => [t.id, t]));
  const assets = new Map();
  data.players.forEach(p => assets.set(p.id, { ...p, kind: 'player' }));
  data.picks.forEach(p => assets.set(p.id, { ...p, kind: 'pick' }));

  const state = {
    team: { a: data.mine, b: null },
    sends: { a: [], b: [] },
    clock: { a: data.balanced, b: data.balanced },
    lens: 'model',
    mode: 'build',
    target: null,
  };

  /* ---- helpers ---- */

  const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const fmt = n => Math.round(n).toLocaleString('en-US');
  const teamName = id => (teams.get(id) || {}).name || 'Team';
  const other = side => (side === 'a' ? 'b' : 'a');
  const weighted = (par, w) => par.reduce((sum, v, i) => sum + v * (w[i] || 0), 0);
  const weights = side => data.clocks[state.clock[side]].weights;
  const balanced = data.clocks[data.balanced].weights;

  // A pick that changed hands carries the original team's name: "2027 1st (Kevin Kolb's)".
  const label = a => a.kind === 'pick' && a.from ? `${a.name} (${a.from}'s)` : a.name;

  function detail(a) {
    if (a.kind === 'pick') return a.range;
    const bits = [a.pos, a.nfl || 'FA'];
    if (a.age) bits.push(`age ${a.age.toFixed(0)}`);
    if (a.injury) bits.push(a.injury);
    return bits.join(' · ');
  }

  /** Model value of a list of assets, by one team's clock. */
  const modelOf = (ids, w) => ids.reduce((s, id) => s + weighted(assets.get(id).par, w), 0);
  const marketOf = ids => ids.reduce((s, id) => s + (assets.get(id).market || 0), 0);

  /* ---- the two team columns ---- */

  function fillTeamSelect(side) {
    const sel = root.querySelector(`#tm-team-${side}`);
    sel.textContent = '';
    const blank = new Option(side === 'b' ? 'Choose a team' : 'Choose your team', '');
    sel.add(blank);
    data.teams.forEach(t => {
      if (t.id === state.team[other(side)]) return;
      const o = new Option(`${t.name} · ${t.owner}`, t.id);
      o.selected = t.id === state.team[side];
      sel.add(o);
    });
  }

  function fillAddSelect(side) {
    const sel = root.querySelector(`#tm-add-${side}`);
    sel.textContent = '';
    const team = state.team[side];
    sel.add(new Option(team ? '+ Add a player or pick' : 'Choose a team first', ''));
    sel.disabled = !team;
    if (!team) return;
    const chosen = new Set(state.sends[side]);
    const mine = [...assets.values()].filter(a => a.team === team && !chosen.has(a.id));
    const groups = [['QB', 'Quarterbacks'], ['RB', 'Running backs'], ['WR', 'Wide receivers'], ['TE', 'Tight ends']];
    groups.forEach(([pos, name]) => {
      const list = mine.filter(a => a.kind === 'player' && a.pos === pos)
        .sort((x, y) => weighted(y.par, balanced) - weighted(x.par, balanced));
      if (!list.length) return;
      const g = document.createElement('optgroup');
      g.label = name;
      list.forEach(a => g.appendChild(new Option(`${a.name} · ${fmt(weighted(a.par, balanced))}`, a.id)));
      sel.appendChild(g);
    });
    const picks = mine.filter(a => a.kind === 'pick').sort((x, y) => x.season - y.season || x.round - y.round);
    if (picks.length) {
      const g = document.createElement('optgroup');
      g.label = 'Draft picks';
      picks.forEach(a => g.appendChild(new Option(`${label(a)} · ${a.range}`, a.id)));
      sel.appendChild(g);
    }
  }

  function drawList(side) {
    const ul = root.querySelector(`.tm-side--${side} .tm-list`);
    ul.textContent = '';
    if (!state.sends[side].length) {
      const li = document.createElement('li');
      li.className = 'tm-empty';
      li.textContent = state.team[side] ? 'Nothing yet' : '';
      ul.appendChild(li);
      return;
    }
    state.sends[side].forEach(id => {
      const a = assets.get(id);
      const li = document.createElement('li');
      li.className = 'tm-item';
      const text = document.createElement('span');
      const name = document.createElement('b');
      name.textContent = label(a);
      const sub = document.createElement('span');
      sub.className = 'row-note';
      sub.textContent = `${detail(a)} · Model ${fmt(weighted(a.par, balanced))} · Market ${a.market === null ? '—' : fmt(a.market)}`;
      text.append(name, sub);
      const x = document.createElement('button');
      x.type = 'button';
      x.className = 'tm-remove';
      x.textContent = '×';
      x.setAttribute('aria-label', `Remove ${label(a)}`);
      x.addEventListener('click', () => { state.sends[side] = state.sends[side].filter(v => v !== id); update(); });
      li.append(text, x);
      ul.appendChild(li);
    });
  }

  function drawClock(side) {
    root.querySelector(`#tm-clock-${side}`).value = state.clock[side];
    root.querySelector(`.tm-side--${side} .tm-clock-label`).textContent = data.clocks[state.clock[side]].label;
  }

  /* ---- results ---- */

  /** Each team's take: what it receives and sends, by its own clock. */
  function modelVerdict() {
    const out = {};
    SIDES.forEach(s => {
      const w = weights(s);
      const gets = modelOf(state.sends[other(s)], w);
      const gives = modelOf(state.sends[s], w);
      out[s] = { gets, gives, net: gets - gives, pct: (gets - gives) / Math.max(gets, gives, 1) };
    });
    return out;
  }

  function marketVerdict() {
    const out = {};
    SIDES.forEach(s => {
      const gets = marketOf(state.sends[other(s)]);
      const gives = marketOf(state.sends[s]);
      out[s] = { gets, gives, net: gets - gives, pct: (gets - gives) / Math.max(gets, gives, 1) };
    });
    return out;
  }

  function headline(v, what) {
    const a = v.a, b = v.b;
    if (Math.abs(a.pct) < EVEN && Math.abs(b.pct) < EVEN) return `Even trade by the ${what}.`;
    if (a.net > 0 && b.net > 0) return `Both teams come out ahead on their own timelines.`;
    const win = a.net >= b.net ? 'a' : 'b';
    return `The ${what} favors ${esc(teamName(state.team[win]))} by ${Math.round(Math.abs(v[win].pct) * 100)}%.`;
  }

  function verdictBlock(title, v, what, note) {
    const total = v.a.gets + v.b.gets || 1;
    const share = Math.round(v.a.gets / total * 100);
    const row = s => `<li><b>${esc(teamName(state.team[s]))}</b> gets ${fmt(v[s].gets)}, gives ${fmt(v[s].gives)}
      <span class="tm-net ${v[s].net >= 0 ? 'is-up' : 'is-down'}">${v[s].net >= 0 ? '+' : '−'}${fmt(Math.abs(v[s].net))}</span></li>`;
    return `<div class="tm-verdict">
      <p class="mono-label">${title}</p>
      <p class="tm-headline">${headline(v, what)}</p>
      <div class="tm-split" role="img" aria-label="${esc(teamName(state.team.a))} receives ${share}% of the value in this trade">
        <span class="tm-split-a" style="width:${share}%"></span><span class="tm-split-b" style="width:${100 - share}%"></span>
      </div>
      <ul class="tm-nets">${row('a')}${row('b')}</ul>
      ${note ? `<p class="row-note">${note}</p>` : ''}
    </div>`;
  }

  /** Points above replacement each team receives, season by season, as grouped bars. */
  function drawChart(box) {
    const n = data.seasons.length;
    const gets = s => data.seasons.map((_, i) => state.sends[other(s)].reduce((sum, id) => sum + (assets.get(id).par[i] || 0), 0));
    const series = { a: gets('a'), b: gets('b') };
    const max = Math.max(1, ...series.a, ...series.b);
    const W = Math.max(300, box.clientWidth), H = 190, top = 18, bottom = 26, left = 4, right = 4;
    const band = (W - left - right) / n, bar = Math.min(34, band * 0.32);
    const y = v => top + (H - top - bottom) * (1 - v / max);
    const svg = document.createElementNS(NS, 'svg');
    svg.setAttribute('viewBox', `0 0 ${W} ${H}`);
    svg.setAttribute('height', H);
    svg.setAttribute('role', 'img');
    svg.setAttribute('aria-label', 'Points above replacement each team receives in each season. The sentence below sums it up.');
    const el = (name, attrs, text) => {
      const node = document.createElementNS(NS, name);
      Object.entries(attrs).forEach(([k, v]) => node.setAttribute(k, v));
      if (text !== undefined) node.textContent = text;
      svg.appendChild(node);
      return node;
    };
    el('line', { x1: left, x2: W - right, y1: H - bottom, y2: H - bottom, class: 'tm-axis' });
    data.seasons.forEach((season, i) => {
      const cx = left + band * i + band / 2;
      SIDES.forEach((s, j) => {
        const v = series[s][i];
        const x = cx + (j === 0 ? -bar - 2 : 2);
        el('rect', { x, y: y(v), width: bar, height: Math.max(0, H - bottom - y(v)), class: `tm-bar tm-bar--${s}` });
        if (v >= 1) el('text', { x: x + bar / 2, y: y(v) - 4, class: 'tm-bar-value', 'text-anchor': 'middle' }, fmt(v));
      });
      el('text', { x: cx, y: H - 8, class: 'tm-bar-label', 'text-anchor': 'middle' }, season.replace(' rest', ''));
    });
    box.textContent = '';
    box.appendChild(svg);
    return series;
  }

  /** "Team A gets more through 2027, Team B from 2028 on." */
  function chartSummary(series) {
    const lead = data.seasons.map((_, i) => {
      const d = series.a[i] - series.b[i];
      const big = Math.max(series.a[i], series.b[i]);
      return big < 3 || Math.abs(d) < 0.1 * big ? null : d > 0 ? 'a' : 'b';
    });
    const years = data.seasons.map(s => s.replace(' rest', ''));
    const runs = [];
    lead.forEach((s, i) => {
      if (runs.length && runs[runs.length - 1].side === s) runs[runs.length - 1].to = i;
      else runs.push({ side: s, from: i, to: i });
    });
    const parts = runs.filter(r => r.side).map(r => {
      const span = r.from === r.to ? `in ${years[r.from]}` : r.to === years.length - 1 ? `from ${years[r.from]} on` : `in ${years[r.from]}–${years[r.to]}`;
      return `${esc(teamName(state.team[r.side]))} gets more ${span}`;
    });
    return parts.length ? parts.join('; ') + '.' : 'The two sides are close every season.';
  }

  const OUT = ['IR', 'PUP', 'Sus', 'NA', 'DNR'];

  /** Best starting lineup by current points per game: QB, 2 RB, 2 WR, TE, FLEX. */
  function lineup(ids) {
    const pool = ids.map(id => assets.get(id)).filter(a => a.kind === 'player' && !OUT.includes(a.injury))
      .sort((x, y) => y.ppg - x.ppg);
    const need = { QB: 1, RB: 2, WR: 2, TE: 1 };
    const starters = [];
    const left = [];
    pool.forEach(p => { if (need[p.pos] > 0) { need[p.pos]--; starters.push(p); } else left.push(p); });
    const flex = left.find(p => ['RB', 'WR', 'TE'].includes(p.pos));
    if (flex) starters.push(flex);
    return { total: starters.reduce((s, p) => s + p.ppg, 0), starters };
  }

  function lineupBlock(side) {
    const team = state.team[side];
    const before = [...assets.values()].filter(a => a.team === team).map(a => a.id);
    const after = before.filter(id => !state.sends[side].includes(id)).concat(state.sends[other(side)]);
    const b = lineup(before), a = lineup(after);
    const d = a.total - b.total;
    const ids = list => new Set(list.map(p => p.id));
    const bIds = ids(b.starters), aIds = ids(a.starters);
    const into = a.starters.filter(p => !bIds.has(p.id)).map(p => p.name);
    const outOf = b.starters.filter(p => !aIds.has(p.id)).map(p => p.name);
    const change = into.length || outOf.length
      ? `${into.length ? 'In: ' + esc(into.join(', ')) : ''}${into.length && outOf.length ? '. ' : ''}${outOf.length ? 'Out: ' + esc(outOf.join(', ')) : ''}.`
      : 'Same starters.';
    return `<li><b>${esc(teamName(team))}</b> ${b.total.toFixed(1)} → ${a.total.toFixed(1)} pts/gm
      <span class="tm-net ${d >= 0 ? 'is-up' : 'is-down'}">${d >= 0 ? '+' : '−'}${Math.abs(d).toFixed(1)}</span>
      <span class="row-note">${change}</span></li>`;
  }

  /** The smallest additions that would even things up, from the team getting more. */
  function balanceBlock() {
    const val = id => state.lens === 'model' ? weighted(assets.get(id).par, balanced) : (assets.get(id).market || 0);
    const gets = s => state.sends[other(s)].reduce((sum, id) => sum + val(id), 0);
    const gap = gets('a') - gets('b');
    const what = state.lens === 'model' ? 'model' : 'market';
    const toggle = `<div class="tm-lens" role="radiogroup" aria-label="Balance by">
      <span class="mono-label">Balance by</span>
      ${['model', 'market'].map(l => `<label class="pill"><input type="radio" name="tm-lens" value="${l}"${state.lens === l ? ' checked' : ''}><span>${l === 'model' ? 'Model' : 'Market'}</span></label>`).join('')}
    </div>`;
    if (Math.abs(gap) < EVEN * Math.max(gets('a'), gets('b'), 1)) {
      return `${toggle}<p>Already even by the ${what}, on a balanced timeline.</p>`;
    }
    const ahead = gap > 0 ? 'a' : 'b';
    const need = Math.abs(gap);
    const chosen = new Set([...state.sends.a, ...state.sends.b]);
    const options = [...assets.values()]
      .filter(a => a.team === state.team[ahead] && !chosen.has(a.id))
      .map(a => ({ a, v: val(a.id) }))
      .filter(o => o.v >= need * 0.35 && o.v <= need * 1.35)
      .sort((x, y) => Math.abs(x.v - need) - Math.abs(y.v - need))
      .slice(0, 3);
    const who = esc(teamName(state.team[ahead]));
    if (!options.length) {
      return `${toggle}<p>${who} gets ${fmt(need)} more by the ${what}. Nothing on their roster is close to that gap on its own.</p>`;
    }
    return `${toggle}<p>${who} gets ${fmt(need)} more by the ${what}. To even it up, ${who} could add:</p>
      <ul class="tm-suggest">${options.map(o => `<li><button type="button" class="tm-add-suggest" data-side="${ahead}" data-id="${esc(o.a.id)}">+ ${esc(label(o.a))}</button>
        <span class="row-note">${esc(detail(o.a))} · ${fmt(o.v)}</span></li>`).join('')}</ul>`;
  }

  function drawResults() {
    if (!state.team.a || !state.team.b || (!state.sends.a.length && !state.sends.b.length)) {
      results.innerHTML = state.team.a && state.team.b
        ? '<p class="tm-hint">Add players or picks to either side to see how the trade grades out.</p>'
        : '<p class="tm-hint">Choose both teams to start.</p>';
      return;
    }
    const sameClock = state.clock.a === state.clock.b;
    const clockNote = sameClock ? '' : `Each team's side is valued on its own timeline: ${esc(teamName(state.team.a))} ${esc(data.clocks[state.clock.a].label.toLowerCase())}, ${esc(teamName(state.team.b))} ${esc(data.clocks[state.clock.b].label.toLowerCase())}.`;
    const missing = [...state.sends.a, ...state.sends.b].filter(id => assets.get(id).market === null).map(id => label(assets.get(id)));
    const marketNote = missing.length ? `No market value for ${esc(missing.join(', '))}, counted as 0.` : '';
    results.innerHTML = `
      <div class="tm-verdicts">
        ${verdictBlock('Model', modelVerdict(), 'model', clockNote)}
        ${verdictBlock('Market', marketVerdict(), 'market', marketNote)}
      </div>
      <div class="tm-panel">
        <h3>Who gets more, season by season</h3>
        <p class="tm-legend"><span class="tm-key tm-key--a"></span>${esc(teamName(state.team.a))} receives <span class="tm-key tm-key--b"></span>${esc(teamName(state.team.b))} receives</p>
        <div class="tm-chart"></div>
        <p class="tm-chart-summary"></p>
        <p class="row-note">Model points above a replacement starter, before any timeline weighting.</p>
      </div>
      <div class="tm-panel">
        <h3>Starting lineup this season</h3>
        <ul class="tm-lineups">${lineupBlock('a')}${lineupBlock('b')}</ul>
        <p class="row-note">Best lineup by current points per game: QB, 2 RB, 2 WR, TE, FLEX. Players on IR don't count.</p>
      </div>
      <div class="tm-panel tm-balance">
        <h3>Balance it</h3>
        ${balanceBlock()}
      </div>
      ${state.sends.b.length ? `<div class="tm-panel tm-alternatives"><h3>Alternatives</h3>${alternativesBlock()}</div>` : ''}`;
    const series = drawChart(results.querySelector('.tm-chart'));
    results.querySelector('.tm-chart-summary').innerHTML = chartSummary(series);
  }

  /* ---- finding deals ---- */

  const rosterIds = team => [...assets.values()].filter(a => a.team === team).map(a => a.id);
  const signed = (n, digits = 1) => `${n >= 0 ? '+' : '−'}${Math.abs(n).toFixed(digits)}`;

  /**
   * Packages of up to three assets from one team that should get a deal done
   * for the target assets. A package qualifies when the other team gets at
   * least what it gives up, by its own timeline in the model and by the market
   * (within 5%). Among those, the best cost you the least by your timeline,
   * use fewer roster spots and help the other team's lineup. Returns up to
   * `limit` packages, each built around a different main piece.
   */
  function findPackages(targetIds, yourTeam, theirTeam, wYou, wThem, limit = 3) {
    const needModel = modelOf(targetIds, wThem);
    const needMarket = marketOf(targetIds);
    const marketKnown = targetIds.every(id => assets.get(id).market !== null) && needMarket > 0;
    const youGet = Math.max(1, modelOf(targetIds, wYou));
    const ctx = { needModel, needMarket, marketKnown, youGet, targetIds, yourTeam, theirTeam, packages: [] };
    if (needModel < 1 && !marketKnown) return ctx;
    const share = o => o.m / Math.max(1, needModel) + (marketKnown ? o.k / needMarket : 0);
    // The 22 pieces that matter most to the other side keep the search to a
    // couple of thousand combinations.
    const pool = rosterIds(yourTeam)
      .map(id => ({ id, m: weighted(assets.get(id).par, wThem), k: assets.get(id).market || 0, you: weighted(assets.get(id).par, wYou) }))
      .filter(o => o.m >= 0.03 * needModel || (marketKnown && o.k >= 0.03 * needMarket))
      .sort((x, y) => share(y) - share(x))
      .slice(0, 22);
    const theirRoster = rosterIds(theirTeam).filter(id => !targetIds.includes(id));
    const theirBefore = lineup(rosterIds(theirTeam)).total;
    const found = [];
    const consider = combo => {
      const m = combo.reduce((s, o) => s + o.m, 0);
      const k = combo.reduce((s, o) => s + o.k, 0);
      if (m < 0.98 * needModel) return;
      if (marketKnown && k < 0.95 * needMarket) return;
      const ids = combo.map(o => o.id);
      const give = combo.reduce((s, o) => s + o.you, 0);
      const theirGain = lineup(theirRoster.concat(ids)).total - theirBefore;
      const overpay = marketKnown ? Math.max(0, k / needMarket - 1) : 0;
      const score = give / youGet + 0.5 * overpay + 0.05 * (combo.length - 1) - 0.01 * theirGain;
      const main = combo.reduce((best, o) => (o.m > best.m ? o : best)).id;
      found.push({ ids, main, model: m, market: k, give, theirGain, score });
    };
    for (let i = 0; i < pool.length; i++) {
      consider([pool[i]]);
      for (let j = i + 1; j < pool.length; j++) {
        consider([pool[i], pool[j]]);
        for (let k = j + 1; k < pool.length; k++) consider([pool[i], pool[j], pool[k]]);
      }
    }
    found.sort((x, y) => x.score - y.score);
    const used = new Set();
    for (const p of found) {
      if (used.has(p.main)) continue;
      used.add(p.main);
      ctx.packages.push(p);
      if (ctx.packages.length >= limit) break;
    }
    return ctx;
  }

  /** One package, with what each side gets out of it. */
  function packageCard(p, ctx, button) {
    const yours = rosterIds(ctx.yourTeam);
    const yourGain = lineup(yours.filter(id => !p.ids.includes(id)).concat(ctx.targetIds)).total - lineup(yours).total;
    const them = esc(teamName(ctx.theirTeam));
    return `<li class="tm-package">
      <p class="tm-package-offer">Offer <b>${p.ids.map(id => esc(label(assets.get(id)))).join('</b> + <b>')}</b></p>
      <p class="row-note">${p.ids.map(id => esc(detail(assets.get(id)))).join(' / ')}</p>
      <ul class="tm-package-facts">
        <li>${them} gets <b>${fmt(p.model)}</b> by the model on their timeline, for ${fmt(ctx.needModel)}</li>
        <li>Market: <b>${fmt(p.market)}</b> for ${ctx.marketKnown ? fmt(ctx.needMarket) : 'a piece with no market value'}</li>
        <li>On your timeline you give ${fmt(p.give)} for ${fmt(ctx.youGet)}</li>
        <li>Starting lineups: yours <span class="tm-net ${yourGain >= 0 ? 'is-up' : 'is-down'}">${signed(yourGain)}</span>, theirs <span class="tm-net ${p.theirGain >= 0 ? 'is-up' : 'is-down'}">${signed(p.theirGain)}</span> pts/gm</li>
      </ul>
      ${button}
    </li>`;
  }

  function fillFinder() {
    const team = root.querySelector('#tm-find-team');
    team.textContent = '';
    team.add(new Option('Choose your team', ''));
    data.teams.forEach(t => {
      const o = new Option(`${t.name} · ${t.owner}`, t.id);
      o.selected = t.id === state.team.a;
      team.add(o);
    });
    const target = root.querySelector('#tm-find-target');
    target.textContent = '';
    target.add(new Option(state.team.a ? 'Choose a player or pick' : 'Choose your team first', ''));
    target.disabled = !state.team.a;
    if (state.team.a) {
      data.teams.filter(t => t.id !== state.team.a).forEach(t => {
        const list = [...assets.values()].filter(a => a.team === t.id)
          .sort((x, y) => (x.kind === y.kind ? weighted(y.par, balanced) - weighted(x.par, balanced) : x.kind === 'player' ? -1 : 1));
        if (!list.length) return;
        const g = document.createElement('optgroup');
        g.label = t.name;
        list.forEach(a => {
          const o = new Option(a.kind === 'pick' ? `${label(a)} · ${a.range}` : `${a.name} · ${a.pos} · ${fmt(weighted(a.par, balanced))}`, a.id);
          o.selected = a.id === state.target;
          g.appendChild(o);
        });
        target.appendChild(g);
      });
    }
    root.querySelector('#tm-find-clock').value = state.clock.b;
    root.querySelector('.tm-finder .tm-clock-label').textContent = data.clocks[state.clock.b].label;
  }

  function drawFinder() {
    const box = root.querySelector('.tm-finder-results');
    if (!state.team.a || !state.target) {
      box.innerHTML = '<p class="tm-hint">Pick a player or pick on another team, and the machine will look for packages from your roster that should get it done.</p>';
      return;
    }
    const target = assets.get(state.target);
    const ctx = findPackages([target.id], state.team.a, target.team, weights('a'), weights('b'));
    const them = esc(teamName(target.team));
    if (!ctx.packages.length) {
      box.innerHTML = `<p>Nothing on your roster, up to three pieces, gets to what ${them} would give up for ${esc(label(target))}.</p>`;
      return;
    }
    box.innerHTML = `<p class="tm-finder-lead">Packages that should get ${them} to say yes to <b>${esc(label(target))}</b>, cheapest for you first.
      Your timeline is ${esc(data.clocks[state.clock.a].label.toLowerCase())}; change it under Build a trade.</p>
      <ol class="tm-packages">${ctx.packages.map(p => packageCard(p, ctx,
        `<button type="button" class="tm-add-suggest tm-open-deal" data-ids="${esc(p.ids.join(','))}">Open in the trade builder</button>`)).join('')}</ol>`;
  }

  /** Other places to get the main piece, and other ways to pay for what's on the table. */
  function alternativesBlock() {
    if (!state.sends.b.length) return '';
    const received = state.sends.b.map(id => assets.get(id));
    const main = received.reduce((x, y) => (weighted(y.par, balanced) > weighted(x.par, balanced) ? y : x));
    const v = weighted(main.par, balanced);
    const similar = [...assets.values()]
      .filter(a => a.id !== main.id && a.team !== state.team.a && a.team !== state.team.b && a.kind === main.kind
        && (a.kind === 'pick' ? a.round === main.round : a.pos === main.pos))
      .map(a => ({ a, v: weighted(a.par, balanced) }))
      .filter(o => v > 0 && Math.abs(o.v - v) <= 0.25 * v)
      .sort((x, y) => Math.abs(x.v - v) - Math.abs(y.v - v))
      .slice(0, 4);
    let html = `<h4>If ${esc(teamName(state.team.b))} says no</h4>`;
    html += similar.length
      ? `<p>Pieces like ${esc(label(main))} on other teams, by model value:</p>
        <ul class="tm-suggest">${similar.map(o => `<li><button type="button" class="tm-add-suggest tm-swap-in" data-team="${o.a.team}" data-id="${esc(o.a.id)}">Ask ${esc(teamName(o.a.team))} for ${esc(label(o.a))}</button>
          <span class="row-note">${esc(detail(o.a))} · ${fmt(o.v)}</span></li>`).join('')}</ul>`
      : `<p>No one else has a piece close to ${esc(label(main))}.</p>`;
    const ctx = findPackages(state.sends.b, state.team.a, state.team.b, weights('a'), weights('b'), 4);
    const current = [...state.sends.a].sort().join(',');
    const others = ctx.packages.filter(p => [...p.ids].sort().join(',') !== current).slice(0, 3);
    html += `<h4>Other ways to pay for it</h4>`;
    html += others.length
      ? `<ol class="tm-packages">${others.map(p => packageCard(p, ctx,
          `<button type="button" class="tm-add-suggest tm-use-offer" data-ids="${esc(p.ids.join(','))}">Offer this instead</button>`)).join('')}</ol>`
      : `<p>Nothing else on your roster, up to three pieces, covers it.</p>`;
    return html;
  }

  function setMode(mode) {
    state.mode = mode;
    root.querySelectorAll('.tm-mode').forEach(b => b.setAttribute('aria-pressed', String(b.dataset.mode === mode)));
    root.querySelector('.tm-build').hidden = mode !== 'build';
    root.querySelector('.tm-finder').hidden = mode !== 'find';
    if (mode === 'find') { fillFinder(); drawFinder(); }
  }

  /* ---- state in the URL, so a trade can be shared with another owner ---- */

  function saveUrl() {
    const q = new URLSearchParams(location.search);
    ['a', 'b'].forEach(s => {
      state.team[s] ? q.set(s, state.team[s]) : q.delete(s);
      state.sends[s].length ? q.set(`${s}s`, state.sends[s].join(',')) : q.delete(`${s}s`);
      state.clock[s] !== data.balanced ? q.set(`${s}c`, state.clock[s]) : q.delete(`${s}c`);
    });
    history.replaceState(null, '', `${location.pathname}?${q.toString()}`.replace(/\?$/, '') + '#trade-machine');
  }

  function loadUrl() {
    const q = new URLSearchParams(location.search);
    ['a', 'b'].forEach(s => {
      const t = Number(q.get(s));
      if (teams.has(t)) state.team[s] = t;
      const c = Number(q.get(`${s}c`));
      if (q.has(`${s}c`) && data.clocks[c]) state.clock[s] = c;
    });
    if (state.team.a === state.team.b) state.team.b = null;
    ['a', 'b'].forEach(s => {
      state.sends[s] = (q.get(`${s}s`) || '').split(',').filter(id => assets.has(id) && assets.get(id).team === state.team[s]);
    });
  }

  // The address only changes once someone starts building a trade.
  let touched = false;

  function update() {
    SIDES.forEach(s => { fillTeamSelect(s); fillAddSelect(s); drawList(s); drawClock(s); });
    drawResults();
    if (touched) saveUrl();
  }

  /* ---- events ---- */

  SIDES.forEach(s => {
    root.querySelector(`#tm-team-${s}`).addEventListener('change', e => {
      state.team[s] = Number(e.target.value) || null;
      state.sends[s] = [];
      update();
    });
    root.querySelector(`#tm-add-${s}`).addEventListener('change', e => {
      if (e.target.value) state.sends[s].push(e.target.value);
      update();
      root.querySelector(`#tm-add-${s}`).focus();
    });
    root.querySelector(`#tm-clock-${s}`).addEventListener('input', e => {
      state.clock[s] = Number(e.target.value);
      drawClock(s);
      drawResults();
      if (touched) saveUrl();
    });
  });

  root.addEventListener('input', () => { touched = true; });
  root.addEventListener('click', () => { touched = true; });

  results.addEventListener('click', e => {
    const btn = e.target.closest('.tm-add-suggest');
    if (!btn) return;
    if (btn.classList.contains('tm-swap-in')) {
      // Same offer, different team: keep what you send, ask for the new piece.
      state.team.b = Number(btn.dataset.team);
      state.sends.b = [btn.dataset.id];
    } else if (btn.classList.contains('tm-use-offer')) {
      state.sends.a = btn.dataset.ids.split(',');
    } else {
      state.sends[btn.dataset.side].push(btn.dataset.id);
    }
    update();
    root.querySelector('.tm-results').scrollIntoView({ behavior: 'smooth', block: 'start' });
  });

  root.querySelectorAll('.tm-mode').forEach(b => b.addEventListener('click', () => setMode(b.dataset.mode)));
  root.querySelector('#tm-find-team').addEventListener('change', e => {
    state.team.a = Number(e.target.value) || null;
    state.sends.a = [];
    if (state.target && assets.get(state.target).team === state.team.a) state.target = null;
    fillFinder();
    drawFinder();
  });
  root.querySelector('#tm-find-target').addEventListener('change', e => {
    state.target = e.target.value || null;
    drawFinder();
  });
  root.querySelector('#tm-find-clock').addEventListener('input', e => {
    state.clock.b = Number(e.target.value);
    root.querySelector('.tm-finder .tm-clock-label').textContent = data.clocks[state.clock.b].label;
    drawFinder();
  });
  root.querySelector('.tm-finder-results').addEventListener('click', e => {
    const btn = e.target.closest('.tm-open-deal');
    if (!btn) return;
    const target = assets.get(state.target);
    state.team.b = target.team;
    state.sends.b = [target.id];
    state.sends.a = btn.dataset.ids.split(',');
    setMode('build');
    update();
    root.querySelector('.tm-results').scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
  results.addEventListener('change', e => {
    if (e.target.name !== 'tm-lens') return;
    state.lens = e.target.value;
    drawResults();
  });

  let width = root.clientWidth;
  window.addEventListener('resize', () => {
    if (root.clientWidth === width) return;
    width = root.clientWidth;
    drawResults();
  });

  loadUrl();
  touched = new URLSearchParams(location.search).has('a');
  update();
})();
