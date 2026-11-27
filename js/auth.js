document.addEventListener("DOMContentLoaded", function () {
  // Apply Figma placeholder hints cleanly while keeping XHTML 1.0 Strict valid
  var placeholders = {
    "login-email": "nama@email.com",
    "login-password": "Masukkan password",
    "reg-nama": "Cth: Give Run Rak BOOM",
    "reg-username": "Cth: unemployed guy",
    "reg-hp": "Cth: 081234567890",
    "reg-email": "Cth: example@email.com",
    "reg-password": "Masukkan password",
    "reg-confirm": "Masukkan password"
  };

  Object.keys(placeholders).forEach(function (id) {
    var input = document.getElementById(id);
    if (input) {
      input.setAttribute("placeholder", placeholders[id]);
    }
  });

  // Show / hide password eye toggles
  var toggles = document.querySelectorAll(".auth-password__toggle");
  toggles.forEach(function (btn) {
    btn.addEventListener("click", function () {
      var wrapper = btn.parentNode;
      var input = wrapper.querySelector("input");
      if (!input) {
        return;
      }
      if (input.type === "password") {
        input.type = "text";
        btn.setAttribute("title", "Sembunyikan password");
      } else {
        input.type = "password";
        btn.setAttribute("title", "Tampilkan password");
      }
    });
  });
});