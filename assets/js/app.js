/* AgriRent – core client: AJAX, forms, toasts, modals, live filters, notifications */
(function () {
  'use strict';

  const APP = window.APP || {};
  const BN = window.AGRI_BN || {};
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  /** Translate an English UI string (Bangla dictionary loaded only for Bangla users). */
  function t(text, params) {
    let out = (APP.lang === 'bn' && BN[text]) || text;
    if (params) Object.keys(params).forEach((k) => { out = out.split(':' + k).join(params[k]); });
    return out;
  }

  function csrf() {
    const meta = $('meta[name="csrf-token"]');
    return meta ? meta.content : APP.csrf;
  }

  function uuid() {
    if (window.crypto && crypto.randomUUID) return crypto.randomUUID();
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
      const r = Math.random() * 16 | 0;
      return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
    });
  }

  function debounce(fn, wait) {
    let timer;
    return function (...args) { clearTimeout(timer); timer = setTimeout(() => fn.apply(this, args), wait); };
  }

  function escapeHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  }

  /** fetch wrapper that always resolves to {ok, message, status, ...} */
  async function request(url, opts = {}) {
    const method = opts.method || 'GET';
    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort(), opts.timeout || 20000);
    const headers = { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' };
    if (method !== 'GET') headers['X-CSRF-Token'] = csrf();
    try {
      const res = await fetch(url, { method, body: opts.body || null, headers, credentials: 'same-origin', signal: controller.signal, cache: 'no-store' });
      const type = res.headers.get('content-type') || '';
      let data;
      if (type.includes('application/json')) {
        data = await res.json();
      } else {
        const text = await res.text();
        data = { ok: res.ok, html: text, message: res.ok ? '' : t('Something went wrong. Please try again.') };
      }
      if (typeof data.ok === 'undefined') data.ok = res.ok;
      data.status = res.status;
      return data;
    } catch (err) {
      return {
        ok: false, network: true, status: 0,
        message: navigator.onLine ? t('The server is not responding. Please try again.') : t('You are offline. Please check your internet connection.'),
      };
    } finally {
      clearTimeout(timer);
    }
  }

  /* ------------------------------------------------------------------
   * Toasts
   * ------------------------------------------------------------------ */
  const ICONS = {
    success: '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m9 12 2 2 4-4"/></svg>',
    error: '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6M9 9l6 6"/></svg>',
    warning: '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4M12 17h.01"/></svg>',
    info: '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>',
  };

  function dismissToast(el) {
    el.classList.add('hide');
    setTimeout(() => el.remove(), 260);
  }

  function toast(type, message, timeout) {
    if (!message) return;
    let stack = $('#toasts');
    if (!stack) {
      stack = document.createElement('div');
      stack.id = 'toasts';
      stack.className = 'toast-stack';
      document.body.appendChild(stack);
    }
    const el = document.createElement('div');
    el.className = 'toast toast-' + (ICONS[type] ? type : 'info');
    el.setAttribute('role', type === 'error' ? 'alert' : 'status');
    el.innerHTML = (ICONS[type] || ICONS.info) + '<span>' + escapeHtml(message) + '</span><button type="button" class="toast-close" aria-label="' + escapeHtml(t('Close')) + '">×</button>';
    stack.appendChild(el);
    setTimeout(() => dismissToast(el), timeout || (type === 'error' ? 7000 : 4500));
  }

  /** Reload the page and show a toast afterwards. */
  function reloadWithToast(type, message) {
    try { sessionStorage.setItem('agri_toast', JSON.stringify([type, message])); } catch (e) { /* ignore */ }
    location.reload();
  }

  /* ------------------------------------------------------------------
   * Modals, confirm & prompt dialogs
   * ------------------------------------------------------------------ */
  let lastFocus = null;
  function openModal(modal) {
    if (typeof modal === 'string') modal = $(modal);
    if (!modal) return;
    lastFocus = document.activeElement;
    modal.hidden = false;
    const focusable = modal.querySelector('input:not([type=hidden]):not([disabled]), select, textarea, button:not([data-modal-close])');
    if (focusable) setTimeout(() => focusable.focus(), 30);
  }
  function closeModal(modal) {
    if (typeof modal === 'string') modal = $(modal);
    if (!modal) return;
    modal.hidden = true;
    if (lastFocus && lastFocus.focus) lastFocus.focus();
  }

  function confirmDialog(text, okLabel) {
    const modal = $('#confirmModal');
    if (!modal) return Promise.resolve(window.confirm(text));
    $('[data-confirm-text]', modal).textContent = text;
    $('[data-confirm-ok]', modal).textContent = okLabel || t('Confirm');
    openModal(modal);
    return new Promise((resolve) => {
      const done = (value) => {
        closeModal(modal);
        ok.removeEventListener('click', onOk);
        cancel.removeEventListener('click', onCancel);
        resolve(value);
      };
      const ok = $('[data-confirm-ok]', modal);
      const cancel = $('[data-confirm-cancel]', modal);
      const onOk = () => done(true);
      const onCancel = () => done(false);
      ok.addEventListener('click', onOk);
      cancel.addEventListener('click', onCancel);
    });
  }

  function promptDialog(label, optional) {
    return new Promise((resolve) => {
      const modal = document.createElement('div');
      modal.className = 'modal';
      modal.innerHTML = '<div class="modal-card modal-sm"><h3>' + escapeHtml(label) + '</h3>' +
        '<textarea rows="3" maxlength="255" style="margin:8px 0"></textarea><small class="field-error"></small>' +
        '<div class="modal-actions"><button type="button" class="btn btn-light" data-x="cancel">' + escapeHtml(t('Cancel')) + '</button>' +
        '<button type="button" class="btn btn-primary" data-x="ok">' + escapeHtml(t('Submit')) + '</button></div></div>';
      document.body.appendChild(modal);
      const area = $('textarea', modal);
      area.focus();
      const finish = (value) => { modal.remove(); resolve(value); };
      modal.addEventListener('click', (e) => {
        if (e.target === modal || e.target.dataset.x === 'cancel') finish(null);
        if (e.target.dataset.x === 'ok') {
          const value = area.value.trim();
          if (!value && !optional) { $('.field-error', modal).textContent = t('This field is required.'); return; }
          finish(value);
        }
      });
    });
  }

  /* ------------------------------------------------------------------
   * Form errors & loading state
   * ------------------------------------------------------------------ */
  function fieldFor(form, name) {
    let el = form.elements ? form.elements[name] : null;
    if (!el && form.id) el = document.querySelector('[form="' + form.id + '"][name="' + name + '"]');
    if (el && el.length && !el.tagName) el = el[0];
    return el || null;
  }

  function clearErrors(form) {
    $$('[data-error-for]', form).forEach((el) => { el.textContent = ''; });
    $$('.is-invalid', form).forEach((el) => el.classList.remove('is-invalid'));
    if (form.id) $$('[form="' + form.id + '"].is-invalid').forEach((el) => el.classList.remove('is-invalid'));
  }

  function showErrors(form, errors) {
    if (!errors) return;
    let first = null;
    Object.keys(errors).forEach((name) => {
      const input = fieldFor(form, name);
      let holder = $('[data-error-for="' + name + '"]', form);
      if (!holder && input) {
        holder = document.createElement('small');
        holder.className = 'field-error';
        holder.dataset.errorFor = name;
        const anchor = input.closest('.input-icon, .input-with-btn, .otp-boxes') || input;
        anchor.insertAdjacentElement('afterend', holder);
      }
      if (holder) holder.textContent = errors[name];
      if (input && input.classList) {
        input.classList.add('is-invalid');
        if (!first) first = input;
      }
    });
    if (first && first.focus && first.type !== 'hidden') first.focus({ preventScroll: false });
  }

  function setLoading(btn, loading) {
    if (!btn) return;
    btn.classList.toggle('is-loading', loading);
    btn.disabled = loading;
  }

  function removeRow(key) {
    $$('[data-row="' + key + '"]').forEach((row) => {
      row.style.transition = 'opacity .25s';
      row.style.opacity = '0';
      setTimeout(() => row.remove(), 260);
    });
  }

  function htmlToElement(html) {
    const tpl = document.createElement('template');
    tpl.innerHTML = String(html).trim();
    return tpl.content.firstElementChild;
  }

  /** Applies the standard JSON response contract to a form. */
  function handleResponse(form, data) {
    form.dispatchEvent(new CustomEvent(data.ok ? 'ajax:success' : 'ajax:error', { detail: data, bubbles: true }));
    if (!data.ok) {
      showErrors(form, data.errors);
      toast('error', data.message || t('Something went wrong. Please try again.'));
      if (data.status === 419) toast('warning', t('Please refresh the page and try again.'));
      if (data.redirect) setTimeout(() => { location.href = data.redirect; }, 1300);
      return;
    }
    if (data.redirect) { location.href = data.redirect; return; }
    const ds = form.dataset;
    if (ds.reload !== undefined) { reloadWithToast('success', data.message); return; }
    toast('success', data.message);
    if (ds.reset !== undefined) form.reset();
    if (ds.closeModal !== undefined || form.closest('.modal')) closeModal(form.closest('.modal'));
    if (ds.removeRow) removeRow(ds.removeRow);
    if (ds.replaceRow && data.row) {
      $$('[data-row="' + ds.replaceRow + '"]').forEach((row) => { const el = htmlToElement(data.row); if (el) row.replaceWith(el); });
    }
    if (ds.replaceSelf !== undefined && data.html) form.outerHTML = data.html;
    if (ds.prependRow && data.row) {
      const target = $(ds.prependRow);
      const el = target ? htmlToElement(target.tagName === 'TBODY' ? '<table><tbody>' + data.row + '</tbody></table>' : data.row) : null;
      const row = el && el.tagName === 'TABLE' ? el.querySelector('tr') : el;
      if (target && row) target.prepend(row);
    }
    if (ds.panelUpdate !== undefined && data.html) {
      const panel = form.closest('.side-panel');
      if (panel) panel.innerHTML = data.html;
      if (data.row && data.id) {
        const row = $('[data-row="delivery-' + data.id + '"]');
        const el = htmlToElement('<table><tbody>' + data.row + '</tbody></table>');
        if (row && el) { const tr = el.querySelector('tr'); tr.classList.add('is-selected'); row.replaceWith(tr); }
      }
    }
    if (ds.onSuccess === 'notifications-read') {
      $$('[data-notif-count]').forEach((b) => { b.hidden = true; });
      $$('.dropdown-notif.unread').forEach((n) => n.classList.remove('unread'));
    }
  }

  /* ------------------------------------------------------------------
   * AJAX form submission (with offline queue support)
   * ------------------------------------------------------------------ */
  document.addEventListener('submit', async (e) => {
    const form = e.target.closest('form[data-ajax]');
    if (!form) return;
    e.preventDefault();
    if (form.dataset.busy) return;

    if (!navigator.onLine) {
      if (form.dataset.offline && window.Agri.offline) {
        const payload = {};
        new FormData(form).forEach((v, k) => { if (!(v instanceof File) && k !== '_token') payload[k] = v; });
        window.Agri.offline.queue(form.dataset.offline, payload, payload.subject || form.dataset.offline);
        form.reset();
        return;
      }
      toast('warning', t('You are offline. Please check your internet connection.'));
      return;
    }

    if (form.dataset.confirm && !(await confirmDialog(form.dataset.confirm))) return;
    let promptValue = null;
    if (form.dataset.prompt) {
      promptValue = await promptDialog(form.dataset.prompt, form.hasAttribute('data-prompt-optional'));
      if (promptValue === null) return;
    }

    const fd = new FormData(form);
    if (e.submitter && e.submitter.name) fd.set(e.submitter.name, e.submitter.value);
    if (promptValue !== null) fd.set(form.dataset.promptField || 'note', promptValue);
    if (form.hasAttribute('data-uuid')) {
      const field = form.elements.client_uuid;
      if (field && !field.value) field.value = uuid();
      if (field) fd.set('client_uuid', field.value);
    }

    clearErrors(form);
    const btn = e.submitter || $('[type=submit]', form) || $('button:not([type=button])', form);
    setLoading(btn, true);
    form.dataset.busy = '1';
    const data = await request(form.action, { method: 'POST', body: fd });
    setLoading(btn, false);
    delete form.dataset.busy;
    handleResponse(form, data);
  });

  /* ------------------------------------------------------------------
   * Live filters (search/filter without page reload)
   * ------------------------------------------------------------------ */
  async function refreshLive(form, page) {
    const target = $(form.dataset.target);
    if (!target) return;
    const params = new URLSearchParams(new FormData(form));
    if (page) params.set('page', page); else params.delete('page');
    const pageUrl = 'index.php?' + params.toString();
    params.set('partial', '1');
    target.classList.add('is-loading-area');
    const data = await request('index.php?' + params.toString());
    target.classList.remove('is-loading-area');
    if (data.ok && typeof data.html === 'string') {
      target.innerHTML = data.html;
      history.replaceState(null, '', pageUrl);
      const count = $('[data-result-count]');
      if (count && typeof data.count !== 'undefined') count.textContent = '(' + data.count + ')';
      if (form.hasAttribute('data-sync-downloads')) syncDownloads(form);
      document.dispatchEvent(new CustomEvent('live:updated', { detail: { form, target } }));
    } else if (data.network && form.hasAttribute('data-offline-catalog') && window.Agri.offline) {
      window.Agri.offline.renderCatalog(form, target);
    } else {
      toast('error', data.message || t('Filter operation failed.'));
    }
  }

  function syncDownloads(form) {
    const params = new URLSearchParams(new FormData(form));
    $$('[data-download]').forEach((a) => {
      const url = new URL(a.href, location.href);
      ['q', 'from', 'to', 'status'].forEach((k) => {
        const v = params.get(k);
        if (v) url.searchParams.set(k, v); else url.searchParams.delete(k);
      });
      a.href = url.pathname.split('/').pop() + url.search;
    });
  }

  function initLive() {
    $$('form[data-live]').forEach((form) => {
      const run = debounce(() => refreshLive(form), 350);
      form.addEventListener('input', (e) => { if (e.target.matches('input[type=text], input[type=search], input:not([type]), input[type=number]')) run(); });
      form.addEventListener('change', (e) => { if (!e.target.matches('input[type=text], input:not([type])')) refreshLive(form); });
      form.addEventListener('submit', (e) => { e.preventDefault(); refreshLive(form); });
      form.addEventListener('click', (e) => {
        const cat = e.target.closest('[data-category]');
        if (cat) {
          const input = $('[data-category-input]', form);
          if (input) input.value = cat.dataset.category;
          $$('[data-category]', form).forEach((b) => b.classList.toggle('active', b === cat));
          refreshLive(form);
        }
        const tab = e.target.closest('[data-tab]');
        if (tab) {
          const input = $('[data-tab-input]', form);
          if (input) input.value = tab.dataset.tab;
          $$('[data-tab]', form).forEach((b) => b.classList.toggle('active', b === tab));
          refreshLive(form);
        }
      });
      const target = $(form.dataset.target);
      if (target) {
        target.addEventListener('click', (e) => {
          const link = e.target.closest('.pager a[data-page]');
          if (!link) return;
          e.preventDefault();
          if (!link.classList.contains('disabled')) refreshLive(form, link.dataset.page);
        });
      }
    });
  }

  /* ------------------------------------------------------------------
   * Notifications (polling every 30 seconds)
   * ------------------------------------------------------------------ */
  async function pollNotifications() {
    if (!APP.user || !navigator.onLine || document.hidden) return;
    const data = await request('index.php?r=notification/poll');
    if (!data.ok) return;
    $$('[data-notif-count]').forEach((b) => { b.textContent = data.unread; b.hidden = !data.unread; });
    $$('[data-msg-count]').forEach((b) => { b.textContent = data.messages; b.hidden = !data.messages; });
    const list = $('[data-notif-list]');
    if (list && data.html) list.innerHTML = data.html;
  }

  /* ------------------------------------------------------------------
   * Small UI behaviours
   * ------------------------------------------------------------------ */
  document.addEventListener('click', (e) => {
    const target = e.target;

    const closeBtn = target.closest('.toast-close');
    if (closeBtn) { dismissToast(closeBtn.closest('.toast')); return; }

    const dd = target.closest('[data-dropdown]');
    $$('.dropdown.open').forEach((d) => { if (!dd || d !== dd.parentElement) d.classList.remove('open'); });
    if (dd) {
      const parent = dd.parentElement;
      parent.classList.toggle('open');
      dd.setAttribute('aria-expanded', parent.classList.contains('open'));
      if (dd.hasAttribute('data-notifications') && parent.classList.contains('open')) pollNotifications();
      return;
    }
    if (!target.closest('.dropdown-menu')) $$('.dropdown.open').forEach((d) => d.classList.remove('open'));

    const opener = target.closest('[data-modal-open]');
    if (opener) { e.preventDefault(); openModal(opener.dataset.modalOpen); }
    if (target.closest('[data-modal-close]')) closeModal(target.closest('.modal'));
    if (target.classList && target.classList.contains('modal') && target.id !== 'confirmModal') closeModal(target);

    const toggler = target.closest('[data-toggle-class]');
    if (toggler) {
      const el = toggler.dataset.target === 'body' ? document.body : $(toggler.dataset.target);
      if (el) el.classList.toggle(toggler.dataset.toggleClass);
    }

    const pw = target.closest('[data-pw-toggle]');
    if (pw) {
      const input = pw.parentElement.querySelector('input');
      input.type = input.type === 'password' ? 'text' : 'password';
      pw.setAttribute('aria-label', input.type === 'password' ? t('Show password') : t('Hide password'));
    }

    const demo = target.closest('[data-demo-login]');
    if (demo) {
      const form = $('form[action*="auth/login"]');
      if (form) { form.elements.email.value = demo.dataset.demoLogin; form.elements.password.value = demo.dataset.demoPass; form.elements.email.focus(); }
    }

    const edit = target.closest('[data-edit-toggle]');
    if (edit) {
      const form = $(edit.dataset.editToggle);
      if (form) {
        form.classList.remove('is-locked');
        $$('input[readonly][name]', form).forEach((i) => { i.readOnly = false; i.dataset.wasReadonly = '1'; });
        const first = $('input[name]:not([disabled])', form);
        if (first) first.focus();
      }
    }
    const cancel = target.closest('[data-edit-cancel]');
    if (cancel) lockForm(cancel.closest('form'), true);
  });

  function lockForm(form, reset) {
    if (!form) return;
    if (reset) form.reset();
    form.classList.add('is-locked');
    $$('[data-was-readonly]', form).forEach((i) => { i.readOnly = true; });
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      $$('.modal:not([hidden])').forEach((m) => { if (m.id !== 'confirmModal') closeModal(m); });
      $$('.dropdown.open').forEach((d) => d.classList.remove('open'));
      document.body.classList.remove('sidebar-open', 'nav-open');
    }
  });

  // profile form: update visible values immediately after saving
  document.addEventListener('ajax:success', (e) => {
    const form = e.target;
    const data = e.detail;
    if (form.hasAttribute('data-profile-form') && data.profile) {
      Object.keys(data.profile).forEach((key) => {
        $$('[data-profile="' + key + '"]').forEach((el) => {
          if (key === 'avatar') el.innerHTML = data.profile.avatar; else el.textContent = data.profile[key];
        });
      });
      const sideName = $('.sidebar-profile strong');
      if (sideName) sideName.textContent = data.profile.name;
      if (data.profile.avatar) {
        const side = $('.sidebar-profile .avatar');
        const el = htmlToElement(data.profile.avatar.replace('avatar-xl', 'avatar-xl'));
        if (side && el) side.replaceWith(el);
      }
      if (form.id === 'profileForm') lockForm(form, false);
    }
    if (form.hasAttribute('data-countdown-form')) {
      const btn = $('[data-countdown]', form);
      if (btn) startCountdown(btn, parseInt(btn.dataset.countdown, 10));
    }
    if (data.expires_in) $$('[data-expiry]').forEach((el) => startExpiry(el, data.expires_in));
  });

  /* password strength */
  function strength(value) {
    let score = 0;
    if (value.length >= 8) score++;
    if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score++;
    if (/\d/.test(value)) score++;
    if (/[^A-Za-z0-9]/.test(value)) score++;
    return score;
  }
  document.addEventListener('input', (e) => {
    if (e.target.matches('[data-strength]')) {
      const label = e.target.closest('.field').querySelector('[data-strength-label]');
      if (!label) return;
      const s = strength(e.target.value);
      label.classList.remove('weak', 'fair', 'strong');
      if (!e.target.value) { label.textContent = t('At least 8 characters'); return; }
      const cls = s <= 1 ? 'weak' : (s <= 3 ? 'fair' : 'strong');
      label.classList.add(cls);
      label.textContent = { weak: t('Weak password'), fair: t('Fair – add a special character or number'), strong: t('Strong password') }[cls];
    }
    if (e.target.matches('[data-counter]')) {
      const label = e.target.closest('.field').querySelector('[data-counter-label]');
      if (label) label.textContent = e.target.value.length + '/' + (e.target.maxLength > 0 ? e.target.maxLength : 500);
    }
  });

  /* file inputs */
  document.addEventListener('change', (e) => {
    const input = e.target;
    if (input.matches('[data-file-input]')) {
      const label = document.querySelector('label[for="' + input.id + '"] [data-file-label]');
      if (label && input.files[0]) label.textContent = input.files[0].name + ' (' + Math.round(input.files[0].size / 1024) + ' KB)';
    }
    if (input.matches('[data-image-input]') && input.files[0]) {
      const box = document.querySelector('label[for="' + input.id + '"]');
      const img = box && $('[data-preview]', box);
      if (img) {
        img.src = URL.createObjectURL(input.files[0]);
        img.hidden = false;
        const hint = $('.upload-hint', box);
        if (hint) hint.hidden = true;
      }
    }
    const auto = input.closest('form[data-autosubmit]');
    if (auto && input.type === 'file' && input.files[0]) {
      if (auto.requestSubmit) auto.requestSubmit(); else auto.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
    }
  });

  /* OTP boxes */
  function initOtp(input) {
    const form = input.form;
    const wrap = document.createElement('div');
    wrap.className = 'otp-boxes';
    const boxes = [];
    for (let i = 0; i < 6; i++) {
      const box = document.createElement('input');
      box.type = 'text';
      box.inputMode = 'numeric';
      box.maxLength = 1;
      box.autocomplete = i === 0 ? 'one-time-code' : 'off';
      box.setAttribute('aria-label', t('Digit') + ' ' + (i + 1));
      wrap.appendChild(box);
      boxes.push(box);
    }
    input.type = 'hidden';
    input.insertAdjacentElement('beforebegin', wrap);
    const sync = () => {
      input.value = boxes.map((b) => b.value).join('');
      if (input.value.length === 6 && form && form.hasAttribute('data-autosubmit-otp') && !form.dataset.busy) {
        if (form.requestSubmit) form.requestSubmit();
      }
    };
    boxes.forEach((box, i) => {
      box.addEventListener('input', () => {
        box.value = box.value.replace(/\D/g, '').slice(-1);
        if (box.value && boxes[i + 1]) boxes[i + 1].focus();
        sync();
      });
      box.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !box.value && boxes[i - 1]) boxes[i - 1].focus();
        if (e.key === 'ArrowLeft' && boxes[i - 1]) boxes[i - 1].focus();
        if (e.key === 'ArrowRight' && boxes[i + 1]) boxes[i + 1].focus();
      });
      box.addEventListener('paste', (e) => {
        const digits = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6);
        if (!digits) return;
        e.preventDefault();
        digits.split('').forEach((d, j) => { if (boxes[j]) boxes[j].value = d; });
        (boxes[digits.length] || boxes[5]).focus();
        sync();
      });
    });
    if (form) form.addEventListener('ajax:error', () => { boxes.forEach((b) => { b.value = ''; b.classList.add('is-invalid'); }); input.value = ''; boxes[0].focus(); });
    boxes[0].focus();
  }

  /* countdowns */
  function startCountdown(btn, seconds) {
    if (!btn.dataset.label) btn.dataset.label = btn.textContent.trim();
    clearInterval(btn._timer);
    let left = seconds;
    btn.disabled = true;
    const tick = () => {
      btn.textContent = btn.dataset.label + ' (' + left + 's)';
      if (left-- <= 0) { clearInterval(btn._timer); btn.disabled = false; btn.textContent = btn.dataset.label; }
    };
    tick();
    btn._timer = setInterval(tick, 1000);
  }
  function startExpiry(el, seconds) {
    const out = $('[data-expiry-left]', el);
    clearInterval(el._timer);
    let left = seconds;
    const tick = () => {
      if (left <= 0) {
        clearInterval(el._timer);
        el.innerHTML = '<span class="text-danger">' + escapeHtml(t('Code expired. Please request a new code.')) + '</span><b data-expiry-left hidden></b>';
        return;
      }
      if (out) out.textContent = Math.floor(left / 60) + ':' + String(left % 60).padStart(2, '0');
      left--;
    };
    tick();
    el._timer = setInterval(tick, 1000);
  }

  /* ------------------------------------------------------------------
   * Boot
   * ------------------------------------------------------------------ */
  document.addEventListener('DOMContentLoaded', () => {
    try {
      const pending = sessionStorage.getItem('agri_toast');
      if (pending) { sessionStorage.removeItem('agri_toast'); const [type, msg] = JSON.parse(pending); toast(type, msg); }
    } catch (e) { /* ignore */ }
    $$('#toasts .toast').forEach((el) => setTimeout(() => dismissToast(el), el.classList.contains('toast-error') ? 8000 : 5000));
    $$('input[data-otp]').forEach(initOtp);
    $$('form[data-countdown-form] [data-countdown]').forEach((btn) => startCountdown(btn, parseInt(btn.dataset.countdown, 10)));
    $$('[data-expiry]').forEach((el) => startExpiry(el, parseInt(el.dataset.expiry, 10)));
    $$('.modal[data-auto-open]').forEach((m) => setTimeout(() => openModal(m), 400));
    $$('.field-error').forEach((err) => {
      if (err.textContent.trim()) {
        const field = err.closest('.field');
        const input = field && field.querySelector('input, select, textarea');
        if (input) input.classList.add('is-invalid');
      }
    });
    initLive();
    if (APP.user) {
      pollNotifications();
      setInterval(pollNotifications, 30000);
    } else if ('caches' in window) {
      // signed out: remove cached private pages
      caches.keys().then((keys) => keys.filter((k) => k.startsWith('agri-pages')).forEach((k) => caches.delete(k))).catch(() => {});
    }
  });

  window.Agri = Object.assign(window.Agri || {}, {
    t, $, $$, csrf, uuid, debounce, escapeHtml, request, toast, reloadWithToast,
    openModal, closeModal, confirmDialog, promptDialog, handleResponse, showErrors, clearErrors,
    setLoading, refreshLive, startCountdown, htmlToElement, removeRow,
  });
})();
