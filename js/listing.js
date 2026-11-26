/*
 * CampRent – listing page behaviour. Keep your existing js/script.js
 * (carousel arrows, header hide-on-scroll); this file only adds:
 *   1. search placeholder (XHTML 1.0 Strict has no "placeholder" attribute)
 *   2. filter dropdowns submit the form as soon as they change
 *   3. Add to Cart without reloading the page
 */
document.addEventListener('DOMContentLoaded', function () {
  var each = Array.prototype.forEach;

  // 1. Search placeholder
  var search = document.getElementById('search-input');
  if (search) {
    search.setAttribute('placeholder', 'Cari Tenda, Tracking pool, Carrier');
  }

  // 2. Filters apply immediately
  var filterForm = document.getElementById('filter-form');
  if (filterForm) {
    var selects = filterForm.querySelectorAll('select');

    // Leave empty dropdowns (and the default sort) out of the URL: ?cat=tent instead of ?cat=tent&price=&brand=...
    var tidy = function () {
      each.call(selects, function (s) {
        s.disabled = (s.value === '' || (s.name === 'sort' && s.value === 'newest'));
      });
    };
    var restore = function () {
      each.call(selects, function (s) { s.disabled = false; });
    };

    filterForm.addEventListener('submit', tidy);          // the "Filter" button
    each.call(selects, function (select) {
      select.addEventListener('change', function () {
        tidy();
        filterForm.submit();                               // submit() does not fire the event above
      });
    });
    window.addEventListener('pageshow', restore);          // back button: re-enable the dropdowns
  }

  // 3. Add to Cart (falls back to a normal form post if JavaScript is off)
  var toast = document.getElementById('toast');
  var toastTimer = null;

  function showToast(message, isError) {
    if (!toast) { return; }
    toast.textContent = message;
    toast.className = 'toast is-visible' + (isError ? ' toast--error' : '');
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(function () {
      toast.className = 'toast' + (isError ? ' toast--error' : '');
    }, 2600);
  }

  function setBadge(count) {
    var badge = document.getElementById('cart-badge');
    if (!badge) { return; }
    badge.textContent = count;
    badge.className = 'cart__badge' + (count > 0 ? '' : ' is-empty');
  }

  if (toast && toast.className.indexOf('is-visible') !== -1) {
    // message left by a non-JavaScript form post
    toastTimer = window.setTimeout(function () {
      toast.className = 'toast' + (toast.className.indexOf('toast--error') !== -1 ? ' toast--error' : '');
    }, 2600);
  }

  each.call(document.querySelectorAll('form.add-to-cart'), function (form) {
    form.addEventListener('submit', function (event) {
      if (!window.fetch || !window.FormData) { return; }   // old browser: normal post
      event.preventDefault();

      var button = form.querySelector('button');
      if (button) { button.disabled = true; }

      fetch(form.getAttribute('action'), {
        method: 'POST',
        body: new FormData(form),
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (response) { return response.json(); })
        .then(function (data) {
          showToast(data.message, !data.ok);
          if (typeof data.cartCount === 'number') { setBadge(data.cartCount); }
        })
        .catch(function () {
          showToast('Could not add to cart. Please try again.', true);
        })
        .then(function () {
          if (button) { button.disabled = false; }
        });
    });
  });
});
