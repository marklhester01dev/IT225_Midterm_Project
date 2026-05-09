const notifCards = document.querySelectorAll(".notif-card");
const notifPop = document.querySelectorAll(".notif-close-link");

function dismissCard(card) {
  card.style.animation = "none";
  card.style.transition = "opacity 0.5s ease, transform 0.5s ease";
  card.style.opacity = "0";
  card.style.transform = "translateX(80px)";
  setTimeout(() => card.remove(), 500);
}

setTimeout(() => {
  notifCards.forEach((card) => dismissCard(card));
}, 4000);

notifPop.forEach((link) => {
  link.addEventListener("click", function (e) {
    e.preventDefault();
    dismissCard(this.closest(".notif-card"));
  });
});
