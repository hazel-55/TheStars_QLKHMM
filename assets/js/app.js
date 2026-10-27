
const body = document.querySelector('#lines'),
    source = document.querySelector('#source');

let serial = 0;

function make(tag, text, cls) {
    const el = document.createElement(tag);
    if (text !== undefined) el.textContent = text;
    if (cls) el.className = cls;
    return el;
}

function refresh() {
    if (!body) return;

    let count = 0;

    for (const row of body.rows) {
        const p = CK.products.find(
            p => p.maBienThe === row.querySelector('select').value
        );

        const s = CK.stock.find(
            s => s.maKho === source.value &&
                 s.maBienThe === p?.maBienThe
        );

        const available = Number(s?.available ?? 0);
        const qty = row.querySelector('input[type=number]');
        const isPiece = p?.donViTinh === 'Cái';

        row.querySelector('.unit').textContent =
            p?.donViTinh ?? '—';

        row.querySelector('.available').textContent =
            p ? available.toLocaleString('vi-VN') : '—';

        // Đơn vị Cái nhập số nguyên, Mét/Kg nhập được số thập phân
        qty.min = isPiece ? '1' : '0.001';
        qty.step = isPiece ? '1' : '0.001';

        if (!CK.readonly) {
            const value = Number(qty.value);
            let error = '';

            if (qty.value !== '') {
                if (value <= 0) {
                    error = 'Số lượng phải lớn hơn 0.';
                } else if (isPiece && !Number.isInteger(value)) {
                    error = 'Hàng có đơn vị Cái phải nhập số nguyên.';
                } else if (value > available) {
                    error = 'Số lượng vượt tồn khả dụng.';
                }
            }

            qty.max = String(available);
            qty.setCustomValidity(error);
        }

        row.classList.toggle(
            'over',
            !CK.readonly &&
            qty.value !== '' &&
            Number(qty.value) > available
        );

        if (p) count++;
    }

    const summary = document.querySelector('#summary');
    if (summary) summary.textContent = count + ' dòng hàng';
}

function addLine(data = {}) {
    const n = serial++,
        tr = make('tr'),
        sel = make('select');

    sel.name = `rows[${n}][maBienThe]`;
    sel.required = true;
    sel.disabled = CK.readonly;
    sel.setAttribute('aria-label', 'Hàng hóa');

    sel.append(new Option('Chọn hàng hóa / biến thể', ''));

    for (const p of CK.products) {
        sel.append(new Option(
            `${p.maBienThe} · ${p.tenHangHoa} / ${p.mauSac} / ${p.kichThuoc}`,
            p.maBienThe
        ));
    }

    if (
        data.maBienThe &&
        !CK.products.some(p => p.maBienThe === data.maBienThe)
    ) {
        sel.append(new Option(
            data.maBienThe + ' (ngừng hoạt động)',
            data.maBienThe
        ));
    }

    sel.value = data.maBienThe ?? '';

    const td = make('td');
    td.append(sel);

    tr.append(
        td,
        make('td', '—', 'unit'),
        make('td', '—', 'available')
    );

    const qt = make('td'),
        qty = make('input');

    qty.type = 'number';

    // Đồng bộ tên trường với database mới
    qty.name = `rows[${n}][soLuongChuyen]`;

    qty.min = '0.001';
    qty.step = '0.001';
    qty.required = true;

    qty.value = data.soLuongChuyen ??
                data.soLuongYeuCau ?? '';

    qty.disabled = CK.readonly;
    qty.setAttribute('aria-label', 'Số lượng chuyển');

    qt.append(qty);
    tr.append(qt);

    const nt = make('td'),
        note = make('input');

    note.name = `rows[${n}][ghiChu]`;
    note.value = data.ghiChu ?? '';
    note.maxLength = 255;
    note.placeholder = 'Ghi chú';
    note.disabled = CK.readonly;
    note.setAttribute('aria-label', 'Ghi chú hàng hóa');

    nt.append(note);
    tr.append(nt);

    const dt = make('td');

    if (!CK.readonly) {
        const del = make('button', '×', 'remove');

        del.type = 'button';
        del.setAttribute('aria-label', 'Xóa dòng hàng');

        del.onclick = () => {
            tr.remove();
            refresh();
        };

        dt.append(del);
    }

    tr.append(dt);
    body.append(tr);

    sel.onchange = refresh;
    qty.oninput = refresh;

    refresh();
}

if (body) {
    for (const r of CK.rows) addLine(r);

    if (source) source.onchange = refresh;

    document.querySelector('#addRow')?.addEventListener(
        'click',
        () => {
            if (body.rows.length < 100) addLine();
        }
    );

    document.querySelector('#requestForm')?.addEventListener(
        'submit',
        e => {
            if (e.submitter?.value === 'cancel') {
                if (!confirm(
                    'Hủy phiếu nháp này? Phiếu sẽ được giữ lại với trạng thái Đã hủy.'
                )) {
                    e.preventDefault();
                }
                return;
            }

            const ids = [
                ...body.querySelectorAll('select')
            ].map(x => x.value);

            let message = '';

            if (
                source.value ===
                document.querySelector('#destination').value
            ) {
                message = 'Kho xuất và kho nhập phải khác nhau.';
            } else if (ids.length === 0) {
                message = 'Hãy thêm ít nhất một dòng hàng.';
            } else if (new Set(ids).size !== ids.length) {
                message = 'Một biến thể chỉ xuất hiện một lần trong phiếu.';
            }

            if (message) {
                e.preventDefault();
                alert(message);
                return;
            }

            if (
                e.submitter?.value === 'send' &&
                !confirm(
                    'Gửi phê duyệt? Sau khi gửi, bạn không thể sửa hoặc hủy phiếu.'
                )
            ) {
                e.preventDefault();
            }
        }
    );
}

// Tìm kiếm yêu cầu chuyển kho
document.querySelector('#search')?.addEventListener(
    'input',
    e => {
        const q = e.target.value.toLocaleLowerCase('vi').trim();
        const history = document.querySelector('#history');

        if (!history) return;

        let visible = 0;

        for (const r of history.rows) {
            r.hidden = !r.textContent
                .toLocaleLowerCase('vi')
                .includes(q);

            if (!r.hidden) visible++;
        }

        const noResults = document.querySelector('#noResults');

        if (noResults) {
            noResults.hidden = visible > 0 || !q;
        }
    }
);
