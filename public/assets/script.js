document.addEventListener('click', (event) => {
  const openButton = event.target.closest('[data-modal-open]');
  const closeButton = event.target.closest('[data-modal-close]');
  const overlay = event.target.classList.contains('modal-overlay') ? event.target : null;

  if (openButton) {
    const modal = document.getElementById(openButton.dataset.modalOpen);
    if (modal) {
      modal.classList.add('is-open');
      modal.setAttribute('aria-hidden', 'false');
    }
  }

  if (closeButton) {
    const modal = closeButton.closest('.modal-overlay');
    if (modal) {
      modal.classList.remove('is-open');
      modal.setAttribute('aria-hidden', 'true');
    }
  }

  if (overlay) {
    overlay.classList.remove('is-open');
    overlay.setAttribute('aria-hidden', 'true');
  }
});

document.addEventListener('keydown', (event) => {
  if (event.key !== 'Escape') {
    return;
  }

  document.querySelectorAll('.modal-overlay.is-open').forEach((modal) => {
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
  });
});
