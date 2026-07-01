/* Tracefy JS Error Tracker (standalone) */
(function (window) {
  "use strict";

  var Tracefy = window.Tracefy || {};
  var config = null;
  var isStarted = false;
  var DEFAULT_PROXY_ENDPOINT = "/tracefy-sdk/events/js";

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

  Tracefy.init = function init(options) {
    config = options || {};

    if (isStarted) {
      return;
    }

    window.addEventListener("error", onError);
    window.addEventListener("unhandledrejection", onUnhandledRejection);
    isStarted = true;
  };

  window.Tracefy = Tracefy;
})(window);
