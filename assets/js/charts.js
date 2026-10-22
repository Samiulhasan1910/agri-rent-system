/* AgriRent – tiny SVG chart helpers (bar, line, donut). No external libraries. */
(function () {
  'use strict';
  const PALETTE = ['#16803c', '#f59e0b', '#2563eb', '#7c3aed', '#dc2626', '#0ea5e9', '#86cf9d', '#a16207'];
  const NS = 'http://www.w3.org/2000/svg';
  const t = (s) => (window.Agri && window.Agri.t ? window.Agri.t(s) : s);

  function fmt(n) {
    const v = Number(n) || 0;
    if (Math.abs(v) >= 1e6) return (v / 1e6).toFixed(1).replace(/\.0$/, '') + 'M';
    if (Math.abs(v) >= 1e3) return (v / 1e3).toFixed(1).replace(/\.0$/, '') + 'k';
    return String(Math.round(v * 100) / 100);
  }
  function el(name, attrs, parent) {
    const node = document.createElementNS(NS, name);
    Object.keys(attrs || {}).forEach((k) => node.setAttribute(k, attrs[k]));
    if (parent) parent.appendChild(node);
    return node;
  }
  function niceMax(max) {
    if (max <= 0) return 4;
    const pow = Math.pow(10, Math.floor(Math.log10(max)));
    const n = max / pow;
    const step = n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10;
    return step * pow;
  }
  function empty(container) {
    container.innerHTML = '<div class="chart-empty">' + t('No data available') + '</div>';
  }
  function tooltip(container) {
    let tip = container.querySelector('.chart-tooltip');
    if (!tip) { tip = document.createElement('div'); tip.className = 'chart-tooltip'; container.appendChild(tip); }
    return tip;
  }
  function showTip(container, svg, x, y, text) {
    const tip = tooltip(container);
    const box = svg.getBoundingClientRect();
    const scale = box.width / svg.viewBox.baseVal.width;
    tip.textContent = text;
    tip.style.left = (x * scale) + 'px';
    tip.style.top = (y * scale) + 'px';
    tip.style.opacity = '1';
  }
  function hideTip(container) { const tip = container.querySelector('.chart-tooltip'); if (tip) tip.style.opacity = '0'; }

  function frame(container, labels, max, height) {
    container.innerHTML = '';
    const W = Math.max(320, Math.min(900, container.clientWidth || 600));
    const H = height || 260;
    const pad = { l: 44, r: 12, t: 14, b: 34 };
    const svg = el('svg', { viewBox: '0 0 ' + W + ' ' + H, role: 'img' }, container);
    const top = niceMax(max);
    for (let i = 0; i <= 4; i++) {
      const y = pad.t + (H - pad.t - pad.b) * (1 - i / 4);
      el('line', { x1: pad.l, x2: W - pad.r, y1: y, y2: y, stroke: '#e6eee8', 'stroke-dasharray': i ? '3 4' : '' }, svg);
      const txt = el('text', { x: pad.l - 8, y: y + 4, 'text-anchor': 'end', 'font-size': 11, fill: '#7b8a80' }, svg);
      txt.textContent = fmt(top * i / 4);
    }
    const every = Math.max(1, Math.ceil(labels.length / Math.floor((W - pad.l - pad.r) / 56)));
    const step = (W - pad.l - pad.r) / Math.max(1, labels.length);
    labels.forEach((label, i) => {
      if (i % every !== 0) return;
      const txt = el('text', { x: pad.l + step * i + step / 2, y: H - 10, 'text-anchor': 'middle', 'font-size': 11, fill: '#7b8a80' }, svg);
      txt.textContent = label;
    });
    return { svg, W, H, pad, top, step };
  }

  function bar(container, labels, values, opts = {}) {
    if (!container) return;
    if (!labels.length || values.every((v) => !Number(v))) { empty(container); return; }
    const f = frame(container, labels, Math.max(...values.map(Number)), opts.height);
    const plotH = f.H - f.pad.t - f.pad.b;
    const bw = Math.min(42, f.step * 0.56);
    values.forEach((v, i) => {
      const h = plotH * (Number(v) / f.top);
      const x = f.pad.l + f.step * i + (f.step - bw) / 2;
      const y = f.pad.t + plotH - h;
      const rect = el('rect', { x, y, width: bw, height: Math.max(0, h), rx: 6, fill: opts.color || PALETTE[0], opacity: 0.9 }, f.svg);
      rect.addEventListener('mouseenter', () => { rect.setAttribute('opacity', 1); showTip(container, f.svg, x + bw / 2, y, labels[i] + ': ' + (opts.prefix || '') + fmt(v) + ' ' + (opts.label || '')); });
      rect.addEventListener('mouseleave', () => { rect.setAttribute('opacity', 0.9); hideTip(container); });
    });
    remember(container, () => bar(container, labels, values, opts));
  }

  function line(container, labels, values, opts = {}) {
    if (!container) return;
    if (!labels.length || values.every((v) => !Number(v))) { empty(container); return; }
    const f = frame(container, labels, Math.max(...values.map(Number)), opts.height);
    const plotH = f.H - f.pad.t - f.pad.b;
    const color = opts.color || PALETTE[0];
    const pts = values.map((v, i) => [f.pad.l + f.step * i + f.step / 2, f.pad.t + plotH - plotH * (Number(v) / f.top)]);
    const defs = el('defs', {}, f.svg);
    const grad = el('linearGradient', { id: 'g' + Math.random().toString(36).slice(2), x1: 0, y1: 0, x2: 0, y2: 1 }, defs);
    el('stop', { offset: '0', 'stop-color': color, 'stop-opacity': 0.25 }, grad);
    el('stop', { offset: '1', 'stop-color': color, 'stop-opacity': 0 }, grad);
    const d = pts.map((p, i) => (i ? 'L' : 'M') + p[0].toFixed(1) + ' ' + p[1].toFixed(1)).join(' ');
    el('path', { d: d + ' L' + pts[pts.length - 1][0] + ' ' + (f.pad.t + plotH) + ' L' + pts[0][0] + ' ' + (f.pad.t + plotH) + ' Z', fill: 'url(#' + grad.id + ')' }, f.svg);
    el('path', { d, fill: 'none', stroke: color, 'stroke-width': 2.5, 'stroke-linejoin': 'round', 'stroke-linecap': 'round' }, f.svg);
    pts.forEach((p, i) => {
      const c = el('circle', { cx: p[0], cy: p[1], r: pts.length > 40 ? 2 : 4, fill: '#fff', stroke: color, 'stroke-width': 2 }, f.svg);
      const hit = el('circle', { cx: p[0], cy: p[1], r: 10, fill: 'transparent' }, f.svg);
      hit.addEventListener('mouseenter', () => { c.setAttribute('r', 6); showTip(container, f.svg, p[0], p[1], labels[i] + ': ' + (opts.prefix || '') + fmt(values[i])); });
      hit.addEventListener('mouseleave', () => { c.setAttribute('r', pts.length > 40 ? 2 : 4); hideTip(container); });
    });
    remember(container, () => line(container, labels, values, opts));
  }

  function donut(container, items, opts = {}) {
    if (!container) return;
    const data = (items || []).map((it) => ({ label: it.label, value: Number(it.value) || 0 })).filter((it) => it.value > 0);
    if (!data.length) { empty(container); return; }
    container.innerHTML = '';
    const total = data.reduce((s, it) => s + it.value, 0);
    const size = 200, r = 78, stroke = 30, cx = size / 2, cy = size / 2;
    const wrap = document.createElement('div');
    wrap.style.cssText = 'display:flex;flex-wrap:wrap;gap:16px;align-items:center;justify-content:center';
    container.appendChild(wrap);
    const svg = el('svg', { viewBox: '0 0 ' + size + ' ' + size, width: 190, height: 190, role: 'img' });
    wrap.appendChild(svg);
    let angle = -Math.PI / 2;
    data.forEach((it, i) => {
      const frac = it.value / total;
      const end = angle + frac * Math.PI * 2;
      const large = frac > 0.5 ? 1 : 0;
      const x1 = cx + r * Math.cos(angle), y1 = cy + r * Math.sin(angle);
      const x2 = cx + r * Math.cos(end - 0.0001), y2 = cy + r * Math.sin(end - 0.0001);
      const path = el('path', { d: 'M' + x1 + ' ' + y1 + ' A' + r + ' ' + r + ' 0 ' + large + ' 1 ' + x2 + ' ' + y2, fill: 'none', stroke: PALETTE[i % PALETTE.length], 'stroke-width': stroke }, svg);
      const title = el('title', {}, path);
      title.textContent = it.label + ': ' + fmt(it.value) + ' (' + Math.round(frac * 100) + '%)';
      angle = end;
    });
    const tt = el('text', { x: cx, y: cy - 2, 'text-anchor': 'middle', 'font-size': 26, 'font-weight': 700, fill: '#1b2a21' }, svg);
    tt.textContent = fmt(total);
    const st = el('text', { x: cx, y: cy + 18, 'text-anchor': 'middle', 'font-size': 11, fill: '#7b8a80' }, svg);
    st.textContent = opts.centerLabel || t('Total');
    const legend = document.createElement('div');
    legend.className = 'chart-legend';
    legend.style.flexDirection = 'column';
    legend.innerHTML = data.map((it, i) => '<span><i style="background:' + PALETTE[i % PALETTE.length] + '"></i>' + window.Agri.escapeHtml(it.label) + ' — <b>' + fmt(it.value) + '</b> (' + Math.round(it.value / total * 100) + '%)</span>').join('');
    wrap.appendChild(legend);
  }

  // re-render bar/line charts on resize
  const renderers = new Map();
  function remember(container, fn) { renderers.set(container, fn); }
  let resizeTimer;
  window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => renderers.forEach((fn, node) => { if (document.body.contains(node)) fn(); }), 200);
  });

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.chart[data-chart="bar"]').forEach((node) => {
      try {
        bar(node, JSON.parse(node.dataset.labels || '[]'), JSON.parse(node.dataset.values || '[]'), { label: node.dataset.seriesLabel || '' });
      } catch (e) { empty(node); }
    });
  });

  window.Charts = { bar, line, donut, fmt };
})();
