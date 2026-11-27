/*
 * support.js - the extra JavaScript for the Support page.
 * Keep js/script.js and js/listing.js. This file only:
 *   1. puts the placeholder texts in the form (XHTML 1.0 Strict has no "placeholder" attribute)
 *   2. checks the form before it is sent (the server checks again, so this is only a help)
 *   3. stops double clicks on the send button
 */
document.addEventListener('DOMContentLoaded', function () {

  var form = document.getElementById('support-form');
  if (!form) { return; }

  // ---------- 1. Placeholders ----------
  var placeholders = {
    'sup-nama': 'Nama penyewa',
    'sup-kontak': '0812xxxx / email@contoh.com',
    'sup-kode': 'Contoh: CR-2026-104',
    'sup-pesan': 'Tuliskan rencana gunung tujuan, tanggal berangkat, atau kendala yang dialami...'
  };
  Object.keys(placeholders).forEach(function (id) {
    var field = document.getElementById(id);
    if (field) {
      field.setAttribute('placeholder', placeholders[id]);
    }
  });

  // ---------- 2. Checking the form ----------
  var nameBox = document.getElementById('sup-nama');
  var contactBox = document.getElementById('sup-kontak');
  var messageBox = document.getElementById('sup-pesan');
  var submitButton = document.getElementById('sup-submit');

  var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  var phonePattern = /^(\+62|62|0)8[0-9]{7,12}$/;

  // Shows (or clears, when text is empty) the red text under one field
  function setError(box, errorId, text) {
    var field = box.parentNode;
    document.getElementById(errorId).textContent = text;
    if (text === '') {
      field.className = field.className.replace(' has-error', '');
    } else if (field.className.indexOf('has-error') === -1) {
      field.className = field.className + ' has-error';
    }
  }

  // Returns true when everything is fine
  function checkForm() {
    var ok = true;
    var phone = contactBox.value.replace(/[\s-]/g, '');

    if (nameBox.value.replace(/^\s+|\s+$/g, '').length < 3) {
      setError(nameBox, 'err-nama', 'Nama minimal 3 karakter.');
      ok = false;
    } else {
      setError(nameBox, 'err-nama', '');
    }

    if (!emailPattern.test(contactBox.value) && !phonePattern.test(phone)) {
      setError(contactBox, 'err-kontak', 'Isi nomor WhatsApp (08xx) atau email yang valid.');
      ok = false;
    } else {
      setError(contactBox, 'err-kontak', '');
    }

    var length = messageBox.value.replace(/^\s+|\s+$/g, '').length;
    if (length < 10 || length > 2000) {
      setError(messageBox, 'err-pesan', 'Detail pesan 10 sampai 2000 karakter.');
      ok = false;
    } else {
      setError(messageBox, 'err-pesan', '');
    }

    return ok;
  }

  form.addEventListener('submit', function (event) {
    if (!checkForm()) {
      event.preventDefault();                       // stop sending, show the red texts
      var firstError = form.querySelector('.has-error input, .has-error textarea');
      if (firstError) { firstError.focus(); }
      return;
    }
    // ---------- 3. No double clicks ----------
    submitButton.disabled = true;
    submitButton.textContent = 'Mengirim...';
  });

  // Back button: the page comes from the cache, so switch the button on again
  window.addEventListener('pageshow', function () {
    submitButton.disabled = false;
    submitButton.textContent = 'Kirim ke Tim Support';
  });
});
