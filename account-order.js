document.querySelectorAll("[data-copy-value]").forEach((button) => {
  button.addEventListener("click", async () => {
    const value = button.dataset.copyValue || "";
    const original = button.textContent;
    try {
      await navigator.clipboard.writeText(value);
      button.textContent = "Đã copy";
    } catch {
      button.textContent = "Hãy copy thủ công";
    }
    setTimeout(() => { button.textContent = original; }, 1600);
  });
});

document.querySelectorAll("[data-confirm-submit]").forEach((form) => {
  form.addEventListener("submit", (event) => {
    if (!confirm(form.dataset.confirmSubmit || "Bạn chắc chắn muốn tiếp tục?")) {
      event.preventDefault();
    }
  });
});
