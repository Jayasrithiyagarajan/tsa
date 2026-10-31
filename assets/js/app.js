/* TUV EAST IMS — progressive enhancement (no framework) */
(function () {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => Array.from(r.querySelectorAll(s));
  const csrf = () => (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

  window.IMS = {
    toast(msg, ms = 2600) {
      const w = $('#toasts'); if (!w) return;
      const t = document.createElement('div'); t.className = 'toast'; t.textContent = msg; w.appendChild(t);
      setTimeout(() => t.remove(), ms);
    },
    async post(url, data) {
      const body = data instanceof FormData ? data : new URLSearchParams(data);
      const r = await fetch(url, { method: 'POST', body, headers: { 'X-CSRF-Token': csrf(), 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' }, credentials: 'same-origin' });
      const j = await r.json().catch(() => ({}));
      if (!r.ok) throw new Error(j.error || 'Request failed');
      return j;
    },
  };

  // mobile nav
  document.addEventListener('click', (e) => {
    if (e.target.closest('[data-open-nav]')) document.body.classList.add('nav-open');
    if (e.target.closest('[data-close-nav]')) document.body.classList.remove('nav-open');
    // dropdowns
    const tog = e.target.closest('[data-dd-toggle]');
    $$('[data-dd].open').forEach((d) => { if (!tog || !d.contains(tog)) d.classList.remove('open'); });
    if (tog) tog.closest('[data-dd]').classList.toggle('open');
    // modal open / close
    const mo = e.target.closest('[data-modal]');
    if (mo) { e.preventDefault(); const d = document.getElementById(mo.dataset.modal); if (d) { d.showModal(); const f = d.querySelector('input:not([type=hidden]),textarea,select'); f && f.focus(); } }
    if (e.target.closest('[data-modal-close]')) e.target.closest('dialog').close();
    // clickable rows
    const row = e.target.closest('tr[data-href]');
    if (row && !e.target.closest('a,button,input,select,label,form')) window.location = row.dataset.href;
  });

  // confirm forms
  document.addEventListener('submit', (e) => {
    const f = e.target;
    if (e.defaultPrevented) return;
    if (f.dataset.confirm && !window.confirm(f.dataset.confirm)) { e.preventDefault(); return; }
    const b = f.querySelector('[type=submit]:not([formnovalidate])');
    if (b && !f.dataset.noLock) setTimeout(() => { b.disabled = true; }, 0);
  });

  // tabs
  $$('[data-tabs]').forEach((wrap) => {
    const btns = $$('[data-tab]', wrap);
    const show = (id, remember) => {
      btns.forEach((b) => b.classList.toggle('on', b.dataset.tab === id));
      $$('.tab-panel', wrap.parentElement).forEach((p) => p.classList.toggle('on', p.id === id));
      if (remember) { try { history.replaceState(null, '', '#' + id); } catch (_) {} }
    };
    btns.forEach((b) => b.addEventListener('click', () => show(b.dataset.tab, true)));
    const h = location.hash.slice(1);
    show(btns.some((b) => b.dataset.tab === h) ? h : (btns[0] && btns[0].dataset.tab));
  });

  // auto-submit filters
  $$('form[data-autosubmit] select').forEach((s) => s.addEventListener('change', () => s.form.submit()));

  // repeater rows (sub-jobs, request items, template items)
  document.addEventListener('click', (e) => {
    const add = e.target.closest('[data-repeat-add]');
    if (add) {
      const list = document.getElementById(add.dataset.repeatAdd);
      const tpl = document.getElementById(add.dataset.repeatAdd + '-tpl');
      const idx = Date.now().toString(36);
      list.insertAdjacentHTML('beforeend', tpl.innerHTML.replace(/__i__/g, idx));
      list.dispatchEvent(new Event('repeat:add'));
    }
    const rm = e.target.closest('[data-repeat-remove]');
    if (rm) { const row = rm.closest('[data-repeat-row]'); const list = row.parentElement; if (list.querySelectorAll('[data-repeat-row]').length > 1 || rm.dataset.allowEmpty) row.remove(); }
    const up = e.target.closest('[data-move]');
    if (up) { const row = up.closest('[data-repeat-row]'); if (up.dataset.move === 'up' && row.previousElementSibling) row.parentElement.insertBefore(row, row.previousElementSibling); if (up.dataset.move === 'down' && row.nextElementSibling) row.parentElement.insertBefore(row.nextElementSibling, row); }
  });

  // ---------------------------------------------------------------- inspector checklist
  const cl = $('#checklist-form');
  if (cl) {
    const state = $('#cl-save-state');
    let timer = null; let dirty = false;
    const recount = () => {
      const items = $$('.cl-item', cl);
      let pass = 0, fail = 0, na = 0, open = 0;
      items.forEach((it) => {
        const v = (it.querySelector('input[type=radio]:checked') || {}).value;
        it.classList.toggle('is-fail', v === 'fail');
        if (v === 'fail') it.classList.add('show-rem');
        if (v === 'pass') pass++; else if (v === 'fail') fail++; else if (v === 'na') na++; else open++;
      });
      const set = (id, v) => { const el = document.getElementById(id); if (el) el.textContent = v; };
      set('cl-pass', pass); set('cl-fail', fail); set('cl-na', na); set('cl-open', open);
      const pct = items.length ? Math.round(((items.length - open) / items.length) * 100) : 0;
      const bar = $('#cl-progress'); if (bar) bar.style.width = pct + '%';
      set('cl-pct', pct + '%');
    };
    const save = async () => {
      if (!dirty) return; dirty = false;
      state.className = 'save-state saving'; state.textContent = 'Saving…';
      try {
        const j = await IMS.post(cl.dataset.api, new FormData(cl));
        state.className = 'save-state saved'; state.textContent = 'All changes saved';
        if (j.stats) { const t = document.getElementById('tab-count-checklist'); if (t) { t.textContent = j.stats.answered + '/' + j.stats.total; t.className = 't-count ' + (j.stats.missing ? 'warn' : 'ok'); } }
      } catch (err) { dirty = true; state.className = 'save-state error'; state.textContent = err.message; }
    };
    const schedule = () => { dirty = true; state.className = 'save-state'; state.textContent = 'Unsaved changes'; clearTimeout(timer); timer = setTimeout(save, 900); };
    cl.addEventListener('change', (e) => { recount(); schedule(); });
    cl.addEventListener('input', (e) => { if (e.target.matches('input[type=text],input[type=date],textarea')) schedule(); });
    cl.addEventListener('click', (e) => {
      const t = e.target.closest('.rem-toggle'); if (t) { const it = t.closest('.cl-item'); it.classList.toggle('show-rem'); const i = it.querySelector('.rem input'); i && i.focus(); }
      const all = e.target.closest('[data-mark-all]');
      if (all) { const sec = all.dataset.markAll; $$('.cl-item' + (sec ? '[data-section="' + sec + '"]' : ''), cl).forEach((it) => { if (!it.querySelector('input[type=radio]:checked')) { const r = it.querySelector('input[value=pass]'); if (r) r.checked = true; } }); recount(); schedule(); }
    });
    cl.addEventListener('submit', (e) => { e.preventDefault(); dirty = true; save(); });
    window.addEventListener('beforeunload', (e) => { if (dirty) { save(); } });
    recount();
  }

  // certificate form autosave
  const cf = $('#certificate-form');
  if (cf && cf.dataset.api) {
    const st = $('#cf-save-state'); let tm = null;
    const go = async () => {
      st.className = 'save-state saving'; st.textContent = 'Saving…';
      try { await IMS.post(cf.dataset.api, new FormData(cf)); st.className = 'save-state saved'; st.textContent = 'All changes saved'; }
      catch (err) { st.className = 'save-state error'; st.textContent = err.message; }
    };
    cf.addEventListener('input', () => { st.className = 'save-state'; st.textContent = 'Unsaved changes'; clearTimeout(tm); tm = setTimeout(go, 1100); });
    cf.addEventListener('change', () => { clearTimeout(tm); tm = setTimeout(go, 300); });
  }

  // assignment: filter inspector select by qualification for chosen sub-jobs
  $$('[data-qualified-filter]').forEach((form) => {
    const sel = form.querySelector('select[name=inspector_id]');
    const update = () => {
      const types = $$('input[name="sub_job_ids[]"]:checked', form).map((c) => c.dataset.type);
      $$('option[data-skills]', sel).forEach((o) => {
        const skills = (o.dataset.skills || '').split(',');
        const ok = types.every((t) => skills.includes(t));
        o.disabled = !ok; o.textContent = o.dataset.label + (ok ? '' : ' — not qualified');
      });
      const n = types.length; const c = form.querySelector('[data-selected-count]'); if (c) c.textContent = n;
    };
    form.addEventListener('change', update); update();
    const all = form.querySelector('[data-check-all]');
    all && all.addEventListener('change', () => { $$('input[name="sub_job_ids[]"]:not(:disabled)', form).forEach((c) => (c.checked = all.checked)); update(); });
  });

  // client → equipment dependent select (job creation)
  const clientSel = $('[data-client-equipment]');
  if (clientSel) {
    const load = async () => {
      const id = clientSel.value; const list = window.__equipment = id ? await fetch(document.body.dataset.base + 'api/v1/clients/' + id + '/equipment', { headers: { Accept: 'application/json' } }).then((r) => r.json()).then((j) => j.data || []) : [];
      $$('select[data-equipment]').forEach((s) => fill(s, list));
    };
    const fill = (s, list) => {
      const cur = s.value;
      s.replaceChildren(new Option('— New / not registered —', ''));
      list.forEach((e) => { const o = new Option((e.equipment_no ? e.equipment_no + ' · ' : '') + e.description, e.id); o.dataset.type = e.inspection_type_id || ''; s.add(o); });
      s.value = cur;
    };
    clientSel.addEventListener('change', load);
    const rows = $('#subjobs'); rows && rows.addEventListener('repeat:add', () => { const s = $$('select[data-equipment]', rows).pop(); fill(s, window.__equipment || []); });
    if (clientSel.value) load();
    document.addEventListener('change', (e) => {
      if (!e.target.matches('select[data-equipment]')) return;
      const row = e.target.closest('[data-repeat-row]'); const nw = row.querySelector('[data-new-eq]');
      if (nw) nw.hidden = !!e.target.value;
      const t = e.target.selectedOptions[0] && e.target.selectedOptions[0].dataset.type; const ts = row.querySelector('select[name$="[inspection_type_id]"]');
      if (t && ts && !ts.value) ts.value = t;
    });
  }

  // searchable select: <select data-searchable> becomes a dropdown with a search box (the native select stays the source of truth)
  $$('select[data-searchable]').forEach((sel) => {
    const wrap = document.createElement('div'); wrap.className = 'ss';
    sel.parentNode.insertBefore(wrap, sel); wrap.appendChild(sel);
    sel.classList.add('ss-native'); sel.tabIndex = -1;
    const btn = document.createElement('button'); btn.type = 'button'; btn.className = 'select ss-btn'; btn.setAttribute('aria-haspopup', 'listbox');
    const pop = document.createElement('div'); pop.className = 'ss-pop'; pop.hidden = true;
    const q = document.createElement('input'); q.type = 'text'; q.className = 'input ss-q'; q.placeholder = 'Type to search…'; q.autocomplete = 'off'; q.setAttribute('aria-label', 'Search');
    const list = document.createElement('ul'); list.className = 'ss-list'; list.setAttribute('role', 'listbox');
    pop.append(q, list); wrap.append(btn, pop);
    let items = [], active = -1;
    const label = () => { const o = sel.selectedOptions[0]; btn.textContent = o ? o.textContent : ''; btn.classList.toggle('ss-empty', !sel.value); };
    const build = () => {
      const term = q.value.trim().toLowerCase().split(/\s+/).filter(Boolean);
      list.replaceChildren(); items = [];
      Array.from(sel.options).forEach((o) => {
        const t = o.textContent; const low = t.toLowerCase();
        if (term.length && o.value !== '' && !term.every((w) => low.includes(w))) return;
        if (term.length && o.value === '') return;
        const li = document.createElement('li'); li.setAttribute('role', 'option'); li.textContent = t; li.dataset.value = o.value;
        if (o.disabled) li.className = 'ss-dis';
        if (o.value === sel.value) li.classList.add('ss-sel');
        list.appendChild(li); items.push(li);
      });
      if (!items.length) { const li = document.createElement('li'); li.className = 'ss-none'; li.textContent = 'No matches'; list.appendChild(li); }
      setActive(items.findIndex((li) => li.classList.contains('ss-sel')) >= 0 && !term.length ? items.findIndex((li) => li.classList.contains('ss-sel')) : (items.length ? (term.length && items[0].dataset.value === '' ? Math.min(1, items.length - 1) : 0) : -1));
    };
    const setActive = (i) => {
      items.forEach((li) => li.classList.remove('ss-act')); active = i;
      if (items[i]) { items[i].classList.add('ss-act'); items[i].scrollIntoView({ block: 'nearest' }); }
    };
    const open = () => { if (sel.disabled) return; pop.hidden = false; wrap.classList.add('open'); q.value = ''; build(); q.focus(); };
    const close = (focusBtn) => { pop.hidden = true; wrap.classList.remove('open'); if (focusBtn) btn.focus(); };
    const choose = (li) => {
      if (!li || li.classList.contains('ss-dis')) return;
      const changed = sel.value !== li.dataset.value; sel.value = li.dataset.value; label(); close(true);
      sel.classList.remove('is-invalid');
      if (changed) sel.dispatchEvent(new Event('change', { bubbles: true }));
    };
    btn.addEventListener('click', () => (pop.hidden ? open() : close(true)));
    btn.addEventListener('keydown', (e) => { if (['ArrowDown', 'ArrowUp'].includes(e.key)) { e.preventDefault(); open(); } });
    q.addEventListener('input', build);
    q.addEventListener('keydown', (e) => {
      if (e.key === 'ArrowDown') { e.preventDefault(); setActive(Math.min(items.length - 1, active + 1)); }
      else if (e.key === 'ArrowUp') { e.preventDefault(); setActive(Math.max(0, active - 1)); }
      else if (e.key === 'Enter') { e.preventDefault(); choose(items[active]); }
      else if (e.key === 'Escape') { e.preventDefault(); close(true); }
      else if (e.key === 'Tab') close(false);
    });
    list.addEventListener('mousedown', (e) => e.preventDefault());
    list.addEventListener('click', (e) => choose(e.target.closest('li[data-value]')));
    list.addEventListener('mousemove', (e) => { const li = e.target.closest('li[data-value]'); if (li) { const i = items.indexOf(li); if (i !== active) setActive(i); } });
    document.addEventListener('click', (e) => { if (!pop.hidden && !wrap.contains(e.target)) close(false); });
    sel.addEventListener('invalid', () => { btn.classList.add('is-invalid'); });
    sel.addEventListener('change', () => { label(); btn.classList.remove('is-invalid'); });
    if (sel.form) sel.form.addEventListener('reset', () => setTimeout(label));
    label();
  });

  // demo login helper
  $$('[data-demo]').forEach((b) => b.addEventListener('click', () => { $('#email').value = b.dataset.demo; $('#password').focus(); }));
})();
