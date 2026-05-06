const menu_mobile = document.querySelector(".burger_icon_mobile");
const sidenav_mobile = document.querySelector(".sidenav_mobile");

let isMobileOpen = false;

menu_mobile.addEventListener("click", () => {
  isMobileOpen = !isMobileOpen;
  if (isMobileOpen) {
    sidenav_mobile.style.display = "block";
  } else {
    sidenav_mobile.style.display = "none";
  }
});
