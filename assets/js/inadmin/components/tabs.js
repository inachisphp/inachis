(function () {
  function Tabs(root, options) {
    this.nav = root;
    this.options = options || {};
    this.activeIndex = this.options.active || 0;

    const allItems = Array.from(this.nav.children);
    this.items = allItems.filter(li => !li.classList.contains("ui-tabs-title"));
    this.links = this.items.map(li => li.querySelector("a"));
    this.panels = this.links.map(link => {
      const href = link.getAttribute('href');
      return href && href.startsWith('#')
        ? document.querySelector(href)
        : null;
    });
    this.init();
  }

  Tabs.prototype.init = function () {
    const root = this.root;

    // Nav
    this.nav.classList.add(
      "ui-tabs-nav",
      "ui-widget-header"
    );
    this.nav.setAttribute("role", "tablist");

    // Tabs
    this.items.forEach((li, i) => {
      const link = this.links[i];
      const panel = this.panels[i];

      if (li.classList.contains('ui-state-active')) {
        this.activeIndex = i;
      }
      if (panel == null) {
        return;
      }
      const tabId = link.id || (link.id = "ui-tab-" + i);
      const panelId = panel.id;
      li.classList.add("ui-tabs-tab", "ui-state-default");
      li.setAttribute("role", "presentation");

      link.setAttribute("role", "tab");
      link.setAttribute("aria-controls", panelId);
      link.setAttribute("aria-selected", "false");
      link.setAttribute("tabindex", "-1");

      link.addEventListener("click", e => {
        e.preventDefault();
        this.activate(i, true);
      });

      link.addEventListener("keydown", e => this.onKeyDown(e, i));

      panel.classList.add("ui-tabs-panel");
      panel.setAttribute("role", "tabpanel");
      panel.setAttribute("aria-labelledby", tabId);
      panel.hidden = true;
    });
    this.activate(this.activeIndex, false);
  };

  Tabs.prototype.activate = function (index, userInitiated) {
    this.items.forEach((li, i) => {
      const link = this.links[i];
      const panel = this.panels[i];
      const active = i === index;

      li.classList.toggle("ui-tabs-active", active);
      li.classList.toggle("ui-state-active", active);

      link.setAttribute("aria-selected", active ? "true" : "false");
      link.setAttribute("tabindex", active ? "0" : "-1");

      if (panel) {
        panel.hidden = !active;
      }

    });

    this.links[index].focus();
    this.activeIndex = index;

    if (userInitiated && typeof this.options.activate === "function") {
      this.options.activate({
        newTab: this.items[index],
        newPanel: this.panels[index],
        index
      });
    }
  };

  Tabs.prototype.onKeyDown = function (e, index) {
    const key = e.key;
    let next = null;

    if (key === "ArrowRight" || key === "ArrowDown") {
      next = (index + 1) % this.items.length;
    } else if (key === "ArrowLeft" || key === "ArrowUp") {
      next = (index - 1 + this.items.length) % this.items.length;
    } else if (key === "Home") {
      next = 0;
    } else if (key === "End") {
      next = this.items.length - 1;
    }

    if (next !== null) {
      e.preventDefault();
      this.activate(next, true);
    }
  };

  window.tabs = function (selector, options) {
    document.querySelectorAll(selector).forEach(el => {
      el._uiTabs = new Tabs(el, options);
    });
  };
})();