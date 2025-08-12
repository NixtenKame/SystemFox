document.addEventListener("click", (event) => {
  if (event.target.id === "news-show") {
    event.preventDefault();
    const newsBody = document.getElementById("news-body");
    const newsShow = event.target;
    if (!newsBody) return;

    if (newsBody.style.display === "none" || newsBody.style.display === "") {
      newsBody.style.display = "block";
      newsShow.textContent = "Hide";
    } else {
      newsBody.style.display = "none";
      newsShow.textContent = "Show";
    }
  }

  if (event.target.id === "news-dismiss" || event.target.closest("#news-dismiss")) {
    event.preventDefault();
    const notice = event.target.closest(".site-notice");
    if (notice) {
      notice.style.display = "none";
    }
  }
});
