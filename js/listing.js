/*
 * listing.js - the extra JavaScript for the product pages.
 * Keep your own js/script.js (carousel arrows, header hiding). This file only:
 *   1. puts the placeholder text in the search box
 *   2. sends the filter form as soon as you pick a dropdown value
 *   3. adds to cart without reloading the page
 */

// Wait until the whole page is loaded before touching anything on it
document.addEventListener('DOMContentLoaded', function () {

  // ---------- 1. Search box placeholder ----------
  // (XHTML 1.0 Strict has no "placeholder" attribute, so JavaScript adds it.)
  var searchBox = document.getElementById('search-input');
  if (searchBox) {
    searchBox.setAttribute('placeholder', 'Cari Tenda, Tracking pool, Carrier');
  }

  // ---------- 2. Filter dropdowns ----------
  var filterForm = document.getElementById('filter-form');
  if (filterForm) {
    var dropdowns = filterForm.querySelectorAll('select');

    // A disabled dropdown is not sent, so the address stays short
    // (?cat=tent instead of ?cat=tent&price=&brand=...).
    function switchOffEmptyDropdowns() {
      for (var i = 0; i < dropdowns.length; i++) {
        var d = dropdowns[i];
        if (d.value === '' || (d.name === 'sort' && d.value === 'newest')) {
          d.disabled = true;
        }
      }
    }

    // Undo the above (needed when the visitor presses the browser's Back button)
    function switchAllDropdownsOn() {
      for (var i = 0; i < dropdowns.length; i++) {
        dropdowns[i].disabled = false;
      }
    }

    // When any dropdown changes: tidy up, then send the form
    for (var i = 0; i < dropdowns.length; i++) {
      dropdowns[i].addEventListener('change', function () {
        switchOffEmptyDropdowns();
        filterForm.submit();
      });
    }
    // The "Filter" button
    filterForm.addEventListener('submit', switchOffEmptyDropdowns);
    // Back button
    window.addEventListener('pageshow', switchAllDropdownsOn);
  }

  // ---------- 3. Add to Cart without reloading ----------
  var toast = document.getElementById('toast');   // the small message box
  var toastTimer = null;

  // Show a message for about 2.6 seconds, then hide it
  function showToast(message, isError) {
    if (!toast) { return; }
    toast.textContent = message;
    if (isError) {
      toast.className = 'toast is-visible toast--error';
    } else {
      toast.className = 'toast is-visible';
    }
    clearTimeout(toastTimer);
    toastTimer = setTimeout(hideToast, 2600);
  }

  function hideToast() {
    if (toast.className.indexOf('toast--error') !== -1) {
      toast.className = 'toast toast--error';
    } else {
      toast.className = 'toast';
    }
  }

  // Update the yellow number on the cart icon
  function updateCartBadge(count) {
    var badge = document.getElementById('cart-badge');
    if (!badge) { return; }
    badge.textContent = count;
    if (count > 0) {
      badge.className = 'cart__badge';
    } else {
      badge.className = 'cart__badge is-empty';
    }
  }

  // If the server left a message (visitor had JavaScript off earlier), hide it after a while
  if (toast && toast.className.indexOf('is-visible') !== -1) {
    toastTimer = setTimeout(hideToast, 2600);
  }

  // Do this for every "Add to Cart" form on the page
  var cartForms = document.querySelectorAll('form.add-to-cart');
  for (var f = 0; f < cartForms.length; f++) {
    cartForms[f].addEventListener('submit', function (event) {
      // fetch() is the modern way to send data in the background.
      // Very old browsers do not have it; then we let the normal form send instead.
      if (!window.fetch) { return; }

      event.preventDefault();                       // stop the normal page reload
      var form = event.target;
      var button = form.querySelector('button');
      button.disabled = true;                       // stop double clicks

      fetch(form.getAttribute('action'), {
        method: 'POST',
        body: new FormData(form),                   // the form's hidden fields
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (response) { return response.json(); })
        .then(function (data) {
          showToast(data.message, !data.ok);
          if (typeof data.cartCount === 'number') {
            updateCartBadge(data.cartCount);
          }
          button.disabled = false;
        })
        .catch(function () {
          showToast('Could not add to cart. Please try again.', true);
          button.disabled = false;
        });
    });
  }
});
