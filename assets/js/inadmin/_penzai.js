(function (global) {
  function penzai(root, options = {}) {
    root.classList.add("penzai");
    root.setAttribute("role", "tree");

    const settings = {
      checkboxes: true,
      disclosureElement: "div",
      ...options
    };

    function initNode(li) {
      const childList = li.querySelector(":scope > ul, :scope > ol");
      const hasChildren = Boolean(childList);

      li.setAttribute("role", "treeitem");
      li.setAttribute("tabindex", "-1");

      if (hasChildren) {
        li.setAttribute("aria-expanded", "false");
      }

      // disclosure toggle
      const disclosure = document.createElement(settings.disclosureElement);
      disclosure.className = "penzai-disclosure";
      li.prepend(disclosure);

      // checkbox
      let checkbox;
      if (settings.checkboxes) {
        checkbox = document.createElement("input");
        checkbox.type = "checkbox";
        checkbox.className = "penzai-checkbox";
        li.prepend(checkbox);
        checkbox.addEventListener("change", () => cascadeDown(li, checkbox.checked));
      }

      if (!hasChildren) {
        li.classList.add("penzai-leaf");
        return;
      }

      childList.setAttribute("role", "group");

      disclosure.addEventListener("click", e => {
        e.stopPropagation();
        toggle(li);
      });

      childList.querySelectorAll(":scope > li").forEach(initNode);
    }

    function toggle(li) {
      const expanded = li.getAttribute("aria-expanded") === "true";
      li.setAttribute("aria-expanded", String(!expanded));
    }

    function cascadeDown(li, checked) {
      li.querySelectorAll(":scope ul input[type=checkbox], :scope ol input[type=checkbox]").forEach(cb => {
        cb.checked = checked;
        cb.indeterminate = false;
      });
      cascadeUp(li.parentElement.closest("li"));
    }

    function cascadeUp(li) {
      if (!li) return;

      const boxes = li.querySelectorAll(":scope > ul > li > input[type=checkbox], :scope > ol > li > input[type=checkbox]");
      const parentBox = li.querySelector(":scope > input[type=checkbox]");
      if (!parentBox) return;

      const checkedCount = [...boxes].filter(b => b.checked).length;

      if (checkedCount === 0) {
        parentBox.checked = false;
        parentBox.indeterminate = false;
      } else if (checkedCount === boxes.length) {
        parentBox.checked = true;
        parentBox.indeterminate = false;
      } else {
        parentBox.checked = false;
        parentBox.indeterminate = true;
      }

      cascadeUp(li.parentElement.closest("li"));
    }

    function destroy() {
      root.querySelectorAll(".penzai-disclosure, .penzai-checkbox").forEach(el => el.remove());
    }

    // keyboard navigation
    root.addEventListener("keydown", e => {
      const current = document.activeElement.closest("li");
      if (!current || !root.contains(current)) return;

      switch (e.key) {
        case "ArrowRight":
          if (current.getAttribute("aria-expanded") === "false") toggle(current);
          break;
        case "ArrowLeft":
          if (current.getAttribute("aria-expanded") === "true") toggle(current);
          break;
        case "ArrowDown":
          current.nextElementSibling?.focus();
          break;
        case "ArrowUp":
          current.previousElementSibling?.focus();
          break;
        case " ":
          current.querySelector("input[type=checkbox]")?.click();
          e.preventDefault();
          break;
      }
    });

    root.querySelectorAll(":scope > li").forEach(initNode);
    root.querySelector("li")?.setAttribute("tabindex", "0");

    const api = {
      update() {
        destroy();
        root.querySelectorAll(":scope > li").forEach(initNode);
      },
      expandAll() {
        root.querySelectorAll("li[aria-expanded]").forEach(li =>
          li.setAttribute("aria-expanded", "true")
        );
      },
      collapseAll() {
        root.querySelectorAll("li[aria-expanded]").forEach(li =>
          li.setAttribute("aria-expanded", "false")
        );
      }
    };

    if (options.expandAll) {
      api.expandAll();
    }

    return api;
  }

  // Expose globally for legacy usage
  global.penzai = penzai;

})(window);
