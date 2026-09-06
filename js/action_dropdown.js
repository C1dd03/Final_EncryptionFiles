(() => {
  let current = null;
  let nextMenuId = 0;

  function closeDropdown(restoreFocus = false) {
    if (!current) return;
    const { button, menu } = current;
    menu.classList.remove("open");
    button.classList.remove("active");
    button.setAttribute("aria-expanded", "false");
    current = null;
    if (restoreFocus && button.isConnected) button.focus();
  }

  function openDropdown(button) {
    closeDropdown();
    const container = button.closest(".action-dropdown");
    const menu = container?.querySelector(".action-dropdown-menu");
    if (!menu) return;
    if (!menu.id) menu.id = `action-menu-${++nextMenuId}`;
    button.setAttribute("aria-controls", menu.id);
    button.setAttribute("aria-expanded", "true");
    button.classList.add("active");
    menu.classList.add("open");
    current = { container, button, menu };

    const margin = 8;
    const gap = 6;
    const viewportWidth = document.documentElement.clientWidth;
    const viewportHeight = document.documentElement.clientHeight;
    const anchor = button.getBoundingClientRect();
    menu.style.maxHeight = `${Math.max(0, viewportHeight - margin * 2)}px`;
    const width = menu.offsetWidth;
    const height = menu.offsetHeight;
    const below = viewportHeight - anchor.bottom - gap - margin;
    const above = anchor.top - gap - margin;
    const opensAbove = height > below && above > below;
    const top = opensAbove ? anchor.top - gap - height : anchor.bottom + gap;
    menu.style.left = `${Math.max(margin, Math.min(anchor.right - width, viewportWidth - width - margin))}px`;
    menu.style.top = `${Math.max(margin, Math.min(top, viewportHeight - height - margin))}px`;
  }

  function menuItems() {
    return [...current.menu.querySelectorAll(".action-menu-item:not(:disabled)")];
  }

  document.addEventListener("click", (event) => {
    const button = event.target.closest(".action-dropdown-btn");
    if (button) {
      if (current?.button === button) closeDropdown();
      else openDropdown(button);
      return;
    }
    if (!current) return;
    if (event.target.closest(".action-menu-item") || !current.container.contains(event.target)) {
      closeDropdown();
    }
  });

  document.addEventListener("keydown", (event) => {
    const button = event.target.closest(".action-dropdown-btn");
    if (button && ["ArrowDown", "ArrowUp"].includes(event.key)) {
      event.preventDefault();
      if (current?.button !== button) openDropdown(button);
      if (!current) return;
      const items = menuItems();
      (event.key === "ArrowUp" ? items.at(-1) : items[0])?.focus();
      return;
    }
    if (!current) return;
    if (event.key === "Escape") {
      event.preventDefault();
      closeDropdown(true);
      return;
    }
    if (!current.menu.contains(event.target)) return;
    const items = menuItems();
    const index = items.indexOf(document.activeElement);
    let next;
    if (event.key === "ArrowDown") next = (index + 1) % items.length;
    else if (event.key === "ArrowUp") next = (index - 1 + items.length) % items.length;
    else if (event.key === "Home") next = 0;
    else if (event.key === "End") next = items.length - 1;
    else return;
    event.preventDefault();
    items[next]?.focus();
  });

  document.addEventListener("focusin", (event) => {
    if (current && !current.container.contains(event.target)) closeDropdown();
  });

  window.addEventListener("resize", () => closeDropdown());
  window.addEventListener("scroll", (event) => {
    if (current && !current.menu.contains(event.target)) closeDropdown();
  }, true);
})();
