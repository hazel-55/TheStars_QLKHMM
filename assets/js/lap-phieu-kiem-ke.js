'use strict';
(function () {
  const rows = [...document.querySelectorAll('.kk-item-row')];
  if (!rows.length) return;
  // Phần nghìn dùng BigInt để khớp DECIMAL(18,3), kể cả số lớn.
  const format = n => {
    if (typeof n !== 'bigint') return String(n);
    const whole = (n / 1000n).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    const fraction = (n % 1000n).toString().padStart(3, '0').replace(/0+$/, '');
    return whole + (fraction ? '.' + fraction : '');
  };
  const read = value => {
    const match = value.trim().match(/^([0-9]{1,15}|[0-9]{1,3}(?: [0-9]{3}){1,4})(?:\.([0-9]{1,3}))?$/);
    if (!match) return null;
    return BigInt(match[1].replace(/ /g, '')) * 1000n + BigInt((match[2] || '').padEnd(3, '0'));
  };
  const update = () => {
    let count = 0, plus = 0, minus = 0;
    for (const row of rows) {
      const selected = row.querySelector('.kk-select').checked;
      row.classList.toggle('is-selected', selected);
      row.nextElementSibling.classList.toggle('is-visible', selected);
      const actual = row.querySelector('.kk-actual');
      const destroyed = row.querySelector('.kk-destroyed');
      const stock = read(row.dataset.stock);
      const a = actual.value.trim() === '' ? null : read(actual.value);
      const d = read(destroyed.value);
      const valid = selected && a !== null && d !== null && d <= a && stock !== null;
      row.querySelector('.kk-remaining').textContent = valid ? format(a - d) : '—';
      const delta = row.querySelector('.kk-difference');
      if (valid) {
        const diff = a - d - stock;
        delta.textContent = (diff > 0 ? '+' : '') + (diff < 0 ? '−' : '') + format(diff < 0n ? -diff : diff);
        delta.classList.toggle('kk-pos', diff > 0);
        delta.classList.toggle('kk-neg', diff < 0);
        if (diff > 0) plus++;
        if (diff < 0) minus++;
      } else {
        delta.textContent = '—';
        delta.classList.remove('kk-pos', 'kk-neg');
      }
      if (selected) count++;
    }
    for (const id of ['selected-count','metric-count']) {
      const el = document.getElementById(id);
      if (el) el.textContent = format(count);
    }
    const p = document.getElementById('metric-plus'), m = document.getElementById('metric-minus');
    if (p) p.textContent = format(plus);
    if (m) m.textContent = format(minus);
  };
  for (const row of rows) {
    row.addEventListener('input', update);
    row.querySelector('.kk-select').addEventListener('change', update);
    for (const selector of ['.kk-actual','.kk-destroyed']) {
      const input = row.querySelector(selector);
      input.addEventListener('blur', () => {
        const n = read(input.value);
        if (n !== null) input.value = format(n);
        update();
      });
    }
  }
  const all = document.getElementById('select-all');
  if (all) all.addEventListener('click', () => {
    const visible = rows.filter(r => !r.hidden);
    const shouldCheck = visible.some(r => !r.querySelector('.kk-select').checked);
    for (const row of visible) if (!row.querySelector('.kk-select').disabled) row.querySelector('.kk-select').checked = shouldCheck;
    update();
  });
  const search = document.getElementById('kk-search');
  const visibleLabel = document.getElementById('kk-visible-count');
  if (search) search.addEventListener('input', () => {
    const term = search.value.trim().normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
    let count = 0;
    for (const row of rows) {
      const hay = row.textContent.normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase();
      const visible = hay.includes(term);
      row.hidden = !visible;
      row.nextElementSibling.hidden = !visible;
      if (visible) count++;
    }
    if (visibleLabel) visibleLabel.textContent = format(count)+' / '+format(rows.length)+' dòng hiển thị';
  });
  if (visibleLabel) visibleLabel.textContent = format(rows.length)+' dòng hiển thị';
  // Chỉ gửi thông tin những dòng được chọn: giảm max_input_vars khi danh mục nhiều SKU.
  const form = document.getElementById('kiemke-form');
  if (form) form.addEventListener('submit', () => {
    for (const row of rows) if (!row.querySelector('.kk-select').checked) {
      for (const el of row.querySelectorAll('input,select,textarea')) el.disabled = true;
      for (const el of row.nextElementSibling.querySelectorAll('input,select,textarea')) el.disabled = true;
    }
  });
  update();
})();
