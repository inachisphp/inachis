window.Inachis.EasyMDELinkValidator = class {
  constructor(easymde, options = {}) {
    this.mde = easymde;
    this.cm = easymde.codemirror;

    this.endpoint = options.endpoint || null;
    this.delay = options.delay || 800;

    this.useBackend = !!this.endpoint;

    this.maxConcurrent = options.maxConcurrent || 5;
    this.minConcurrent = 2;
    this.currentConcurrent = this.maxConcurrent;

    this.retryLimit = 1;

    this.cache = new Map();
    this.cacheTime = new Map();
    this.cacheTTL = 60000;

    this.queue = new Set();
    this.inFlight = new Set();
    this.processing = false;

    this.activeMarkers = [];
    this.currentFilter = "all";
    this.timer = null;

    this.coloriseBadge = options.coloriseBadge ?? true;

    this.init();
  }

  init() {
    this.injectStyles();
    this.createTooltip();
    this.createHealthBadge();
    this.createModal();

    this.cm.on("change", () => this.scheduleValidation());
    this.cm.on("paste", (cm, e) => this.handlePaste(e));
  }

  /* --------------------------
   * URL Normalization & Helpers
   * -------------------------- */

  normaliseUrl(url) {
    if (!url) return "";
    try {
      return new URL(url, window.location.origin).href;
    } catch {
      return url;
    }
  }

  isPending(rawUrl) {
    const key = this.normaliseUrl(rawUrl);
    return (
      this.cache.has(key) ||
      this.cache.has(rawUrl) ||
      this.queue.has(key) ||
      this.queue.has(rawUrl) ||
      this.inFlight.has(key) ||
      this.inFlight.has(rawUrl)
    );
  }

  /* --------------------------
   * Scheduling
   * -------------------------- */

  scheduleValidation() {
    if (this.timer) {
      clearTimeout(this.timer);
    }
    this.timer = setTimeout(() => this.handleChange(), this.delay);
  }

  handlePaste(event) {
    const pasted = event.clipboardData?.getData("text") || "";
    const links = this.extractLinks(pasted);

    links.forEach(link => {
      if (!this.isPending(link)) {
        this.queue.add(this.normaliseUrl(link));
      }
    });

    this.processQueue();
  }

  handleChange() {
    const content = this.mde.value();
    const links = this.extractLinks(content);

    this.cleanupCache();

    if (!links.length) {
      this.clearMarkers();
      return;
    }

    const uncached = links.filter(l => !this.isPending(l));

    if (uncached.length && uncached.length < 5) {
      this.markChecking(uncached);
    }

    links.forEach(link => {
      if (!this.isPending(link)) {
        this.queue.add(link);
      }
    });

    this.processQueue();
    this.renderFromCache();
  }

  async processQueue() {
    if (this.processing || this.queue.size === 0) return;

    this.processing = true;

    const batch = Array.from(this.queue);
    this.queue.clear();

    batch.forEach(url => {
      this.inFlight.add(url);
      this.inFlight.add(this.normaliseUrl(url));
    });

    try {
      let results;

      if (this.useBackend) {
        results = await this.validate(batch);
      } else {
        results = await this.validateInParallel(batch);
      }

      if (Array.isArray(results)) {
        results.forEach((r, idx) => {
          const originalUrl = batch[idx] || r.url;
          const normalised = this.normaliseUrl(originalUrl);
          const now = Date.now();

          this.cache.set(originalUrl, r);
          this.cache.set(normalised, r);
          if (r.url) this.cache.set(this.normaliseUrl(r.url), r);

          this.cacheTime.set(originalUrl, now);
          this.cacheTime.set(normalised, now);
        });
      }
    } catch (e) {
      console.error("Validation error:", e);
      // Cache failure placeholder to avoid infinite re-fetching on error
      batch.forEach(url => {
        const failureObj = { url, ok: false, status: null, error: "Validation failed" };
        const normalised = this.normaliseUrl(url);
        this.cache.set(url, failureObj);
        this.cache.set(normalised, failureObj);
        this.cacheTime.set(url, Date.now());
        this.cacheTime.set(normalised, Date.now());
      });
    } finally {
      batch.forEach(url => {
        this.inFlight.delete(url);
        this.inFlight.delete(this.normaliseUrl(url));
      });
      this.processing = false;
    }

    this.renderFromCache();

    if (this.queue.size) this.processQueue();
  }

  async validate(links) {
    const res = await fetch(this.endpoint, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ links }),
    });

    return res.json();
  }

  async validateInParallel(links) {
    const results = [];

    for (let i = 0; i < links.length; i += this.currentConcurrent) {
      const chunk = links.slice(i, i + this.currentConcurrent);
      const start = performance.now();

      const res = await Promise.all(
        chunk.map(url => this.testLinkWithRetry(url))
      );

      const duration = performance.now() - start;

      if (duration > 1500 && this.currentConcurrent > this.minConcurrent) {
        this.currentConcurrent--;
      } else if (duration < 500 && this.currentConcurrent < this.maxConcurrent) {
        this.currentConcurrent++;
      }

      results.push(...res);
    }

    return results;
  }

  async testLinkWithRetry(url, attempt = 0) {
    const result = await this.testLink(url);

    if (!result.ok && attempt < this.retryLimit) {
      return this.testLinkWithRetry(url, attempt + 1);
    }

    return result;
  }

  async testLink(url) {
    const start = performance.now();

    try {
      let res;

      try {
        res = await fetch(url, { method: "HEAD", mode: "cors" });
      } catch {
        res = await fetch(url, { method: "GET", mode: "cors" });
      }

      return {
        url,
        ok: res.ok,
        status: res.status,
        time_ms: Math.round(performance.now() - start),
        redirects: res.redirected ? 1 : 0,
        headers: {
          "content-type": res.headers.get("content-type") || "",
        }
      };

    } catch {
      return {
        url,
        ok: false,
        status: null,
        error: "Network/CORS error",
      };
    }
  }

  /* --------------------------
   * Rendering
   * -------------------------- */

  renderFromCache() {
    const links = this.extractLinks(this.mde.value());
    const results = links
      .map(l => this.cache.get(l) || this.cache.get(this.normaliseUrl(l)))
      .filter(Boolean);

    this.markLinks(results);
    this.updateHealthBadge(results);
  }

  markLinks(results) {
    this.clearMarkers();

    const doc = this.cm.getDoc();

    results.forEach(r => {
      const cursor = doc.getSearchCursor(r.url);

      while (cursor.findNext()) {
        const marker = this.cm.markText(cursor.from(), cursor.to(), {
          className: this.getClass(r),
          attributes: { "data-url": r.url }
        });

        this.attachEvents(marker, r);
        this.activeMarkers.push(marker);
      }
    });
  }

  markChecking(links) {
    const doc = this.cm.getDoc();

    links.forEach(url => {
      const cursor = doc.getSearchCursor(url);

      while (cursor.findNext()) {
        const marker = this.cm.markText(cursor.from(), cursor.to(), {
          className: "cm-link-checking",
        });

        this.activeMarkers.push(marker);
      }
    });
  }

  getClass(r) {
    if (!r.ok) return "cm-link-broken";
    if (r.redirects > 0) return "cm-link-redirect";
    if (r.time_ms > 800) return "cm-link-slow";
    return "cm-link-valid";
  }

  clearMarkers() {
    this.activeMarkers.forEach(m => m.clear());
    this.activeMarkers.length = 0;
  }

  /* --------------------------
   * UI
   * -------------------------- */

  createTooltip() {
    this.tooltip = document.createElement("div");
    this.tooltip.className = "link-tooltip";
    document.body.appendChild(this.tooltip);
  }

  showTooltip(e, d) {
    this.tooltip.innerHTML = d.ok
      ? `✔ ${d.status} (${d.time_ms}ms)`
      : `❌ ${d.error || d.status}`;

    this.tooltip.style.display = "block";
    this.tooltip.style.left = e.pageX + 10 + "px";
    this.tooltip.style.top = e.pageY + 10 + "px";
  }

  hideTooltip() {
    this.tooltip.style.display = "none";
  }

  attachEvents(marker, data) {
    setTimeout(() => {
      document.querySelectorAll(`[data-url="${data.url}"]`).forEach(el => {
        el.onmouseenter = e => this.showTooltip(e, data);
        el.onmouseleave = () => this.hideTooltip();
      });
    });
  }

  createHealthBadge() {
    const bottomBar = this.mde.element.parentElement.querySelector(".editor-statusbar");
    if (!bottomBar) return;

    const badge = document.createElement("span");
    badge.className = "link-health-badge-inline";
    badge.style.cursor = "pointer";
    badge.onclick = () => this.openLinkModal();

    badge.innerHTML = `✔ 0 ⚠ 0 ✖ 0`;

    bottomBar.appendChild(badge);
    this.healthBadge = badge;
  }

  updateHealthBadge(results) {
    let ok = 0, slow = 0, broken = 0;

    results.forEach(r => {
      if (!r.ok) broken++;
      else if (r.time_ms > 800) slow++;
      else ok++;
    });

    if (!this.healthBadge) return;

    if (this.coloriseBadge) {
      this.healthBadge.innerHTML = `
        <span style="color:#0f0">✔ ${ok}</span>
        <span style="color:#ffa500">⚠ ${slow}</span>
        <span style="color:#f00">✖ ${broken}</span>
      `;
    } else {
      this.healthBadge.textContent = `✔ ${ok} ⚠ ${slow} ✖ ${broken}`;
    }
  }

  createModal() {
    const modal = document.createElement("div");
    modal.className = "link-modal";

    modal.innerHTML = `
      <div class="link-modal-content">
        <div class="link-modal-header">
          <strong>Link Health</strong>
          <div>
            <button class="revalidate-btn">Revalidate</button>
            <button class="export-btn">Export</button>
            <button class="close-btn">×</button>
          </div>
        </div>
        <div class="link-modal-list"></div>
      </div>
    `;

    document.body.appendChild(modal);

    modal.querySelector(".close-btn").onclick = () => modal.style.display = "none";
    modal.querySelector(".revalidate-btn").onclick = () => this.revalidateAll();
    modal.querySelector(".export-btn").onclick = () => this.exportReport();

    this.modal = modal;
  }

  openLinkModal() {
    this.modal.style.display = "flex";
    this.renderModal();
  }

  renderModal() {
    const container = this.modal.querySelector(".link-modal-list");
    const links = this.extractLinks(this.mde.value());
    const results = links
      .map(l => this.cache.get(l) || this.cache.get(this.normaliseUrl(l)))
      .filter(Boolean);

    container.innerHTML = "";

    results.forEach(r => {
      const row = document.createElement("div");
      row.className = "link-row";
      row.textContent = `${this.getStatusIcon(r)} ${r.url}`;
      row.onclick = () => this.jumpToLink(r.url);

      container.appendChild(row);
    });
  }

  getStatusIcon(r) {
    if (!r.ok) return "❌";
    if (r.time_ms > 800) return "⏱";
    if (r.redirects > 0) return "🔁";
    return "✔";
  }

  jumpToLink(url) {
    const cursor = this.cm.getDoc().getSearchCursor(url);
    if (cursor.findNext()) {
      this.cm.scrollIntoView(cursor.from());
      this.cm.setCursor(cursor.from());
    }
  }

  revalidateAll() {
    const links = this.extractLinks(this.mde.value());

    links.forEach(l => {
      const normalised = this.normaliseUrl(l);
      this.cache.delete(l);
      this.cache.delete(normalised);
      this.cacheTime.delete(l);
      this.cacheTime.delete(normalised);
      this.inFlight.delete(l);
      this.inFlight.delete(normalised);
      this.queue.add(l);
    });

    this.processQueue();
  }

  exportReport() {
    const links = this.extractLinks(this.mde.value());
    const broken = links
      .map(l => this.cache.get(l) || this.cache.get(this.normaliseUrl(l)))
      .filter(r => r && !r.ok);

    const blob = new Blob([JSON.stringify(broken, null, 2)]);
    const a = document.createElement("a");
    a.href = URL.createObjectURL(blob);
    a.download = "broken-links.json";
    a.click();
  }

  /* --------------------------
   * Utils
   * -------------------------- */

  extractLinks(text) {
    const links = new Set();
    let match;

    const linkRegex = /\[(?:!\[[^\]]*\]\([^)]+\)|[^\]])*\]\(([^)]+)\)/g;
    while ((match = linkRegex.exec(text))) links.add(match[1]);

    const imageRegex = /!\[[^\]]*\]\(([^)]+)\)/g;
    while ((match = imageRegex.exec(text))) links.add(match[1]);

    return [...links];
  }

  cleanupCache() {
    const now = Date.now();

    this.cache.forEach((_, key) => {
      if (now - (this.cacheTime.get(key) || 0) > this.cacheTTL) {
        this.cache.delete(key);
        this.cacheTime.delete(key);
      }
    });
  }

  injectStyles() {
    const css = `
      .cm-link-valid { border-bottom:2px solid green; }
      .cm-link-broken { border-bottom:2px solid red; }
      .cm-link-checking { border-bottom:2px dashed orange; }
      .cm-link-slow { border-bottom:2px dotted orange; }
      .cm-link-redirect { border-bottom:2px dashed blue; }

      .link-health-badge {
        margin-left:10px;
        background:#222;
        color:#fff;
        padding:4px 8px;
        cursor:pointer;
      }

      .link-modal {
        position:fixed; inset:0;
        background:rgba(0,0,0,.6);
        display:none; align-items:center; justify-content:center;
      }

      .link-modal-content {
        background:#fff; width:500px;
        max-height:80vh; overflow:auto;
      }

      .link-row { padding:6px; cursor:pointer; }
      .link-row:hover { background:#eee; }
    `;

    const style = document.createElement("style");
    style.innerHTML = css;
    document.head.appendChild(style);
  }
};