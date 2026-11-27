document.addEventListener("DOMContentLoaded", function () {
  var cartPage = document.getElementById("cart-page");
  if (!cartPage) {
    return;
  }

  // XHTML 1.0 Strict placeholders
  var placeholders = {
    "cart-name": "Your name",
    "cart-wa": "08xxxxxxxxxx",
    "cart-address": "Street, house number, area, city and postal code",
    "cart-notes": "Additional notes (optional)"
  };
  Object.keys(placeholders).forEach(function (id) {
    var el = document.getElementById(id);
    if (el) {
      el.setAttribute("placeholder", placeholders[id]);
    }
  });

  var csrfInput      = document.getElementById("cart-csrf");
  var csrf           = csrfInput ? csrfInput.value : "";
  var pickupSelect   = document.getElementById("cart-pickup");
  var addressGroup   = document.getElementById("delivery-address-group");
  var feeHintText    = document.getElementById("delivery-fee-hint-text");
  var bulkStart      = document.getElementById("bulk-start-date");
  var bulkEnd        = document.getElementById("bulk-end-date");
  var subtotalEl     = document.getElementById("summary-subtotal");
  var deliveryEl     = document.getElementById("summary-delivery");
  var taxEl          = document.getElementById("summary-tax");
  var totalEl        = document.getElementById("summary-total");
  var qrisTotalEl    = document.getElementById("qris-total-amount");
  var toastEl        = document.getElementById("toast");

  function formatRupiah(amount) {
    var num = Math.round(Number(amount) || 0);
    return "Rp " + num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
  }

  function showCartToast(msg, isError) {
    if (!toastEl) {
      return;
    }
    toastEl.textContent = msg;
    toastEl.className = isError ? "toast is-visible toast--error" : "toast is-visible";
    setTimeout(function () {
      toastEl.className = isError ? "toast toast--error" : "toast";
    }, 2600);
  }

  function updateNavbarBadge(count) {
    var badge = document.getElementById("cart-badge");
    if (!badge) {
      return;
    }
    badge.textContent = count;
    badge.className = count > 0 ? "cart__badge" : "cart__badge is-empty";
  }

  function calcDays(startStr, endStr) {
    if (!startStr || !endStr) {
      return 1;
    }
    var s = new Date(startStr + "T00:00:00");
    var e = new Date(endStr + "T00:00:00");
    var diff = Math.round((e - s) / 86400000);
    return diff > 0 ? diff : 1;
  }

  function formatShortDate(dateStr) {
    if (!dateStr) {
      return "";
    }
    var months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    var parts = dateStr.split("-");
    if (parts.length !== 3) {
      return dateStr;
    }
    var mIdx = parseInt(parts[1], 10) - 1;
    return parts[2] + " " + (months[mIdx] || parts[1]);
  }

  function collectDatesMap() {
    var map = {};
    var rows = document.querySelectorAll(".cart-row");
    rows.forEach(function (row) {
      var keyInput   = row.querySelector(".js-item-key");
      var startInput = row.querySelector(".js-start-date");
      var endInput   = row.querySelector(".js-end-date");
      if (keyInput && startInput && endInput) {
        map[keyInput.value] = {
          start_date: startInput.value,
          end_date: endInput.value
        };
      }
    });
    return map;
  }

  function syncDatesToServer() {
    var fd = new FormData();
    fd.append("csrf", csrf);
    fd.append("action", "update_dates");
    fd.append("dates_json", JSON.stringify(collectDatesMap()));
    fetch("cart_action.php", {
      method: "POST",
      body: fd,
      credentials: "same-origin"
    });
  }

  function recalculateSummary() {
    var rows = document.querySelectorAll(".cart-row");
    var rentalSubtotal = 0;

    rows.forEach(function (row) {
      var priceInput = row.querySelector(".js-item-price");
      var qtyEl      = row.querySelector(".js-qty-val");
      var startInput = row.querySelector(".js-start-date");
      var endInput   = row.querySelector(".js-end-date");
      var badgeEl    = row.querySelector(".js-days-badge");
      var subEl      = row.querySelector(".js-row-subtotal");

      var price = priceInput ? parseInt(priceInput.value, 10) : 0;
      var qty   = qtyEl ? parseInt(qtyEl.textContent, 10) : 1;
      var sVal  = startInput ? startInput.value : "";
      var eVal  = endInput ? endInput.value : "";
      var days  = calcDays(sVal, eVal);

      if (badgeEl) {
        badgeEl.textContent = formatShortDate(sVal) + " - " + formatShortDate(eVal) + " · " + days + (days === 1 ? " day" : " days");
      }

      var rowTotal = price * days * qty;
      rentalSubtotal += rowTotal;

      if (subEl) {
        subEl.textContent = formatRupiah(rowTotal);
      }
    });

    var isDelivery  = !pickupSelect || pickupSelect.value === "Delivery";
    var deliveryFee = isDelivery ? 25000 : 0;
    var taxAmount   = Math.round((rentalSubtotal + deliveryFee) * 0.11);
    var grandTotal  = rentalSubtotal + deliveryFee + taxAmount;

    if (addressGroup) {
      addressGroup.style.display = isDelivery ? "" : "none";
    }
    if (feeHintText) {
      feeHintText.textContent = isDelivery
        ? "The delivery fee will be added to your total."
        : "Self pickup selected — no delivery fee will be charged.";
    }

    if (subtotalEl) { subtotalEl.textContent = formatRupiah(rentalSubtotal); }
    if (deliveryEl) { deliveryEl.textContent = formatRupiah(deliveryFee); }
    if (taxEl)      { taxEl.textContent      = formatRupiah(taxAmount); }
    if (totalEl)    { totalEl.textContent    = formatRupiah(grandTotal); }
    if (qrisTotalEl){ qrisTotalEl.textContent= formatRupiah(grandTotal); }
  }

  if (pickupSelect) {
    pickupSelect.addEventListener("change", recalculateSummary);
    recalculateSummary();
  }

  // Apply dates to all items shortcut
  function applyBulkDates() {
    if (!bulkStart || !bulkEnd || !bulkStart.value || !bulkEnd.value) {
      return;
    }
    var rows = document.querySelectorAll(".cart-row");
    rows.forEach(function (row) {
      var s = row.querySelector(".js-start-date");
      var e = row.querySelector(".js-end-date");
      if (s) { s.value = bulkStart.value; }
      if (e) { e.value = bulkEnd.value; }
    });
    recalculateSummary();
    syncDatesToServer();
  }

  if (bulkStart) { bulkStart.addEventListener("change", applyBulkDates); }
  if (bulkEnd)   { bulkEnd.addEventListener("change", applyBulkDates); }

  // Per-item date changes
  document.querySelectorAll(".js-start-date, .js-end-date").forEach(function (input) {
    input.addEventListener("change", function () {
      recalculateSummary();
      syncDatesToServer();
    });
  });

  // Quantity stepper (+ / -) and Remove item buttons
  document.querySelectorAll(".cart-row").forEach(function (row) {
    var keyInput = row.querySelector(".js-item-key");
    var maxInput = row.querySelector(".js-item-max");
    var qtyEl    = row.querySelector(".js-qty-val");
    var minusBtn = row.querySelector(".js-qty-minus");
    var plusBtn  = row.querySelector(".js-qty-plus");
    var remBtn   = row.querySelector(".js-remove-item");

    var itemKey  = keyInput ? keyInput.value : "";
    var maxStock = maxInput ? parseInt(maxInput.value, 10) : 99;

    function sendQtyUpdate(nextQty) {
      var fd = new FormData();
      fd.append("csrf", csrf);
      fd.append("action", "update_qty");
      fd.append("item_key", itemKey);
      fd.append("qty", String(nextQty));

      fetch("cart_action.php", {
        method: "POST",
        body: fd,
        credentials: "same-origin"
      })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (data.ok && qtyEl) {
            qtyEl.textContent = data.qty;
            updateNavbarBadge(data.cartCount);
            recalculateSummary();
          }
        });
    }

    if (minusBtn) {
      minusBtn.addEventListener("click", function () {
        var cur = parseInt(qtyEl.textContent, 10) || 1;
        if (cur > 1) {
          sendQtyUpdate(cur - 1);
        }
      });
    }

    if (plusBtn) {
      plusBtn.addEventListener("click", function () {
        var cur = parseInt(qtyEl.textContent, 10) || 1;
        if (cur < maxStock) {
          sendQtyUpdate(cur + 1);
        } else {
          showCartToast("Maksimal stok tersedia: " + maxStock, true);
        }
      });
    }

    if (remBtn) {
      remBtn.addEventListener("click", function () {
        var fd = new FormData();
        fd.append("csrf", csrf);
        fd.append("action", "remove_item");
        fd.append("item_key", itemKey);

        fetch("cart_action.php", {
          method: "POST",
          body: fd,
          credentials: "same-origin"
        })
          .then(function (res) { return res.json(); })
          .then(function (data) {
            if (data.ok) {
              row.parentNode.removeChild(row);
              updateNavbarBadge(data.cartCount);
              if (data.cartCount <= 0) {
                window.location.reload();
              } else {
                recalculateSummary();
                showCartToast(data.message, false);
              }
            }
          });
      });
    }
  });

  // Order via WhatsApp button (works for both guests and logged-in users)
  var waBtn = document.getElementById("btn-whatsapp");
  if (waBtn) {
    waBtn.addEventListener("click", function () {
      var nameVal   = (document.getElementById("cart-name") || {}).value || "-";
      var waVal     = (document.getElementById("cart-wa") || {}).value || "-";
      var pickupVal = pickupSelect ? pickupSelect.value : "Delivery";
      var addrVal   = (document.getElementById("cart-address") || {}).value || "-";
      var notesVal  = (document.getElementById("cart-notes") || {}).value || "-";

      var lines = [
        "Halo CampRent, saya ingin menyewa perlengkapan berikut:",
        "",
        "Nama: " + nameVal,
        "No. WA: " + waVal,
        "Tipe Pengambilan: " + pickupVal
      ];
      if (pickupVal === "Delivery") {
        lines.push("Alamat: " + addrVal);
      }
      lines.push("", "Daftar Alat:");

      document.querySelectorAll(".cart-row").forEach(function (row, idx) {
        var title = row.querySelector(".cart-item__name").textContent;
        var badge = row.querySelector(".js-days-badge").textContent;
        var qty   = row.querySelector(".js-qty-val").textContent;
        var sub   = row.querySelector(".js-row-subtotal").textContent;
        lines.push((idx + 1) + ". " + title + " (" + badge + ") x" + qty + " = " + sub);
      });

      lines.push(
        "",
        "Rental Subtotal: " + (subtotalEl ? subtotalEl.textContent : ""),
        "Delivery Fee: " + (deliveryEl ? deliveryEl.textContent : ""),
        "Tax 11%: " + (taxEl ? taxEl.textContent : ""),
        "TOTAL: " + (totalEl ? totalEl.textContent : ""),
        "Metode Pembayaran: QRIS",
        "Catatan: " + notesVal
      );

      var waUrl = "https://wa.me/6285395742403?text=" + encodeURIComponent(lines.join("\n"));
      window.open(waUrl, "_blank");
    });
  }

  // QRIS Payment Popup Modal
  var checkoutBtn = document.getElementById("btn-checkout");
  var qrisModal   = document.getElementById("qris-modal");
  var qrisBackdrop= document.getElementById("qris-backdrop");
  var qrisCloseBtn= document.getElementById("qris-close-btn");
  var qrisCancel  = document.getElementById("qris-cancel-btn");
  var qrisConfirm = document.getElementById("qris-confirm-btn");
  var qrisStepPay = document.getElementById("qris-step-pay");
  var qrisStepDone= document.getElementById("qris-step-done");
  var qrisTxCode  = document.getElementById("qris-tx-code");

  function openQrisModal() {
    var nameVal   = ((document.getElementById("cart-name") || {}).value || "").trim();
    var waVal     = ((document.getElementById("cart-wa") || {}).value || "").trim();
    var pickupVal = pickupSelect ? pickupSelect.value : "Delivery";
    var addrVal   = ((document.getElementById("cart-address") || {}).value || "").trim();

    if (!nameVal || !waVal) {
      showCartToast("Harap isi Name dan WhatsApp Number terlebih dahulu.", true);
      return;
    }
    if (pickupVal === "Delivery" && !addrVal) {
      showCartToast("Harap isi Delivery Address terlebih dahulu.", true);
      return;
    }

    recalculateSummary();
    if (qrisStepPay)  { qrisStepPay.className = "qris-modal__body"; }
    if (qrisStepDone) { qrisStepDone.className = "qris-modal__body qris-modal__done is-hidden"; }
    if (qrisModal)    { qrisModal.className = "qris-modal is-open"; }
  }

  function closeQrisModal() {
    if (qrisModal) {
      qrisModal.className = "qris-modal";
    }
  }

  if (checkoutBtn)  { checkoutBtn.addEventListener("click", openQrisModal); }
  if (qrisBackdrop) { qrisBackdrop.addEventListener("click", closeQrisModal); }
  if (qrisCloseBtn) { qrisCloseBtn.addEventListener("click", closeQrisModal); }
  if (qrisCancel)   { qrisCancel.addEventListener("click", closeQrisModal); }

  if (qrisConfirm) {
    qrisConfirm.addEventListener("click", function () {
      qrisConfirm.disabled = true;
      qrisConfirm.textContent = "Memproses...";

      var fd = new FormData();
      fd.append("csrf", csrf);
      fd.append("action", "checkout_qris");
      fd.append("nama_penerima", (document.getElementById("cart-name") || {}).value || "");
      fd.append("no_wa", (document.getElementById("cart-wa") || {}).value || "");
      fd.append("pickup_type", pickupSelect ? pickupSelect.value : "Delivery");
      fd.append("alamat_pengiriman", (document.getElementById("cart-address") || {}).value || "");
      fd.append("catatan_pesanan", (document.getElementById("cart-notes") || {}).value || "");
      fd.append("dates_json", JSON.stringify(collectDatesMap()));

      fetch("cart_action.php", {
        method: "POST",
        body: fd,
        credentials: "same-origin"
      })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          qrisConfirm.disabled = false;
          qrisConfirm.textContent = "Saya Sudah Bayar";

          if (!data.ok) {
            showCartToast(data.message, true);
            return;
          }

          updateNavbarBadge(0);
          if (qrisTxCode)   { qrisTxCode.textContent = data.kode_transaksi; }
          if (qrisStepPay)  { qrisStepPay.className = "qris-modal__body is-hidden"; }
          if (qrisStepDone) { qrisStepDone.className = "qris-modal__body qris-modal__done"; }
        })
        .catch(function () {
          qrisConfirm.disabled = false;
          qrisConfirm.textContent = "Saya Sudah Bayar";
          showCartToast("Terjadi kesalahan saat memproses pembayaran QRIS.", true);
        });
    });
  }
});