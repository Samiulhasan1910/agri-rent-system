/* AgriRent – offline support: network status, action queue with idempotent sync, cached catalogue */
(function () {
  'use strict';
  const A = window.Agri;
  const APP = window.APP || {};
  const t = A.t;
  const userKey = APP.user ? String(APP.user.id) : 'guest';
  const QUEUE_KEY = 'agri_queue_' + userKey;
  const CATALOG_KEY = 'agri_catalog_' + userKey;
  let syncing = false;

  function load(key, fallback) {
    try { return JSON.parse(localStorage.getItem(key)) || fallback; } catch (e) { return fallback; }
  }
  function save(key, value) {
    try { localStorage.setItem(key, JSON.stringify(value)); return true; } catch (e) { return false; }
  }

  function queueItems() { return load(QUEUE_KEY, []); }

  /** Store an action made while offline. Each item gets a UUID so the server never applies it twice. */
  function queue(type, payload, label) {
    const items = queueItems();
    items.push({ uuid: A.uuid(), type, payload, label: label || type, created_at: new Date().toISOString(), status: 'pending', error: '' });
    if (!save(QUEUE_KEY, items)) {
      A.toast('error', t('Could not save the request on this device. Please try again when online.'));
      return;
    }
    A.toast('info', t('Saved offline. It will sync automatically when you are back online.'));
    updateStatus();
  }

  function updateStatus(state) {
    const box = document.getElementById('netStatus');
    if (!box) return;
    const label = box.querySelector('[data-net-label]');
    const btn = box.querySelector('[data-sync-now]');
    const items = queueItems();
    const failed = items.filter((i) => i.status === 'failed').length;
    box.classList.toggle('offline', !navigator.onLine);
    box.classList.toggle('failed', navigator.onLine && (state === 'failed' || failed > 0));
    let text;
    if (!navigator.onLine) text = items.length ? t('Offline – :n pending', { n: items.length }) : t('Offline');
    else if (state === 'syncing') text = t('Syncing…');
    else if (failed) text = t('Sync failed (:n)', { n: failed });
    else if (items.length) text = t('Sync pending (:n)', { n: items.length });
    else if (state === 'success') text = t('Synced');
    else text = t('Online');
    if (label) label.textContent = text;
    box.title = text;
    if (btn) {
      btn.hidden = !items.length || !navigator.onLine || state === 'syncing';
      const bl = btn.querySelector('[data-sync-label]');
      if (bl) bl.textContent = failed ? t('Retry') : t('Sync');
    }
  }

  async function sync(manual) {
    if (syncing || !navigator.onLine || !APP.user) return;
    const items = queueItems();
    if (!items.length) { updateStatus(); return; }
    syncing = true;
    updateStatus('syncing');
    const fd = new FormData();
    fd.append('_token', A.csrf());
    fd.append('items', JSON.stringify(items.map((i) => ({ uuid: i.uuid, type: i.type, payload: i.payload }))));
    const data = await A.request('index.php?r=sync/push', { method: 'POST', body: fd, timeout: 30000 });
    syncing = false;
    if (!data.ok || !Array.isArray(data.results)) {
      items.forEach((i) => { i.status = 'failed'; i.error = data.message || t('Synchronization failed. Please retry.'); });
      save(QUEUE_KEY, items);
      updateStatus('failed');
      A.toast('error', t('Synchronization failed. Please retry.'));
      return;
    }
    const remaining = [];
    let done = 0;
    items.forEach((item) => {
      const result = data.results.find((r) => r.uuid === item.uuid);
      if (result && result.ok) {
        done++;
        A.toast('success', result.message || t('Synced'));
      } else {
        item.status = 'failed';
        item.error = result ? result.message : t('Synchronization failed. Please retry.');
        // validation failures will never succeed: keep them once so the user sees why, then drop on manual retry
        if (!(manual && item.error)) remaining.push(item);
        A.toast('error', item.label + ': ' + item.error);
      }
    });
    save(QUEUE_KEY, remaining);
    updateStatus(remaining.length ? 'failed' : 'success');
    if (done && !remaining.length) setTimeout(() => updateStatus(), 4000);
  }

  /* ------------------------------------------------------------------
   * Offline catalogue
   * ------------------------------------------------------------------ */
  async function cacheCatalog(force) {
    if (!APP.user || !navigator.onLine) return;
    const cached = load(CATALOG_KEY, null);
    if (!force && cached && Date.now() - cached.at < 10 * 60 * 1000) return;
    const data = await A.request('index.php?r=equipment/catalog');
    if (data.ok && Array.isArray(data.items)) save(CATALOG_KEY, { at: Date.now(), items: data.items });
  }

  function renderCatalog(form, target) {
    const cached = load(CATALOG_KEY, null);
    if (!cached || !cached.items) {
      target.innerHTML = '<div class="alert alert-warning">' + A.escapeHtml(t('You are offline and no saved equipment list is available yet.')) + '</div>';
      return;
    }
    const f = Object.fromEntries(new FormData(form));
    const q = (f.q || '').toLowerCase();
    let items = cached.items.filter((it) =>
      (!q || (it.name + ' ' + (it.specs || '') + ' ' + it.category).toLowerCase().includes(q)) &&
      (!f.category || String(it.category_id) === String(f.category)) &&
      (!f.location || it.location.toLowerCase().includes(String(f.location).toLowerCase())) &&
      (!f.min_price || (it.price || 0) >= Number(f.min_price)) &&
      (!f.max_price || (it.price || 0) <= Number(f.max_price)) &&
      (!f.availability || (f.availability === 'available') === it.available));
    const when = new Date(cached.at).toLocaleString();
    let html = '<div class="alert alert-warning"><span>' + A.escapeHtml(t('Offline mode – showing saved equipment from :time.', { time: when })) + '</span></div>';
    if (!items.length) {
      html += '<div class="empty-state"><h3>' + A.escapeHtml(t('No equipment found')) + '</h3></div>';
    } else {
      html += '<div class="equipment-grid">' + items.map((it) => '<article class="eq-card">' +
        '<a class="eq-media" href="index.php?r=equipment/show&id=' + it.id + '"><img src="' + A.escapeHtml(it.image) + '" alt="" onerror="this.src=\'assets/img/equipment/tractor.svg\'">' +
        '<span class="eq-status"><span class="badge badge-' + (it.available ? 'green' : 'red') + '">' + A.escapeHtml(it.available ? t('Available') : t('Not Available')) + '</span></span></a>' +
        (APP.user.role === 'farmer' ? '<button class="heart" type="button" data-wishlist="' + it.id + '" data-saved="0" aria-label="' + A.escapeHtml(t('Save to wishlist')) + '"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg></button>' : '') +
        '<div class="eq-body"><div class="eq-title"><h3>' + A.escapeHtml(it.name) + '</h3></div><p class="eq-specs">' + A.escapeHtml(it.specs || it.category) + '</p>' +
        '<div class="eq-price"><strong>৳' + (it.price ? Number(it.price).toLocaleString() : '—') + '</strong><span>/' + A.escapeHtml(t('day')) + '</span></div>' +
        '<div class="eq-meta"><span>' + A.escapeHtml(it.location) + '</span><span class="rating">★ ' + it.rating + '</span></div></div></article>').join('') + '</div>';
    }
    target.innerHTML = html;
  }

  /* ------------------------------------------------------------------
   * Wiring
   * ------------------------------------------------------------------ */
  window.addEventListener('online', () => { updateStatus(); A.toast('success', t('You are back online.')); setTimeout(() => sync(false), 800); });
  window.addEventListener('offline', () => { updateStatus(); A.toast('warning', t('You are offline. Basic features remain available; actions will be synced later.')); });

  document.addEventListener('click', (e) => {
    if (e.target.closest('[data-sync-now]')) sync(true);
  });

  document.addEventListener('DOMContentLoaded', () => {
    updateStatus();
    if (navigator.onLine) setTimeout(() => sync(false), 1200);
    if (document.querySelector('[data-offline-catalog]')) {
      if (navigator.onLine) cacheCatalog(false);
      else {
        const form = document.querySelector('[data-offline-catalog]');
        renderCatalog(form, document.querySelector(form.dataset.target));
      }
    }
    if ('serviceWorker' in navigator && location.protocol !== 'file:') {
      navigator.serviceWorker.register('service-worker.js').catch(() => {});
    }
  });

  A.offline = { queue, sync, renderCatalog, cacheCatalog, items: queueItems };
})();
