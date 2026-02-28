(function ()
{
  const COLLECTOR_URL = "https://collector.dorjepradhan.com/log/";
  const FLUSH_MS = 5000;

  let queue = [];
  let flushTimer = null;
  let lastActivityTs = Date.now();
  let idleReported = false;

  function pageUrl()
  {
    return window.location.origin + window.location.pathname;
  }

  function enqueue(ev)
  {
    queue.push(ev);
    scheduleFlush();
  }

  function scheduleFlush()
  {
    if (flushTimer) { return; }
    flushTimer = window.setTimeout(function ()
    {
      flushTimer = null;
      flush(false);
    }, FLUSH_MS);
  }

  function flush(useBeacon)
  {
    if (queue.length === 0) { return; }

    const payload = { events: queue };
    queue = [];

    const body = JSON.stringify(payload);

    // Prefer sendBeacon for unload/pagehide
    if (useBeacon && navigator.sendBeacon)
    {
      const blob = new Blob([body], { type: "application/json" });
      navigator.sendBeacon(COLLECTOR_URL, blob);
      return;
    }

    fetch(COLLECTOR_URL,
    {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: body,
      keepalive: true,
      credentials: "omit"
    }).catch(function ()
    {
      // If it fails, drop events (simple, acceptable for class)
    });
  }

  // --- Event 1: pageview (static) ---
  enqueue({
    event_type: "pageview",
    page_url: pageUrl(),
    element: null,
    value: null,
    extra: { title: document.title }
  });

  // --- Event 2: click (activity) ---
  document.addEventListener("click", function (e)
  {
    lastActivityTs = Date.now();
    idleReported = false;

    const t = e.target;
    if (!t) { return; }

    // Keep it simple: prefer id, then tag name
    const el = t.id ? ("#" + t.id) : (t.tagName ? t.tagName.toLowerCase() : "unknown");

    enqueue({
      event_type: "click",
      page_url: pageUrl(),
      element: el,
      value: null,
      extra: null
    });
  }, true);

  // --- Event 3: scroll depth (engagement) ---
  let lastScrollBucket = -1;

  window.addEventListener("scroll", function ()
  {
    lastActivityTs = Date.now();
    idleReported = false;

    const doc = document.documentElement;
    const scrollTop = doc.scrollTop || document.body.scrollTop || 0;
    const scrollHeight = doc.scrollHeight || document.body.scrollHeight || 1;
    const clientHeight = doc.clientHeight || window.innerHeight || 1;

    const maxScroll = Math.max(1, scrollHeight - clientHeight);
    const pct = Math.min(100, Math.max(0, Math.round((scrollTop / maxScroll) * 100)));

    // Only report when crossing 25% buckets (0,25,50,75,100)
    const bucket = Math.floor(pct / 25);
    if (bucket !== lastScrollBucket)
    {
      lastScrollBucket = bucket;

      enqueue({
        event_type: "scroll",
        page_url: pageUrl(),
        element: null,
        value: String(bucket * 25),
        extra: { percent: pct }
      });
    }
  }, { passive: true });

  // --- Event 4: JS errors ---
  window.addEventListener("error", function (e)
  {
    enqueue({
      event_type: "error",
      page_url: pageUrl(),
      element: null,
      value: e.message ? String(e.message) : "error",
      extra:
      {
        filename: e.filename || null,
        lineno: e.lineno || null,
        colno: e.colno || null
      }
    });
  });

  // --- Event 5: idle (session behavior) ---
  // If no activity for 30s, record an idle event once until activity resumes.
  window.setInterval(function ()
  {
    const idleMs = Date.now() - lastActivityTs;

    if (!idleReported && idleMs >= 30000)
    {
      idleReported = true;

      enqueue({
        event_type: "idle",
        page_url: pageUrl(),
        element: null,
        value: String(Math.round(idleMs / 1000)),
        extra: { seconds: Math.round(idleMs / 1000) }
      });
    }
  }, 5000);

  // Flush on leaving the page
  window.addEventListener("pagehide", function ()
  {
    flush(true);
  });

  document.addEventListener("visibilitychange", function ()
  {
    if (document.visibilityState === "hidden")
    {
      flush(true);
    }
  });
})();