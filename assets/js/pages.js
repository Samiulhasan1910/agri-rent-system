/* AgriRent – page specific behaviour */
(function () {
  'use strict';
  const A = window.Agri;
  const { t, $, $$, request, toast, escapeHtml } = A;
  const money = (v) => '৳' + Number(v || 0).toLocaleString('en-US', { maximumFractionDigits: 2 });

  /* ------------------------------------------------------------------
   * Equipment details: live price & availability quote
   * ------------------------------------------------------------------ */
  function initQuote(form) {
    const start = form.elements.start_date;
    const end = form.elements.end_date;
    const btn = $('[data-book-btn]', form);
    const offlineBtn = $('[data-offline-book]', form);
    const out = (key) => $('[data-q="' + key + '"]', form);
    const verified = !window.APP.user || window.APP.user.verified;
    const initiallyDisabled = btn && btn.disabled;
    let seq = 0;

    const setMsg = (text, cls) => { const m = out('message'); m.textContent = text; m.className = 'quote-msg ' + (cls || ''); };
    const reset = () => { ['days', 'rate', 'delivery', 'total'].forEach((k) => { out(k).textContent = '—'; }); };

    async function update() {
      if (start.value) end.min = start.value;
      if (!start.value || !end.value) { reset(); setMsg(t('Choose your dates to see the total price and availability.')); if (btn) btn.disabled = true; return; }
      if (end.value < start.value) { reset(); setMsg(t('End date cannot be earlier than start date.'), 'error'); if (btn) btn.disabled = true; return; }
      if (!navigator.onLine) {
        setMsg(t('You are offline. You can save this booking request and it will be sent when you reconnect.'), 'error');
        if (btn) btn.disabled = true;
        if (offlineBtn && verified) offlineBtn.hidden = false;
        return;
      }
      if (offlineBtn) offlineBtn.hidden = true;
      const id = ++seq;
      setMsg(t('Checking availability…'));
      const params = new URLSearchParams({ r: 'equipment/quote', equipment_id: form.dataset.equipment, start_date: start.value, end_date: end.value });
      const q = await request('index.php?' + params);
      if (id !== seq) return;
      if (!q.ok) { reset(); setMsg(q.message || t('Unable to calculate price. Please try again.'), 'error'); if (btn) btn.disabled = true; return; }
      out('days').textContent = q.days + ' ' + t('days');
      out('rate').textContent = money(q.price_per_day) + (q.season !== 'normal' ? ' (' + q.season_label + ')' : '');
      out('delivery').textContent = money(q.delivery_charge);
      out('total').textContent = money(q.total);
      let ok = true;
      if (q.block_reason) { setMsg(q.block_reason, 'error'); ok = false; }
      else if (q.maintenance) { setMsg(t('Maintenance is scheduled during these dates. Please choose other dates.'), 'error'); ok = false; }
      else if (q.available_units < 1) { setMsg(t('Fully booked for these dates. Please choose other dates.'), 'error'); ok = false; }
      else setMsg(t(':n unit(s) available for this period.', { n: q.available_units }), 'ok');
      if (btn) btn.disabled = !ok || initiallyDisabled && !verified;
    }
    start.addEventListener('change', update);
    end.addEventListener('change', update);
    if (offlineBtn) {
      offlineBtn.addEventListener('click', () => {
        if (!start.value || !end.value) { toast('error', t('Please select a valid start and end date.')); return; }
        A.offline.queue('booking', { equipment_id: form.dataset.equipment, start_date: start.value, end_date: end.value }, t('Booking request'));
        offlineBtn.hidden = true;
      });
    }
    window.addEventListener('online', update);
    window.addEventListener('offline', update);
    update();
  }

  /* ------------------------------------------------------------------
   * Rental extension calculator
   * ------------------------------------------------------------------ */
  function initExtension(form) {
    const input = form.elements.new_end_date;
    const btn = $('[data-extend-btn]', form);
    const set = (k, v) => { const el = $('[data-x="' + k + '"]', form); if (el.tagName === 'INPUT') el.value = v; else el.textContent = v; };
    input.addEventListener('change', async () => {
      if (!input.value) return;
      set('message', t('Checking availability…'));
      const q = await request('index.php?' + new URLSearchParams({ r: 'booking/extension-quote', id: form.dataset.extension, new_end_date: input.value }));
      const msg = $('[data-x="message"]', form);
      if (!q.ok) {
        set('extra_days', '—'); set('extra_cost', '৳ —');
        msg.textContent = q.message; msg.className = 'quote-msg error';
        btn.disabled = true;
        return;
      }
      set('extra_days', q.extra_days + ' ' + t('days'));
      set('extra_cost', money(q.extra_cost));
      msg.textContent = t('New duration: :d days · New total: :total', { d: q.new_days, total: money(q.new_total) });
      msg.className = 'quote-msg ok';
      btn.disabled = false;
    });
  }

  /* ------------------------------------------------------------------
   * Payment: coupon, card formatting, wallet OTP
   * ------------------------------------------------------------------ */
  function initPayment(form) {
    const applyBtn = $('[data-apply-coupon]', form);
    if (applyBtn) {
      applyBtn.addEventListener('click', async () => {
        const code = form.elements.coupon.value.trim();
        const msg = $('[data-coupon-msg]', form);
        A.clearErrors(form);
        if (!code) { A.showErrors(form, { coupon: t('Please enter a coupon code.') }); return; }
        const fd = new FormData();
        fd.append('_token', A.csrf()); fd.append('booking', applyBtn.dataset.booking); fd.append('code', code);
        A.setLoading(applyBtn, true);
        const r = await request(applyBtn.dataset.url, { method: 'POST', body: fd });
        A.setLoading(applyBtn, false);
        if (!r.ok) {
          A.showErrors(form, r.errors || { coupon: r.message });
          msg.textContent = '';
          $$('[data-pay-amount]').forEach((el) => { el.textContent = money(r.amount); });
          $('[data-discount-row]') && ($('[data-discount-row]').hidden = true);
          return;
        }
        msg.textContent = r.message; msg.className = 'hint strong';
        $$('[data-pay-amount]').forEach((el) => { el.textContent = money(r.amount); });
        const row = $('[data-discount-row]');
        if (row) { row.hidden = false; $('[data-discount]').textContent = '- ' + money(r.discount) + ' (' + r.code + ')'; }
      });
    }
    const card = $('[data-card-number]', form);
    if (card) card.addEventListener('input', () => { card.value = card.value.replace(/\D/g, '').slice(0, 19).replace(/(.{4})/g, '$1 ').trim(); });
    const exp = $('[data-expiry]', form);
    if (exp) exp.addEventListener('input', () => { const d = exp.value.replace(/\D/g, '').slice(0, 4); exp.value = d.length > 2 ? d.slice(0, 2) + '/' + d.slice(2) : d; });
    const otpBtn = $('[data-send-otp]', form);
    if (otpBtn) {
      otpBtn.addEventListener('click', async () => {
        const fd = new FormData();
        fd.append('_token', A.csrf()); fd.append('wallet_number', form.elements.wallet_number.value);
        A.clearErrors(form);
        const r = await request('index.php?r=payment/send-otp', { method: 'POST', body: fd });
        if (!r.ok) { A.showErrors(form, r.errors); toast('error', r.message); return; }
        toast('info', r.message, 12000);
        A.startCountdown(otpBtn, parseInt(otpBtn.dataset.countdown, 10) || 30);
        form.elements.otp.focus();
      });
    }
  }

  /* ------------------------------------------------------------------
   * Wishlist hearts (AJAX, works offline through the queue)
   * ------------------------------------------------------------------ */
  document.addEventListener('click', async (e) => {
    const heart = e.target.closest('[data-wishlist]');
    if (!heart) return;
    e.preventDefault();
    const saved = heart.dataset.saved === '1';
    const id = heart.dataset.wishlist;
    const apply = (state) => { heart.dataset.saved = state ? '1' : '0'; $$('[data-wishlist="' + id + '"]').forEach((h) => { h.classList.toggle('on', state); h.dataset.saved = state ? '1' : '0'; }); };
    if (!navigator.onLine) {
      A.offline.queue('wishlist', { equipment_id: id, remove: saved ? 1 : 0 }, t('Wishlist'));
      apply(!saved);
      return;
    }
    const fd = new FormData();
    fd.append('_token', A.csrf()); fd.append('equipment_id', id);
    heart.disabled = true;
    const r = await request('index.php?r=wishlist/' + (saved ? 'remove' : 'add'), { method: 'POST', body: fd });
    heart.disabled = false;
    if (r.ok) { apply(!saved); toast('success', r.message); }
    else { if (r.saved) apply(true); toast('error', r.message); }
  });

  /* ------------------------------------------------------------------
   * Chat (send + poll every 4 seconds)
   * ------------------------------------------------------------------ */
  function initChat() {
    const box = $('#chatMessages');
    const form = $('[data-chat-form]');
    if (!box || !form) return;
    const scroll = () => { box.scrollTop = box.scrollHeight; };
    scroll();
    const append = (html) => {
      if (!html) return;
      const empty = $('.chat-empty', box);
      if (empty) empty.remove();
      box.insertAdjacentHTML('beforeend', html);
      scroll();
    };
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      const input = form.elements.message;
      if (!input.value.trim()) return;
      const fd = new FormData(form);
      input.value = '';
      const r = await request(form.action, { method: 'POST', body: fd });
      if (!r.ok) { toast('error', r.message); input.value = fd.get('message'); return; }
      append(r.html);
      box.dataset.last = Math.max(Number(box.dataset.last), r.id);
    });
    setInterval(async () => {
      if (document.hidden || !navigator.onLine) return;
      const r = await request('index.php?' + new URLSearchParams({ r: 'chat/poll', with: box.dataset.chatWith, after: box.dataset.last }));
      if (r.ok && r.html) {
        const tmp = document.createElement('div');
        tmp.innerHTML = r.html;
        $$('[data-msg]', tmp).forEach((m) => { if (!box.querySelector('[data-msg="' + m.dataset.msg + '"]')) append(m.outerHTML); });
        box.dataset.last = r.last;
      }
    }, 4000);
  }

  /* ------------------------------------------------------------------
   * Smart equipment matching (refreshes as inputs change)
   * ------------------------------------------------------------------ */
  function initSmart(form) {
    const results = $('#smartResults');
    const count = $('[data-smart-count]');
    const placeholder = (msg, cls) => {
      results.className = 'smart-results ' + (cls || 'is-inactive');
      results.innerHTML = '<div class="smart-placeholder"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"/></svg><p>' + escapeHtml(msg) + '</p></div>';
      if (count) count.textContent = '';
    };
    let seq = 0;
    const run = A.debounce(async () => {
      A.clearErrors(form);
      const params = new URLSearchParams(new FormData(form));
      const id = ++seq;
      const r = await request('index.php?' + params);
      if (id !== seq) return;
      if (!r.ok) {
        A.showErrors(form, r.errors);
        placeholder(r.message || t('Farm size is required'), 'is-error');
        return;
      }
      if (r.state === 'results') {
        results.className = 'smart-results';
        results.innerHTML = r.html;
        if (count) count.textContent = t(':n matches', { n: r.count });
      } else {
        placeholder(r.message, r.state === 'empty' ? 'is-error' : 'is-inactive');
      }
    }, 300);
    form.addEventListener('input', run);
    form.addEventListener('change', run);
    form.addEventListener('submit', (e) => { e.preventDefault(); run(); });
    $$('[data-fill-crop]').forEach((chip) => chip.addEventListener('click', () => { form.elements.crop.value = chip.dataset.fillCrop; run(); }));
  }

  /* ------------------------------------------------------------------
   * Emergency request with GPS location detection
   * ------------------------------------------------------------------ */
  function initEmergency(form) {
    const status = $('[data-loc-status]', form);
    const fallback = $('[data-district-fallback]', form);
    const result = $('#emergencyResult');
    const btn = $('.emergency-btn', form);
    const setStatus = (text, cls) => { status.className = 'loc-status ' + (cls || ''); status.querySelector('span').textContent = text; };

    const locate = () => new Promise((resolve) => {
      if (!('geolocation' in navigator)) { resolve(null); return; }
      setStatus(t('Detecting your location…'));
      navigator.geolocation.getCurrentPosition(
        (pos) => resolve({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
        () => resolve(null),
        { enableHighAccuracy: true, timeout: 12000, maximumAge: 60000 });
    });

    async function send() {
      if (!form.elements.category_id.value) { A.showErrors(form, { category_id: t('Please select the equipment type you need.') }); return; }
      if (!form.elements.latitude.value && !form.elements.district.value) {
        const loc = await locate();
        if (loc) {
          form.elements.latitude.value = loc.lat.toFixed(6);
          form.elements.longitude.value = loc.lng.toFixed(6);
          setStatus(t('Location detected (:lat, :lng)', { lat: loc.lat.toFixed(4), lng: loc.lng.toFixed(4) }), 'ok');
        } else {
          setStatus(t('Please enable GPS/location services in your browser, or choose your district below.'), 'error');
          fallback.hidden = false;
          toast('warning', t('Please enable GPS/location services in your browser, or choose your district below.'));
          return;
        }
      }
      if (!form.elements.client_uuid.value) form.elements.client_uuid.value = A.uuid();
      const fd = new FormData(form);
      if (!navigator.onLine) {
        const payload = {};
        fd.forEach((v, k) => { if (k !== '_token') payload[k] = v; });
        A.offline.queue('emergency', payload, t('Emergency request'));
        return;
      }
      A.setLoading(btn, true);
      const r = await request(form.action, { method: 'POST', body: fd });
      A.setLoading(btn, false);
      if (r.ok) {
        result.innerHTML = r.html;
        toast('success', r.message, 9000);
        form.elements.client_uuid.value = '';
        return;
      }
      A.showErrors(form, r.errors);
      if (r.code === 'location_required') { fallback.hidden = false; setStatus(r.message, 'error'); }
      const retry = r.network || r.code === 'retry';
      result.innerHTML = '<div class="alert alert-' + (r.code === 'none_nearby' ? 'warning' : 'danger') + '"><span>' + escapeHtml(r.message) + '</span>' +
        (retry ? ' <button type="button" class="btn btn-outline btn-xs" data-emergency-retry>' + escapeHtml(t('Retry')) + '</button>' : '') + '</div>';
      toast('error', r.message);
    }
    form.addEventListener('submit', (e) => { e.preventDefault(); send(); });
    result.addEventListener('click', (e) => { if (e.target.closest('[data-emergency-retry]')) send(); });
    form.elements.district.addEventListener('change', () => { form.elements.latitude.value = ''; form.elements.longitude.value = ''; });
  }

  /* ------------------------------------------------------------------
   * Analytics dashboard (validated filters, charts, auto refresh)
   * ------------------------------------------------------------------ */
  function initAnalytics(form) {
    const errorBox = $('[data-analytics-error]');
    const emptyBox = $('[data-analytics-empty]');
    let timer = null;
    async function load() {
      errorBox.hidden = true;
      const from = form.elements.from.value;
      const to = form.elements.to.value;
      if (!from || !to || to < from) {
        errorBox.textContent = t('End date cannot be earlier than start date.');
        errorBox.hidden = false;
        return;
      }
      const params = new URLSearchParams(new FormData(form));
      const r = await request(form.dataset.analytics + '&' + params.toString());
      if (!r.ok) { errorBox.textContent = r.message; errorBox.hidden = false; return; }
      Object.keys(r.summary).forEach((k) => { const el = $('[data-kpi="' + k + '"]'); if (el) el.textContent = r.summary[k]; });
      emptyBox.hidden = !r.empty;
      Charts.line($('#revenueChart'), r.series.labels, r.series.revenue, { prefix: '৳' });
      Charts.bar($('#bookingChart'), r.series.labels, r.series.bookings, { color: '#2563eb', label: t('Bookings') });
      Charts.donut($('#usageChart'), r.usage, { centerLabel: t('days') });
      Charts.donut($('#statusChart'), r.statuses, { centerLabel: t('bookings') });
      const stamp = $('[data-refreshed]');
      if (stamp) stamp.textContent = t('Updated :time', { time: r.refreshed_at });
    }
    form.addEventListener('submit', (e) => { e.preventDefault(); load(); });
    const auto = $('[data-auto-refresh]', form);
    const schedule = () => { clearInterval(timer); if (auto && auto.checked) timer = setInterval(() => { if (!document.hidden) load(); }, 60000); };
    if (auto) auto.addEventListener('change', schedule);
    load();
    schedule();
  }

  /* ------------------------------------------------------------------
   * Modal helpers for add/edit forms
   * ------------------------------------------------------------------ */
  function fillForm(form, data) {
    Object.keys(data).forEach((k) => {
      const field = form.elements[k];
      if (field && field.type !== 'file') field.value = data[k] === null ? '' : data[k];
    });
  }
  document.addEventListener('click', async (e) => {
    const editCat = e.target.closest('[data-category-edit], [data-category-new]');
    if (editCat) {
      const form = $('[data-category-form]');
      form.reset(); A.clearErrors(form);
      form.elements.id.value = '';
      if (editCat.dataset.categoryEdit) {
        fillForm(form, JSON.parse(editCat.dataset.categoryEdit));
        form.action = 'index.php?r=category/update';
        $('[data-modal-title]', form.closest('.modal')).textContent = t('Edit category');
      } else {
        form.action = 'index.php?r=category/store';
        $('[data-modal-title]', form.closest('.modal')).textContent = t('Add category');
      }
    }
    const coupon = e.target.closest('[data-coupon-edit], [data-coupon-new]');
    if (coupon) {
      const form = $('[data-coupon-form]');
      form.reset(); A.clearErrors(form); form.elements.id.value = '';
      if (coupon.dataset.couponEdit) fillForm(form, JSON.parse(coupon.dataset.couponEdit));
      $('[data-modal-title]', form.closest('.modal')).textContent = coupon.dataset.couponEdit ? t('Edit coupon') : t('Add coupon');
    }
    const team = e.target.closest('[data-team-edit], [data-team-new]');
    if (team) {
      const form = $('[data-team-form]');
      form.reset(); A.clearErrors(form); form.elements.id.value = '';
      if (team.dataset.teamEdit) fillForm(form, JSON.parse(team.dataset.teamEdit));
      $('[data-modal-title]', form.closest('.modal')).textContent = team.dataset.teamEdit ? t('Edit delivery team') : t('Add delivery team');
    }
    const complaint = e.target.closest('[data-complaint-edit]');
    if (complaint) {
      const form = $('[data-complaint-form]');
      const detail = $('[data-complaint-detail]', form);
      detail.textContent = t('Loading…');
      const r = await request(complaint.dataset.complaintEdit);
      if (!r.ok) { detail.textContent = r.message; return; }
      const c = r.complaint;
      form.elements.id.value = c.id;
      form.elements.status.value = c.status;
      form.elements.response.value = c.admin_response || '';
      detail.innerHTML = '<p><b>' + escapeHtml(c.ticket_no) + '</b> · ' + escapeHtml(c.category) + ' · ' + escapeHtml(c.created_at) + '</p><p><b>' + escapeHtml(c.subject) + '</b></p><p>' + escapeHtml(c.description) + '</p><p class="muted">' + escapeHtml(c.user_name) + (c.booking_code ? ' · #' + escapeHtml(c.booking_code) : '') + '</p>';
    }

    // side panels (delivery / verification)
    const row = e.target.closest('[data-panel-url]');
    if (row && !e.target.closest('form, a')) {
      const panel = $(row.dataset.panelTarget || '#deliveryPanel');
      if (!panel) return;
      $$('[data-panel-url]').forEach((r) => r.classList.toggle('is-selected', r === row));
      $$('[data-panel-url]').forEach((r) => r.classList.toggle('active', r === row));
      panel.innerHTML = '<p class="muted center">' + escapeHtml(t('Loading…')) + '</p>';
      const r = await request(row.dataset.panelUrl);
      panel.innerHTML = r.ok ? r.html : '<div class="alert alert-danger">' + escapeHtml(r.message || t('Failed to load delivery progress.')) + '</div>';
      if (window.innerWidth < 1024) panel.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    const thumb = e.target.closest('[data-gallery-thumb]');
    if (thumb) {
      $('[data-gallery-main]').src = thumb.dataset.galleryThumb;
      $$('[data-gallery-thumb]').forEach((b) => b.classList.toggle('active', b === thumb));
    }
  });

  document.addEventListener('ajax:success', (e) => {
    const form = e.target;
    if (form.hasAttribute('data-complaint-form') && e.detail.id) {
      const cell = $('[data-row="cmp-' + e.detail.id + '"] [data-status-cell]');
      if (cell) cell.innerHTML = e.detail.badge;
    }
  });

  /* ------------------------------------------------------------------
   * Boot
   * ------------------------------------------------------------------ */
  document.addEventListener('DOMContentLoaded', () => {
    $$('form[data-quote]').forEach(initQuote);
    $$('form[data-extension]').forEach(initExtension);
    $$('form[data-payment-form]').forEach(initPayment);
    $$('form[data-smart]').forEach(initSmart);
    $$('form[data-emergency]').forEach(initEmergency);
    $$('form[data-analytics]').forEach(initAnalytics);
    initChat();
  });
})();
