document.addEventListener('DOMContentLoaded', function () {
  // Scroll arrow for carousel
  var carousels = document.querySelectorAll('.carousel');
  Array.prototype.forEach.call(carousels, function (carousel) {
    var track = carousel.querySelector('.carousel__track');

    function step() {
      var first = track.firstElementChild;
      return (first ? first.getBoundingClientRect().width : 255) + 15;
    }

    carousel.querySelector('.carousel__btn--prev').addEventListener('click', function () {
      track.scrollBy({ left: -step(), behavior: 'smooth' });
    });
    carousel.querySelector('.carousel__btn--next').addEventListener('click', function () {
      track.scrollBy({ left: step(), behavior: 'smooth' });
    });
  });
  var header = document.querySelector('.header');
  window.addEventListener('scroll', function () {
  var y = window.scrollY;
  // hide after scrolling down past 120px, show again on any scroll up
  header.classList.toggle('header--hidden', y > 100);
}, { passive: true });
});