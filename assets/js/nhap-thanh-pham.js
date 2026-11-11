/* Giao diện yêu cầu nhập thành phẩm: thêm/xóa dòng, đơn vị tính, đếm tổng, kiểm tra trước khi gửi. */
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('ntp-form');
    const list = document.getElementById('ntp-lines');
    const template = document.getElementById('ntp-row-template');
    const addBtn = document.getElementById('ntp-add');
    if (!form || !list || !template || !addBtn) return;

    const count = document.getElementById('ntp-count');
    const total = document.getElementById('ntp-total');
    const errBox = document.getElementById('ntp-client-error');
    const maxRows = 50;
    function update() {
        const rows = Array.from(list.querySelectorAll('tr'));
        let sum = 0;
        let selected = 0;
        rows.forEach((row, i) => {
            row.querySelector('.ntp-number').textContent = String(i + 1).padStart(2, '0');
            const sel = row.querySelector('.ntp-product');
            row.querySelector('.ntp-unit').textContent = sel.selectedOptions[0]?.dataset.unit || '—';
            row.querySelector('.ntp-remove').disabled = rows.length === 1;
            if (sel.value) selected++;
            const value = Number(row.querySelector('input[name="soLuong[]"]').value);
            if (Number.isFinite(value) && value > 0) sum += value;
        });
        addBtn.disabled = rows.length >= maxRows;
        if (count) count.textContent = String(selected);
        if (total) total.textContent = new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 2 }).format(sum);
    }
    function showError(text) {
        if (!errBox) { window.alert(text); return; }
        errBox.textContent = text;
        errBox.hidden = false;
        errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    addBtn.addEventListener('click', () => {
        if (list.querySelectorAll('tr').length >= maxRows) return;
        list.appendChild(template.content.cloneNode(true));
        update();
        list.lastElementChild.querySelector('.ntp-product')?.focus();
    });
    list.addEventListener('click', (e) => {
        const button = e.target.closest('.ntp-remove');
        if (!button || button.disabled) return;
        button.closest('tr')?.remove();
        update();
    });
    list.addEventListener('change', update);
    list.addEventListener('input', update);
    form.addEventListener('submit', (e) => {
        if (errBox) errBox.hidden = true;
        const products = Array.from(list.querySelectorAll('.ntp-product')).map(el => el.value);
        if (products.some(v => !v)) { e.preventDefault(); showError('Bạn cần chọn thành phẩm ở tất cả các dòng.'); return; }
        if (new Set(products).size !== products.length) { e.preventDefault(); showError('Không được chọn trùng thành phẩm. Hãy chọn mỗi mặt hàng một lần.'); return; }
        const numbers = Array.from(list.querySelectorAll('input[name="soLuong[]"]'));
        for (let i = 0; i < numbers.length; i++) {
            const raw = numbers[i].value.trim();
            if (!/^\d{1,16}(?:\.\d{1,2})?$/.test(raw) || Number(raw) <= 0) {
                e.preventDefault(); showError('Số lượng dòng ' + (i + 1) + ' phải lớn hơn 0, tối đa 2 chữ số thập phân.');
                numbers[i].focus(); return;
            }
        }
        const message = e.submitter?.dataset.confirm;
        if (message && !window.confirm(message)) e.preventDefault();
    });
    update();
});
