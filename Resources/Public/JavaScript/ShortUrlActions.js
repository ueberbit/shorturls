import Notification from '@typo3/backend/notification.js';

/**
 * @module @ueberbit/shorturls/short-url-actions
 */
class ShortUrlActions {
  constructor() {
    document.addEventListener('click', (event) => {
      const target = event.target.closest('[data-shorturl-copy], [data-shorturl-confirm]');
      if (!target) {
        return;
      }

      if (target.hasAttribute('data-shorturl-copy')) {
        event.preventDefault();
        const textToCopy = target.getAttribute('data-shorturl-copy');
        const displayUrl = target.getAttribute('data-shorturl-display') || textToCopy;
        const notificationTitle = target.getAttribute('data-shorturl-notification-title') || 'Copied';
        let notificationMessage = target.getAttribute('data-shorturl-notification-message') || 'Short URL copied to clipboard: %s';
        notificationMessage = notificationMessage.replace('%s', displayUrl);

        navigator.clipboard.writeText(textToCopy).then(() => {
          Notification.success(notificationTitle, notificationMessage, 2);
        });
      } else if (target.hasAttribute('data-shorturl-confirm')) {
        const message = target.getAttribute('data-shorturl-confirm');
        if (!confirm(message)) {
          event.preventDefault();
        }
      }
    });
  }
}

export default new ShortUrlActions();
