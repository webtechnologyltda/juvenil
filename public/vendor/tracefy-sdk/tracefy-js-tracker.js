/* Tracefy JS Error Tracker (standalone) */
(function (window) {
  "use strict";

  var Tracefy = window.Tracefy || {};
  var config = null;
  var isStarted = false;
  var patchedDispatchEvent = false;
  var seenNotificationEvents = typeof WeakSet === "function" ? new WeakSet() : null;
  var DEFAULT_PROXY_ENDPOINT = "/tracefy-sdk/events/js";
  var FILAMENT_NOTIFICATION_EVENTS = [
    "notificationsSent",
    "notificationSent",
    "filament-notifications-sent",
    "filament-notification-sent",
    "notify"
  ];

  function nowIso() {
    return new Date().toISOString();
  }

  function safeString(value) {
    if (typeof value === "string") return value;
    if (value == null) return "";

    try {
      return JSON.stringify(value);
    } catch (e) {
      return String(value);
    }
  }

  function buildPayload(kind, details) {
    return {
      captured_at: nowIso(),
      source: "frontend",
      kind: kind,
      environment: (config && config.environment) || "production",
      page: {
        url: window.location.href,
        user_agent: window.navigator.userAgent
      },
      error: details
    };
  }

  function buildNotificationPayload(kind, details) {
    return {
      captured_at: nowIso(),
      source: "frontend",
      kind: kind,
      environment: (config && config.environment) || "production",
      page: {
        url: window.location.href,
        user_agent: window.navigator.userAgent
      },
      notification: details
    };
  }

  function endpointUrl() {
    if (config && typeof config.endpoint === "string" && config.endpoint !== "") {
      return config.endpoint;
    }

    if (!config || config.useProxy !== false) {
      return DEFAULT_PROXY_ENDPOINT;
    }

    return DEFAULT_PROXY_ENDPOINT;
  }

  function send(payload) {
    if (!config) return;

    var body = JSON.stringify(payload);
    var headers = {
      "Content-Type": "application/json",
      "Accept": "application/json"
    };

    if (navigator.sendBeacon && !config.forceFetch) {
      var blob = new Blob([body], { type: "application/json" });
      navigator.sendBeacon(endpointUrl(), blob);
      return;
    }

    fetch(endpointUrl(), {
      method: "POST",
      headers: headers,
      body: body,
      keepalive: true,
      credentials: "same-origin"
    }).catch(function () {
      // Intentionally silent for tracking script reliability.
    });
  }

  function normalizeNotification(value) {
    var detail = value && value.detail !== undefined ? value.detail : value;
    var first = Array.isArray(detail) ? detail[0] : detail;
    var notification = first && first.notification ? first.notification : first;

    if (!notification || typeof notification !== "object") {
      return {
        title: safeString(notification),
        raw: safeString(detail)
      };
    }

    return {
      id: notification.id || notification.key || null,
      title: notification.title || notification.message || null,
      body: notification.body || null,
      status: notification.status || notification.type || null,
      icon: notification.icon || null,
      duration: notification.duration || null,
      raw: safeString(detail)
    };
  }

  function onError(event) {
    var details = {
      message: event.message || "Unknown JS error",
      file: event.filename || null,
      line: event.lineno || null,
      column: event.colno || null,
      stack: event.error && event.error.stack ? safeString(event.error.stack) : null
    };

    send(buildPayload("error", details));
  }

  function onUnhandledRejection(event) {
    var reason = event.reason;
    var details = {
      message:
        reason && reason.message
          ? safeString(reason.message)
          : "Unhandled promise rejection",
      stack: reason && reason.stack ? safeString(reason.stack) : null,
      reason: safeString(reason)
    };

    send(buildPayload("unhandledrejection", details));
  }

  function onFilamentNotification(event) {
    if (config && config.captureFilamentNotifications === false) {
      return;
    }

    if (seenNotificationEvents && event && typeof event === "object") {
      if (seenNotificationEvents.has(event)) {
        return;
      }

      seenNotificationEvents.add(event);
    }

    send(buildNotificationPayload("filament_notification", normalizeNotification(event)));
  }

  function isFilamentNotificationEvent(event) {
    if (!event || typeof event.type !== "string") {
      return false;
    }

    if (FILAMENT_NOTIFICATION_EVENTS.indexOf(event.type) !== -1) {
      return true;
    }

    return event.type.indexOf("filament") !== -1 && event.type.indexOf("notification") !== -1;
  }

  function patchDispatchEvent() {
    if (patchedDispatchEvent || !window.EventTarget || !window.EventTarget.prototype) {
      return;
    }

    var originalDispatchEvent = window.EventTarget.prototype.dispatchEvent;

    if (typeof originalDispatchEvent !== "function") {
      return;
    }

    window.EventTarget.prototype.dispatchEvent = function dispatchEvent(event) {
      if (this === window && isFilamentNotificationEvent(event)) {
        onFilamentNotification(event);
      }

      return originalDispatchEvent.apply(this, arguments);
    };

    patchedDispatchEvent = true;
  }

  Tracefy.init = function init(options) {
    config = options || {};

    if (isStarted) {
      return;
    }

    window.addEventListener("error", onError);
    window.addEventListener("unhandledrejection", onUnhandledRejection);

    FILAMENT_NOTIFICATION_EVENTS.forEach(function (eventName) {
      window.addEventListener(eventName, onFilamentNotification);
    });

    patchDispatchEvent();
    isStarted = true;
  };

  window.Tracefy = Tracefy;
})(window);
