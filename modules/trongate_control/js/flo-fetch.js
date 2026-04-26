/**
 * floFetch - Cross-origin Fetch Proxy using postMessage
 *
 * Drop-in replacement for fetch() that works when the calling code
 * runs inside an iframe on https://trongate.io, where Chrome blocks
 * direct fetch requests to http://localhost due to Private Network
 * Access (PNA) restrictions.
 *
 * The parent page (http://localhost) already has a postMessage handler
 * (in code-generator.js) that listens for FLO_FETCH messages and
 * responds with FLO_RESPONSE messages.
 *
 * Usage:
 *   floFetch('/your-app/trongate_control-webhooks/inbound', {
 *     method: 'POST',
 *     headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
 *     body: 'action=get_tables'
 *   })
 *   .then(r => r.json())
 *   .then(data => console.log(data));
 */

(function (global) {
  'use strict';

  var TARGET_ORIGIN = 'http://localhost';
  var DEFAULT_TIMEOUT_MS = 30000;

  /**
   * Generate a UUID v4 message ID.
   * Uses crypto.randomUUID if available, otherwise falls back to
   * a simple random hex string.
   */
  function generateMessageId() {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
      return crypto.randomUUID();
    }
    // Fallback: "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx"
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
      var r = (Math.random() * 16) | 0;
      var v = c === 'x' ? r : (r & 0x3) | 0x8;
      return v.toString(16);
    });
  }

  /**
   * Build a minimal Response-like object from the parent's payload.
   *
   * @param {number} status  HTTP status code.
   * @param {string} body    Response body text.
   * @returns {object}  Object with .status, .ok, .text(), .json().
   */
  function buildResponse(status, body) {
    var bodyText = typeof body === 'string' ? body : (body !== null ? String(body) : '');

    return {
      status: status,
      ok: status >= 200 && status < 300,
      text: function () {
        return Promise.resolve(bodyText);
      },
      json: function () {
        return Promise.resolve(JSON.parse(bodyText));
      }
    };
  }

  /**
   * Make a cross-origin HTTP request by sending a postMessage to
   * the parent window and awaiting the response.
   *
   * @param {string} url     Request URL (absolute or relative).
   * @param {object} [options]  Optional fetch-style options object.
   * @returns {Promise}  Resolves with a Response-like object.
   */
  function floFetch(url, options) {
    options = options || {};
    var messageId = generateMessageId();

    return new Promise(function (resolve, reject) {
      var timeoutId;
      var listener;

      // ---------- Cleanup helper ----------
      function cleanup() {
        if (timeoutId) {
          clearTimeout(timeoutId);
          timeoutId = null;
        }
        if (listener) {
          global.removeEventListener('message', listener);
          listener = null;
        }
      }

      // ---------- Timeout ----------
      timeoutId = setTimeout(function () {
        cleanup();
        reject(new Error('floFetch timed out after ' + DEFAULT_TIMEOUT_MS + 'ms for ' + url));
      }, DEFAULT_TIMEOUT_MS);

      // ---------- Message listener ----------
      listener = function (event) {
        // Only accept from the expected parent origin
        if (event.origin !== TARGET_ORIGIN) {
          return;
        }

        var data = event.data;
        if (!data || data.type !== 'FLO_RESPONSE' || data.messageId !== messageId) {
          return;
        }

        cleanup();

        var payload = data.payload || {};
        var status = payload.status || 0;
        var body = payload.body;

        if (status === 0 && payload.error) {
          reject(new Error('floFetch error: ' + payload.error));
          return;
        }

        resolve(buildResponse(status, body));
      };

      global.addEventListener('message', listener);

      // ---------- Send the request via postMessage ----------
      var payload = {
        url: url,
        method: (options.method || 'GET').toUpperCase(),
        headers: options.headers || {},
        body: options.body || null
      };

      global.parent.postMessage(
        {
          type: 'FLO_FETCH',
          messageId: messageId,
          payload: payload
        },
        TARGET_ORIGIN
      );
    });
  }

  // Expose to the global scope
  global.floFetch = floFetch;

})(typeof window !== 'undefined' ? window : globalThis);