window.Inachis.EasyMDELinkValidator = class {
  constructor(easymde, options = {}) {
    this.mde = easymde;
    this.cm = easymde.codemirror;

    this.endpoint = options.endpoint || "/incc/api/validate-links";
    this.delay = options.delay || 800;

    this.cache = new Map();
    this.queue = new Set();
    this.activeMarkers = [];
    this.processing = false;

    this.init();
  }

  init() {
    this.injectStyles();
    this.cm.on("change", this.debounce(() => this.handleChange(), this.delay));
    this.createTooltip();
  }

  handleChange() {
    const content = this.mde.value();
    const links = this.extractLinks(content);

    this.cleanupCache(links);

    if (!links.length) {
      this.clearMarkers();
      return;
    }

    const uncached = links.filter(l => !this.cache.has(l));

    if (uncached.length) {
      this.markChecking(uncached);
    }

    links.forEach(link => {
      if (!this.cache.has(link) && !this.queue.has(link)) {
        this.queue.add(link);
      }
    });

    this.processQueue();

    const results = links.map(l => this.cache.get(l)).filter(Boolean);
    this.markLinks(results);
  }

  async processQueue() {
    if (this.processing || this.queue.size === 0) return;

    this.processing = true;

    const batch = Array.from(this.queue);
    this.queue.clear();

    if (batch.length === 0) {
      this.processing = false;
      return;
    }

    try {
      const results = await this.validate(batch);

      results.forEach(r => {
        this.cache.set(r.url, r);
      });

      this.renderFromCache();
    } catch (e) {
      console.error("Validation error", e);
    }

    this.processing = false;

    if (this.queue.size) {
      this.processQueue();
    }
  }

  async validate(links) {
    const res = await fetch(this.endpoint, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ links }),
    });

    return res.json();
  }

  markChecking(links) {
    this.clearMarkers();

    const doc = this.cm.getDoc();

    links.forEach(url => {
      const cursor = doc.getSearchCursor(url);

      while (cursor.findNext()) {
        const marker = this.cm.markText(cursor.from(), cursor.to(), {
          className: "cm-link-checking",
          attributes: { "data-url": url },
        });

        this.attachEvents(marker, { url, checking: true });
        this.activeMarkers.push(marker);
      }
    });
  }

  markLinks(results) {
    this.clearMarkers();

    const doc = this.cm.getDoc();

    results.forEach(result => {
      const cursor = doc.getSearchCursor(result.url);

      while (cursor.findNext()) {
        const className = result.ok
          ? "cm-link-valid"
          : "cm-link-broken";

        const marker = this.cm.markText(cursor.from(), cursor.to(), {
          className,
          attributes: {
            "data-url": result.url,
          },
        });

        this.attachEvents(marker, result);
        this.activeMarkers.push(marker);
      }
    });
  }

  clearMarkers() {
    this.activeMarkers.forEach(m => m.clear());
    this.activeMarkers.length = 0;
  }

  attachEvents(marker, data) {
    const el = marker.replacedWith || null;

    setTimeout(() => {
      const spans = document.querySelectorAll(`[data-url="${data.url}"]`);

      spans.forEach(span => {
        span.onmouseenter = (e) => this.showTooltip(e, data);
        span.onmouseleave = () => this.hideTooltip();

        if (!data.ok && !data.checking) {
          span.style.cursor = "pointer";
          span.onclick = () => this.retryLink(data.url);
        }
      });
    }, 0);
  }

  renderFromCache() {
    const content = this.mde.value();
    const links = this.extractLinks(content);

    const results = links.map(l => this.cache.get(l)).filter(Boolean);
    this.markLinks(results);
  }

  showTooltip(e, data) {
    const t = this.tooltip;

    if (data.checking) {
      t.innerHTML = "Checking...";
    } else if (data.ok) {
      t.innerHTML = `
        <strong>OK</strong><br>
        Status: ${data.status}<br>
      `;
    } else {
      t.innerHTML = `
        <strong>Broken</strong><br>
        Status: ${data.status || ""}<br>
        Error: ${data.error || "Unknown"}<br>
        <em>Click to retry</em>
      `;
    }

    t.style.display = "block";
    t.style.left = e.pageX + 10 + "px";
    t.style.top = e.pageY + 10 + "px";
  }

  hideTooltip() {
    this.tooltip.style.display = "none";
  }

  createTooltip() {
    const t = document.createElement("div");
    t.className = "link-tooltip";
    document.body.appendChild(t);
    this.tooltip = t;
  }

  retryLink(url) {
    this.cache.delete(url);
    this.queue.add(url);
    this.processQueue();
  }

   extractLinks(text) {
    const links = new Set();
    let match;

    // 1. ALL standard markdown links (this will also catch outer links)
    const linkRegex = /\[(?:!\[[^\]]*\]\([^)]+\)|[^\]])*\]\(([^)]+)\)/g;

    while ((match = linkRegex.exec(text)) !== null) {
      links.add(match[1]);
    }

    // 2. ALL image links
    const imageRegex = /!\[[^\]]*\]\(([^)]+)\)/g;

    while ((match = imageRegex.exec(text)) !== null) {
      links.add(match[1]);
    }

    return [...links];
  }

  cleanupCache(currentLinks) {
    this.cache.forEach((_, key) => {
      if (!currentLinks.includes(key)) {
        this.cache.delete(key);
      }
    });
  }

  debounce(fn, delay) {
    let t;
    return (...args) => {
      clearTimeout(t);
      t = setTimeout(() => fn(...args), delay);
    };
  }

  injectStyles() {
    const css = `
      .cm-link-valid {
        background: rgba(0,180,0,0.12);
        border-bottom: 2px solid rgba(0,180,0,0.6);
      }

      .cm-link-broken {
        background: rgba(255,0,0,0.12);
        border-bottom: 2px solid rgba(255,0,0,0.8);
      }

      .cm-link-checking {
        background: rgba(255,165,0,0.15);
        border-bottom: 2px dashed orange;
      }

      .link-tooltip {
        position: absolute;
        background: #222;
        color: #fff;
        padding: 8px 10px;
        font-size: 12px;
        border-radius: 4px;
        display: none;
        z-index: 9999;
        max-width: 300px;
      }
    `;

    const style = document.createElement("style");
    style.innerHTML = css;
    document.head.appendChild(style);
  }
}