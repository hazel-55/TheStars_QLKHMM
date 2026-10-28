"use strict";

document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("personal-form");

    // Trang chủ và trang xem không có form chỉnh sửa.
    if (!form) {
        return;
    }

    const hoTen = document.getElementById("HoTen");
    const soDienThoai = document.getElementById("SoDienThoai");
    const nutLuu = document.getElementById("personal-save");
    const nutHuy = document.getElementById("personal-cancel");

    const noiDungNutLuu = nutLuu.textContent;

    function layDuLieuForm() {
        return JSON.stringify(Array.from(new FormData(form).entries()));
    }

    const duLieuBanDau = layDuLieuForm();

    function kiemTraHoTen() {
        hoTen.setCustomValidity("");

        if (hoTen.value.trim() === "") {
            hoTen.setCustomValidity("Vui lòng nhập họ và tên.");
        }
    }

    function kiemTraSoDienThoai() {
        const giaTri = soDienThoai.value.trim();

        soDienThoai.setCustomValidity("");

        if (giaTri !== "" && !/^\+?[0-9]{9,15}$/.test(giaTri)) {
            soDienThoai.setCustomValidity(
                "Số điện thoại gồm 9–15 chữ số, có thể bắt đầu bằng +."
            );
        }
    }

    hoTen.addEventListener("input", kiemTraHoTen);
    soDienThoai.addEventListener("input", kiemTraSoDienThoai);

    kiemTraHoTen();
    kiemTraSoDienThoai();

    form.addEventListener("submit", function (event) {
        kiemTraHoTen();
        kiemTraSoDienThoai();

        if (!form.reportValidity()) {
            event.preventDefault();
            return;
        }

        // Tránh bấm lưu nhiều lần trong khi đang gửi.
        nutLuu.disabled = true;
        nutLuu.textContent = "Đang lưu...";
        form.setAttribute("aria-busy", "true");
    });

    nutHuy.addEventListener("click", function (event) {
        const daThayDoi = layDuLieuForm() !== duLieuBanDau;

        if (
            daThayDoi &&
            !window.confirm("Bạn muốn hủy các thay đổi chưa lưu?")
        ) {
            event.preventDefault();
        }
    });

    // Khôi phục nút lưu khi quay lại bằng nút Back của trình duyệt.
    window.addEventListener("pageshow", function () {
        nutLuu.disabled = false;
        nutLuu.textContent = noiDungNutLuu;
        form.removeAttribute("aria-busy");
    });
});