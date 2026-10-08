/**
 * @module @ueberbit/shorturls/short-url-actions
 */
class ShortUrlActions {
  constructor() {
    document.addEventListener('click', (event) => {
      const target = event.target.closest('[data-shorturl-confirm]');
      if (!target) {
        return;
      }

      const message = target.getAttribute('data-shorturl-confirm');
      if (!confirm(message)) {
        event.preventDefault();
      }
    });
  }
}

export default new ShortUrlActions();
