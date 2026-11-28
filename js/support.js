document.addEventListener("DOMContentLoaded", function () {
  var form = document.getElementById("support-form");
  if (!form) {
    return;
  }

  // Placeholders from the Figma design (set here so the XHTML 1.0 Strict template stays valid)
  var placeholders = {
    "support-nama": "Nama penyewa",
    "support-kontak": "0812xxxx / email@contoh.com",
    "support-kode": "Contoh: CR-2026-104",
    "support-pesan": "Tuliskan rencana gunung tujuan, tanggal berangkat, atau kendala yang dialami..."
  };
  Object.keys(placeholders).forEach(function (id) {
    var field = document.getElementById(id);
    if (field) {
      field.setAttribute("placeholder", placeholders[id]);
    }
  });

  // Quick check before sending. The server checks everything again.
  form.addEventListener("submit", function (event) {
    var required = ["support-nama", "support-kontak", "support-pesan"];
    var missing = required.some(function (id) {
      return document.getElementById(id).value.replace(/\s+/g, "") === "";
    });
    if (!missing) {
      return;
    }
    event.preventDefault();
    var box = form.parentNode.querySelector(".support-alert");
    if (!box) {
      box = document.createElement("div");
      box.className = "support-alert";
      form.parentNode.insertBefore(box, form);
    }
    box.textContent = "Nama lengkap, WhatsApp atau email, dan detail pesan wajib diisi.";
  });
});
