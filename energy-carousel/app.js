(() => {
  const track = document.getElementById("track");
  const viewport = document.getElementById("viewport");
  const navButtons = [...document.querySelectorAll(".nav-btn")];
  const slides = [...document.querySelectorAll(".slide")];
  const total = slides.length;
  let index = 0;

  if (new URLSearchParams(location.search).has("export")) {
    document.body.classList.add("export-mode");
  }

  function setActive(next) {
    index = (next + total) % total;
    track.style.transform = `translateX(-${index * 25}%)`;
    navButtons.forEach((btn, i) => {
      btn.classList.toggle("is-active", i === index);
    });
    slides.forEach((slide, i) => {
      slide.classList.toggle("is-active", i === index);
    });
  }

  document.querySelectorAll("[data-go]").forEach((btn) => {
    btn.addEventListener("click", () => setActive(Number(btn.dataset.go)));
  });

  document.querySelectorAll("[data-step]").forEach((btn) => {
    btn.addEventListener("click", () => setActive(index + Number(btn.dataset.step)));
  });

  window.addEventListener("keydown", (event) => {
    if (event.key === "ArrowRight") setActive(index + 1);
    if (event.key === "ArrowLeft") setActive(index - 1);
  });

  let touchStartX = 0;
  viewport.addEventListener(
    "touchstart",
    (event) => {
      touchStartX = event.changedTouches[0].screenX;
    },
    { passive: true }
  );
  viewport.addEventListener(
    "touchend",
    (event) => {
      const delta = event.changedTouches[0].screenX - touchStartX;
      if (Math.abs(delta) < 40) return;
      setActive(index + (delta < 0 ? 1 : -1));
    },
    { passive: true }
  );

  viewport.classList.add("is-ready");
  setActive(0);
})();
