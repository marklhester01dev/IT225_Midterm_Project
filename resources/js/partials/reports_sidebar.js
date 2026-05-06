const drawer = document.querySelector(".drawer_desktop");
const sidenav = document.querySelector(".sidenav");
const main_content = document.querySelector(".main");
const tooltip_collapse = document.querySelector(".tooltip_collapse");

let isIconMode = false;
const isDesktop = () => window.matchMedia("(min-width: 767px)").matches;

const ICON_WIDTH = "90px";
const FULL_WIDTH = "250px";

const PADDING_ICON =
  "var(--padding-80px) var(--padding-24px) var(--padding-24px) var(--padding-114px)";
const PADDING_FULL =
  "var(--padding-80px) var(--padding-24px) var(--padding-24px) var(--padding-274px)";

const setState = (iconMode) => {
  isIconMode = iconMode;

  if (!sidenav) return;

  sidenav.classList.toggle("collapsed", isIconMode);
  sidenav.style.width = isIconMode ? ICON_WIDTH : FULL_WIDTH;

  if (tooltip_collapse) {
    tooltip_collapse.textContent = isIconMode ? "Expand Menu" : "Collapse Menu";
  }

  if (main_content) {
    main_content.style.padding = isIconMode ? PADDING_ICON : PADDING_FULL;
  }

  if (drawer) {
    drawer.src = isIconMode
      ? "../../resources/images/icons/Arrow/Chevron_Right_MD.svg"
      : "../../resources/images/icons/Arrow/Chevron_Left_MD.svg";
  }

  if (sidenav) {
    sidenav.querySelectorAll("a").forEach((link) => {
      const tooltip = link.querySelector(".tooltip");
      if (!tooltip) return;

      if (isIconMode) {
        link.onmouseenter = () => {
          tooltip.style.opacity = "1";
        };
        link.onmouseleave = () => {
          tooltip.style.opacity = "0";
        };
      } else {
        link.onmouseenter = () => {
          tooltip.style.pointerEvents = "none";
          tooltip.style.cursor = "none";
        };
        link.onmouseleave = () => {
          tooltip.style.pointerEvents = "none";
          tooltip.style.cursor = "none";
        };
      }
    });
  }

  localStorage.setItem("sidenav_isIconMode", String(isIconMode));
};

if (isDesktop() && sidenav) {
  const saved = localStorage.getItem("sidenav_isIconMode");
  setState(saved === "true");
}

if (drawer && sidenav) {
  drawer.addEventListener("click", () => {
    if (!isDesktop()) return;
    setState(!isIconMode);
  });
}
