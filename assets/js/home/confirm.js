document.addEventListener('DOMContentLoaded', () => {
  const form = document.querySelector('[data-confirm-test-delete]');
  const modal = document.querySelector('[data-home-confirm-modal]');

  if (!form || !modal) {
    return;
  }

  const confirmButton = modal.querySelector('[data-home-confirm-delete]');
  const cancelButtons = modal.querySelectorAll('[data-home-confirm-cancel]');
  let submitConfirmed = false;

  const openModal = () => {
    modal.hidden = false;
    document.body.classList.add('home-confirm-open');
    confirmButton?.focus();
  };

  const closeModal = () => {
    modal.hidden = true;
    document.body.classList.remove('home-confirm-open');
  };

  form.addEventListener('submit', (event) => {
    if (submitConfirmed) {
      return;
    }

    event.preventDefault();
    openModal();
  });

  confirmButton?.addEventListener('click', () => {
    submitConfirmed = true;
    closeModal();
    form.requestSubmit();
  });

  cancelButtons.forEach((button) => {
    button.addEventListener('click', closeModal);
  });

  modal.addEventListener('click', (event) => {
    if (event.target === modal) {
      closeModal();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (!modal.hidden && event.key === 'Escape') {
      closeModal();
    }
  });
});
