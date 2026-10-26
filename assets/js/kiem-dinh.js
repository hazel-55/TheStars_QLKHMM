
window.KiemDinhPage = window.KiemDinhPage || {};
window.KiemDinhPage.init = function (data) {
    const form = document.querySelector('#kd-form');
    if (!form || form.dataset.initialized === '1') {
        return;
    }

    form.dataset.initialized = '1';
    const container = document.querySelector('#kd-items');
    const readonly = data.chiXem;
    // Escape dữ liệu trước khi đưa vào HTML.
    const escape = value => String(value ?? '').replace(
        /[&<>"']/g,
        char => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#39;'
        })[char]
    );
    const format = value => (value / 1000).toLocaleString('vi-VN');
    const createGroup = (item, group = {}) => {
        const element = document.createElement('section');
        element.className = 'kd-group';
        const results = group.KetQuaTieuChi || {};
        const disabled = readonly ? 'disabled' : '';
        element.innerHTML = `
            <div class="kd-group-head">
                <strong class="kd-group-title">Nhóm</strong>
                <label>
                    Số lượng (${escape(item.DonViTinh)})
                    <input
                        class="kd-qty"
                        type="number"
                        aria-label="Số lượng nhóm"
                        min="${item.DonViTinh === 'Mét' ? '0.001' : '1'}"
                        step="${item.DonViTinh === 'Mét' ? '0.001' : '1'}"
                        value="${escape(group.SoLuong ?? '')}"
                        ${disabled}
                    >
                </label>
                <span class="kd-badge kd-conclusion"></span>
                ${readonly ? '' : `
                    <button
                        class="kd-button kd-remove"
                        type="button"
                    >
                        Xóa nhóm
                    </button>
                `}
            </div>
            <div class="kd-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Tiêu chí bắt buộc</th>
                            <th>Yêu cầu đối chiếu</th>
                            <th>Kết quả</th>
                            <th>Ghi nhận thực tế</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${(item.TieuChi || []).map(criterion => {
            const result =
                results[criterion.MaTieuChiYC] || {};
            return `
                                <tr
                                    class="kd-criterion"
                                    data-id="${escape(
                criterion.MaTieuChiYC
            )}"
                                >
                                    <td>
                                        <strong>
                                            ${escape(criterion.TenTieuChi)}
                                        </strong>
                                    </td>
                                    <td>
                                        ${escape(criterion.YeuCau)}
                                    </td>
                                    <td>
                                        <select
                                            class="kd-criterion-result"
                                            aria-label="${escape(
                criterion.TenTieuChi
            )}"
                                            ${disabled}
                                        >
                                            <option value="">
                                                Chưa đánh giá
                                            </option>
                                            <option
                                                value="Đạt"
                                                ${result.KetQua === 'Đạt'
                    ? 'selected'
                    : ''}
                                            >
                                                Đạt
                                            </option>
                                            <option
                                                value="Không đạt"
                                                ${result.KetQua
                    === 'Không đạt'
                    ? 'selected'
                    : ''}
                                            >
                                                Không đạt
                                            </option>
                                        </select>
                                    </td>
                                    <td>
                                        <textarea
                                            class="kd-criterion-note"
                                            maxlength="500"
                                            aria-label="Ghi chú ${escape(
                        criterion.TenTieuChi
                    )}"
                                            placeholder="Giá trị đo / mô tả lỗi"
                                            ${disabled}
                                        >${escape(result.GhiChu)}</textarea>
                                    </td>
                                </tr>
                            `;
        }).join('')}
                    </tbody>
                </table>
            </div>
            <input
                class="kd-group-note"
                aria-label="Nhãn nhận diện nhóm"
                maxlength="300"
                placeholder="Nhãn nhận diện / ghi chú nhóm"
                value="${escape(group.GhiChu)}"
                ${disabled}
            >
        `;
        return element;
    };
    const updateSummary = itemElement => {
        const item = itemElement.itemData;
        const required = Math.round(
            Number(item.SoLuongYeuCauKiemDinh) * 1000
        );
        let allocated = 0;
        let passed = 0;
        let failed = 0;
        let validQuantity = true;
        itemElement.querySelectorAll('.kd-group').forEach(
            (group, index) => {
                group.querySelector('.kd-group-title').textContent =
                    'Nhóm ' + (index + 1);
                const rawQuantity =
                    group.querySelector('.kd-qty').value;
                const quantity = Number(rawQuantity);
                const valid =
                    /^\d{1,9}(?:\.\d{1,3})?$/.test(rawQuantity)
                    && Number.isFinite(quantity)
                    && quantity > 0
                    && (
                        item.DonViTinh === 'Mét'
                        || Number.isInteger(quantity)
                    );
                if (!valid) {
                    validQuantity = false;
                }
                const results = [
                    ...group.querySelectorAll('.kd-criterion-result')
                ].map(select => select.value);
                const complete =
                    results.length > 0 && results.every(Boolean);
                const hasFailure = results.includes('Không đạt');
                const conclusion = complete
                    ? (hasFailure ? 'Không đạt' : 'Đạt')
                    : 'Chưa hoàn tất';
                const badge = group.querySelector('.kd-conclusion');
                badge.textContent = conclusion;
                badge.style.color = conclusion === 'Đạt'
                    ? '#26734f'
                    : conclusion === 'Không đạt'
                        ? '#ad4747'
                        : '#82682a';
                if (valid) {
                    const amount = Math.round(quantity * 1000);
                    allocated += amount;
                    if (complete) {
                        if (hasFailure) {
                            failed += amount;
                        } else {
                            passed += amount;
                        }
                    }
                }
            }
        );
        itemElement.querySelector('.kd-allocated').textContent =
            format(allocated);
        itemElement.querySelector('.kd-passed').textContent =
            format(passed);
        itemElement.querySelector('.kd-failed').textContent =
            format(failed);
        itemElement.querySelector('.kd-pending').textContent =
            format(Math.max(0, required - passed - failed));
        const validation = itemElement.querySelector('.kd-validation');
        if (!validQuantity) {
            validation.textContent =
                'Kiểm tra số lượng nhóm: phải lớn hơn 0 và đúng đơn vị tính.';
        } else if (allocated > required) {
            validation.textContent =
                'Tổng nhóm vượt số lượng yêu cầu.';
        } else if (allocated < required) {
            validation.textContent =
                'Còn ' + format(required - allocated)
                + ' ' + item.DonViTinh + ' chưa phân nhóm.';
        } else if (passed + failed !== required) {
            validation.textContent =
                'Cần đánh giá đủ tiêu chí của từng nhóm.';
        } else {
            validation.textContent =
                'Đã đủ số lượng và kết quả để hoàn tất.';
        }
        validation.classList.toggle(
            'kd-invalid',
            !validQuantity || allocated > required
        );
    };
    data.hangHoa.forEach(item => {
        const itemElement = document.createElement('section');
        itemElement.className = 'kd-card kd-item';
        itemElement.dataset.id = item.MaCTKD;
        itemElement.itemData = item;
        itemElement.innerHTML = `
            <h2>${escape(item.TenHang)}</h2>
            <p class="kd-muted">
                ${escape(item.MaHang)}
                · ${escape(item.DanhMuc)}
                · ${escape(item.QuyCach)}
                · Yêu cầu:
                <strong>
                    ${escape(item.SoLuongYeuCauKiemDinh)}
                    ${escape(item.DonViTinh)}
                </strong>
            </p>
            <div class="kd-summary">
                <div>Đã phân nhóm<b class="kd-allocated">0</b></div>
                <div>Đạt<b class="kd-passed">0</b></div>
                <div>Không đạt<b class="kd-failed">0</b></div>
                <div>Chưa kiểm định<b class="kd-pending">0</b></div>
            </div>
            <p class="kd-validation kd-muted" role="status"></p>
            <div class="kd-groups"></div>
            ${readonly ? '' : `
                <button
                    class="kd-button kd-add"
                    type="button"
                >
                    + Thêm nhóm kiểm định
                </button>
            `}
        `;
        const groupContainer =
            itemElement.querySelector('.kd-groups');
        let groups = item.NhomKiemDinh || [];
        const posted = data.duLieuDaNhap?.[item.MaCTKD];
        // Khôi phục dữ liệu người dùng nhập khi server báo lỗi.
        if (!readonly && Array.isArray(posted)) {
            groups = posted.map(group => ({
                SoLuong: group.SoLuong,
                GhiChu: group.GhiChu,
                KetQuaTieuChi: group.TieuChi || {}
            }));
        } else if (!readonly && groups.length === 0) {
            groups = [{
                SoLuong: item.SoLuongYeuCauKiemDinh,
                GhiChu: '',
                KetQuaTieuChi: {}
            }];
        }
        groups.forEach(group => {
            groupContainer.append(createGroup(item, group));
        });
        itemElement.addEventListener('input', () => {
            updateSummary(itemElement);
        });
        itemElement.addEventListener('change', () => {
            updateSummary(itemElement);
        });
        itemElement.addEventListener('click', event => {
            if (readonly) {
                return;
            }
            if (event.target.matches('.kd-remove')) {
                event.target.closest('.kd-group').remove();
                updateSummary(itemElement);
            }
            if (event.target.matches('.kd-add')) {
                if (
                    groupContainer.querySelectorAll('.kd-group').length
                    >= 100
                ) {
                    document.querySelector('#kd-form-error').textContent =
                        'Mỗi mặt hàng chỉ được tối đa 100 nhóm.';
                    return;
                }
                groupContainer.append(createGroup(item));
                updateSummary(itemElement);
            }
        });
        container.append(itemElement);
        updateSummary(itemElement);
    });
    form.addEventListener('submit', () => {
        const payload = {};
        container.querySelectorAll('.kd-item').forEach(itemElement => {
            payload[itemElement.dataset.id] = [
                ...itemElement.querySelectorAll('.kd-group')
            ].map(group => {
                const criteria = {};
                group.querySelectorAll('.kd-criterion').forEach(row => {
                    criteria[row.dataset.id] = {
                        KetQua: row.querySelector(
                            '.kd-criterion-result'
                        ).value,
                        GhiChu: row.querySelector(
                            '.kd-criterion-note'
                        ).value
                    };
                });
                return {
                    SoLuong: group.querySelector('.kd-qty').value,
                    GhiChu: group.querySelector(
                        '.kd-group-note'
                    ).value,
                    TieuChi: criteria
                };
            });
        });
        document.querySelector('#kd-payload').value =
            JSON.stringify(payload);
    });
};
(function (global) {
    'use strict';
    const api = {};
    api.summarize = function (required, unit, groups) {
        let assigned = 0, pass = 0, fail = 0, valid = true;
        const conclusions = groups.map(g => {
            const raw = String(g.qty), qty = Number(raw);
            const ok = /^\d{1,9}(?:\.\d{1,3})?$/.test(raw) && qty > 0 && Number.isFinite(qty) && (unit === 'Mét' || Number.isInteger(qty));
            if (!ok) valid = false;
            const complete = g.results.length > 0 && g.results.every(v => v === 'Đạt' || v === 'Không đạt');
            const conclusion = complete ? (g.results.includes('Không đạt') ? 'Không đạt' : 'Đạt') : 'Chưa hoàn tất';
            if (ok) {
                const n = Math.round(qty * 1000); assigned += n;
                if (conclusion === 'Đạt') pass += n;
                if (conclusion === 'Không đạt') fail += n;
            }
            return conclusion;
        });
        const total = Math.round(Number(required) * 1000);
        return { assigned, pass, fail, pending: Math.max(0, total - pass - fail), total, valid, conclusions, complete: valid && groups.length > 0 && assigned === total && pass + fail === total };
    };
    api.init = function () {
        const form = document.getElementById('kd-form');
        if (!form || form.dataset.initialized === '1') return;
        const container = document.getElementById('kd-items');
        if (!container) return;
        const readonly = container.dataset.readonly === '1';
        const fmt = n => (n / 1000).toLocaleString('vi-VN');
        const readGroups = item => [...item.querySelector('.kd-groups').children].filter(g => g.classList.contains('kd-group'));
        function update(item) {
            const groups = readGroups(item);
            const summary = api.summarize(item.dataset.qty, item.dataset.unit, groups.map(g => ({ qty: g.querySelector('.kd-qty').value, results: [...g.querySelectorAll('.kd-criterion-result')].map(s => s.value) })));
            groups.forEach((g, n) => {
                g.querySelector('.kd-group-title').textContent = 'Nhóm ' + (n + 1);
                const badge = g.querySelector('.kd-conclusion'); badge.textContent = summary.conclusions[n];
                badge.classList.toggle('kd-result-pass', summary.conclusions[n] === 'Đạt');
                badge.classList.toggle('kd-result-fail', summary.conclusions[n] === 'Không đạt');
            });
            [['allocated', 'assigned'], ['passed', 'pass'], ['failed', 'fail'], ['pending', 'pending']].forEach(([cls, key]) => item.querySelector('.kd-' + cls).textContent = fmt(summary[key]));
            const message = item.querySelector('.kd-validation');
            message.textContent = !summary.valid ? 'Kiểm tra số lượng: phải lớn hơn 0, đơn vị đếm phải nguyên.' : summary.assigned > summary.total ? 'Tổng nhóm vượt số lượng yêu cầu.' : summary.assigned < summary.total ? 'Còn ' + fmt(summary.total - summary.assigned) + ' ' + item.dataset.unit + ' chưa phân nhóm.' : summary.complete ? 'Đã đủ số lượng và kết quả của tất cả tiêu chí.' : 'Cần đánh giá đủ tiêu chí của từng nhóm.';
            message.classList.toggle('kd-invalid', !summary.valid || summary.assigned > summary.total);
            return summary;
        }
        const items = [...container.querySelectorAll('.kd-item')];
        items.forEach(item => {
            item.addEventListener('input', () => update(item));
            item.addEventListener('change', () => update(item));
            item.addEventListener('click', e => {
                if (readonly) return;
                const remove = e.target.closest('.kd-remove'), add = e.target.closest('.kd-add');
                if (remove) { remove.closest('.kd-group').remove(); update(item); }
                if (add) {
                    if (readGroups(item).length >= 100) { document.getElementById('kd-form-error').textContent = 'Tối đa 100 nhóm/mặt hàng.'; return; }
                    item.querySelector('.kd-groups').append(item.querySelector('.kd-template').content.cloneNode(true)); update(item);
                }
            });
            update(item);
        });
        form.addEventListener('submit', e => {
            if (readonly) { e.preventDefault(); return; }
            const error = document.getElementById('kd-form-error'); error.textContent = '';
            const complete = e.submitter && e.submitter.value === 'hoan_tat';
            if (!items.length || (complete && items.some(item => !update(item).complete))) {
                e.preventDefault(); error.textContent = 'Chưa thể hoàn tất: kiểm tra số lượng và đánh giá đủ tiêu chí của mọi mặt hàng.'; return;
            }
            const payload = {};
            items.forEach(item => {
                payload[item.dataset.id] = readGroups(item).map(g => {
                    const criteria = {};
                    g.querySelectorAll('.kd-criterion').forEach(row => criteria[row.dataset.id] = { KetQua: row.querySelector('select').value, GhiChu: row.querySelector('textarea').value });
                    return { SoLuong: g.querySelector('.kd-qty').value, GhiChu: g.querySelector('.kd-group-note').value, TieuChi: criteria };
                });
            });
            document.getElementById('kd-payload').value = JSON.stringify(payload);
        });
        form.dataset.initialized = '1';
        if (!readonly && items.length) form.querySelectorAll('[data-kd-save]').forEach(b => b.disabled = false);
    };
    global.KiemDinhPage = api;
})(typeof window !== 'undefined' ? window : globalThis);
