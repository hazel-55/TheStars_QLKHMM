(function () {
  "use strict";

  var form = document.querySelector("[data-xnl-form]");

  if (!form) {
    return;
  }

  form.addEventListener("submit", function (event) {
    var maKeHoach = form.querySelector('input[name="makehoach"]');
    var submitButton = form.querySelector("[data-xnl-submit]");
    var action = form.getAttribute("data-action");
    var message;

    if (!maKeHoach || maKeHoach.value.trim() === "") {
      event.preventDefault();
      alert("Không tìm thấy mã kế hoạch sản xuất.");
      return;
    }

    if (action === "edit") {
      message = "Bạn có muốn chỉnh sửa phiếu yêu cầu xuất nguyên liệu không?";
    } else {
      message =
        "Bạn có chắc chắn muốn lập yêu cầu xuất nguyên liệu cho kế hoạch này không?";
    }

    if (!window.confirm(message)) {
      event.preventDefault();
      return;
    }

    if (submitButton) {
      submitButton.disabled = true;

      if (action === "edit") {
        submitButton.textContent = "Đang lưu thay đổi...";
      } else {
        submitButton.textContent = "Đang gửi yêu cầu...";
      }
    }
  });
})();
