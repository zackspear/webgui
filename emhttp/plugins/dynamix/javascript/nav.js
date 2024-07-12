const $siteNavs = document.querySelectorAll(".js-priority-nav");

console.debug("[siteNavs]", $siteNavs);

$siteNavs.forEach(($nav, key) => {
  console.debug("[siteNav]", $nav);

  const inst = priorityPlus($nav, {
    innerToggleTemplate: ({ toggleCount, totalCount }) =>
      toggleCount && toggleCount === totalCount
        ? "Menu"
        : `
      <span class="visually-hidden">Menu</span>
      <svg focusable="false" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-more-horizontal"><circle cx="12" cy="12" r="1"></circle><circle cx="19" cy="12" r="1"></circle><circle cx="5" cy="12" r="1"></circle></svg>
    `,
    classNames: {
      main: ["p-plus", "site-nav"],
      "primary-nav": ["p-plus__primary", "site-nav__menu"],
      "toggle-btn": ["p-plus__toggle-btn", "site-nav__toggle-btn"],
      "overflow-nav": ["p-plus__overflow", "site-nav__overflow-nav"],
    },
  });

  document.addEventListener("click", (e) => {
    console.log("fired listener");
    if (
      e.target.matches(
        "[data-overflow-nav], [data-overflow-nav] *, [data-toggle-btn], [data-toggle-btn] *"
      )
    )
      return;
    inst.setOverflowNavOpen(false);
  });
});
