/**
 * Admin notice dismissal.
 *
 * The cross is printed by PHP as a real link, so a JavaScript error on the page
 * can no longer leave a notice impossible to dismiss. This file only upgrades
 * that link to an Ajax request: when the request cannot be sent or fails, the
 * browser follows the link and the server dismisses the notice instead.
 */
(function (window, document) {
  'use strict';

  const LINK_SELECTOR = '.wppd-admin-notice .wppd-notice-dismiss';
  const ACTION = 'wp_parsidate_dismiss_admin_notice';

  function matches(element, selector) {
    var matcher = element.matches || element.msMatchesSelector || element.webkitMatchesSelector;

    return !!matcher && matcher.call(element, selector);
  }

  function closest(element, selector) {
    while (element && element.nodeType === 1) {
      if (matches(element, selector)) {
        return element;
      }
      element = element.parentNode;
    }

    return null;
  }

  function findNotice(element) {
    while (element && element.nodeType === 1) {
      if (matches(element, '.wppd-admin-notice')) {
        return element;
      }
      element = element.parentNode;
    }

    return null;
  }

  function getConfig() {
    var config = window.WpParsiDateAdmin;

    return config && config.ajaxUrl && config.ajaxNonce ? config : null;
  }

  function removeNotice(notice) {
    if (!notice || !notice.parentNode) {
      return;
    }

    notice.style.transition = 'opacity 150ms linear';
    notice.style.opacity = '0';
    window.setTimeout(function () {
      if (notice.parentNode) {
        notice.parentNode.removeChild(notice);
      }
    }, 150);
  }

  function buildPayload(config, notice) {
    return [
      'action=' + encodeURIComponent(ACTION),
      'nonce=' + encodeURIComponent(config.ajaxNonce),
      'id=' + encodeURIComponent(notice.getAttribute('data-notice-id') || ''),
      'dismiss_time=' + encodeURIComponent(notice.getAttribute('data-notice-dismiss-time') || '0')
    ].join('&');
  }

  /**
   * Sends the dismiss request.
   *
   * @param {HTMLAnchorElement} link The dismiss link.
   * @param {Element|null} notice The notice element.
   * @returns {boolean} True when the Ajax request was sent and the click must not navigate.
   */
  function dismiss(link, notice) {
    var config = getConfig();
    var request;

    if (!config || !notice || typeof window.XMLHttpRequest === 'undefined') {
      return false;
    }

    try {
      request = new window.XMLHttpRequest();
      request.open('POST', config.ajaxUrl, true);
      request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    } catch (error) {
      return false;
    }

    request.onreadystatechange = function () {
      if (request.readyState !== 4) {
        return;
      }

      if (request.status >= 200 && request.status < 300) {
        removeNotice(notice);
        return;
      }

      // The server-side handler dismisses the notice as well.
      window.location.href = link.href;
    };

    try {
      request.send(buildPayload(config, notice));
    } catch (error) {
      return false;
    }

    return true;
  }

  document.addEventListener('click', function (event) {
    var link = closest(event.target, LINK_SELECTOR);

    if (!link) {
      return;
    }

    if (!dismiss(link, findNotice(link))) {
      // Let the link itself reach the server.
      return;
    }

    event.preventDefault();
  }, true);
}(window, document));
