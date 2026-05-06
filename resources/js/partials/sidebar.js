const drawer = document.querySelector(".drawer_desktop");
const sidenav = document.querySelector(".sidenav");
const main_content = document.querySelector(".main");
const brand = document.querySelector(".sidenav_logo-admin--flex");
const tooltip_collapse = document.querySelector(".tooltip_collapse");

let isIconMode = false;

if (drawer) {
  drawer.addEventListener("click", () => {
    isIconMode = !isIconMode;

    sidenav.classList.toggle("collapsed", isIconMode);

    if (isIconMode) {
      sidenav.style.width = "90px";

      sidenav.querySelectorAll("a").forEach((link) => {
        const tooltip = link.querySelector(".tooltip");

        link.onmouseenter = () => {
          tooltip.style.opacity = "1";
        };

        link.onmouseleave = () => {
          tooltip.style.opacity = "0";
        };
      });
      tooltip_collapse.textContent = "Expand Menu";

      main_content.style.padding =
        "var(--padding-24px) var(--padding-24px) var(--padding-24px) var(--padding-114px)";

      brand.style.display = "none";

      drawer.src = "../resources/images/icons/Arrow/Chevron_Right_MD.svg";
    } else {
      sidenav.style.width = "250px";
      sidenav.querySelectorAll("a").forEach((link) => {
        const tooltip = link.querySelector(".tooltip");

        link.onmouseenter = () => {
          tooltip.style.pointerEvents = "none";
          tooltip.style.cursor = "none";
        };

        link.onmouseleave = () => {
          tooltip.style.pointerEvents = "none";
          tooltip.style.cursor = "none";
        };
      });
      tooltip_collapse.textContent = "Collapse Menu";

      main_content.style.padding =
        "var(--padding-24px) var(--padding-24px) var(--padding-24px) var(--padding-274px)";

      brand.style.display = "flex";

      drawer.src = "../resources/images/icons/Arrow/Chevron_Left_MD.svg";
    }
  });
}

drawer.addEventListener("mouseenter", () => {
  tooltip_collapse.style.opacity = "1";
});

drawer.addEventListener("mouseleave", () => {
  tooltip_collapse.style.opacity = "0";
});
