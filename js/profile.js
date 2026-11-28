document.addEventListener("DOMContentLoaded", function () {
  var page = document.getElementById("profile-page");
  if (!page) {
    return;
  }

  var modal = document.getElementById("avatar-modal");
  var openBtn = document.getElementById("open-avatar-modal");
  var closeBtn = document.getElementById("close-avatar-modal");
  var choiceInput = document.getElementById("avatar-choice-input");
  var choiceInitials = document.getElementById("choice-initials");
  var choicePhoto = document.getElementById("choice-photo");
  var fileInput = document.getElementById("avatar-file-input");
  var fileLabel = document.getElementById("avatar-file-name");

  if (fileInput) {
    fileInput.setAttribute("accept", ".jpg,.jpeg,.png,image/jpeg,image/png");
  }

  if (openBtn && modal) {
    openBtn.addEventListener("click", function () {
      modal.classList.add("is-open");
    });
  }

  if (closeBtn && modal) {
    closeBtn.addEventListener("click", function () {
      modal.classList.remove("is-open");
    });
  }

  if (choiceInitials && choicePhoto && choiceInput) {
    choiceInitials.addEventListener("click", function () {
      choiceInitials.classList.add("is-selected");
      choicePhoto.classList.remove("is-selected");
      choiceInput.value = "initials";
    });

    choicePhoto.addEventListener("click", function () {
      choicePhoto.classList.add("is-selected");
      choiceInitials.classList.remove("is-selected");
      choiceInput.value = "photo";
    });
  }

  if (fileInput && fileLabel) {
    fileInput.addEventListener("change", function () {
      if (fileInput.files && fileInput.files.length > 0) {
        fileLabel.textContent = "File dipilih: " + fileInput.files[0].name;
        if (choicePhoto && choiceInitials && choiceInput) {
          choicePhoto.classList.add("is-selected");
          choiceInitials.classList.remove("is-selected");
          choiceInput.value = "photo";
        }
      } else {
        fileLabel.textContent = "Belum ada file dipilih";
      }
    });
  }
});