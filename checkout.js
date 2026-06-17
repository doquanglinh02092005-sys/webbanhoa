const successOrder = document.querySelector("[data-checkout-success]")?.dataset.checkoutSuccess;
if (successOrder) localStorage.removeItem("linh-florist-cart");

document.querySelectorAll("[data-copy-value]").forEach((button) => {
  button.addEventListener("click", async () => {
    const value = button.dataset.copyValue || "";
    try {
      await navigator.clipboard.writeText(value);
      const original = button.textContent;
      button.textContent = "Đã copy";
      setTimeout(() => { button.textContent = original; }, 1600);
    } catch {
      button.textContent = "Hãy copy thủ công";
    }
  });
});

const form = document.querySelector("[data-checkout-form]");
if (form) {
  let cart = [];
  try { cart = JSON.parse(localStorage.getItem("linh-florist-cart")) || []; } catch { cart = []; }
  const currency = new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND", maximumFractionDigits: 0 });
  const items = document.querySelector("[data-checkout-items]");
  const submit = document.querySelector("[data-checkout-submit]");
  const pointsRate = Number(document.querySelector("[data-points-rate]")?.dataset.pointsRate || 10000);
  const redemptionRate = Number(document.querySelector("[data-redemption-rate]")?.dataset.redemptionRate || 1000);
  const availablePoints = Number(document.querySelector("[data-available-points]")?.dataset.availablePoints || 0);
  document.querySelector("[data-cart-json]").value = JSON.stringify(cart);

  if (!cart.length) {
    items.innerHTML = '<div class="checkout-empty">Giỏ hoa đang trống. <a href="index.html#products">Chọn sản phẩm</a></div>';
    submit.disabled = true;
  } else {
    const grouped = new Map();
    cart.forEach((item) => {
      const current = grouped.get(item.id) || { ...item, quantity: 0 };
      current.quantity += 1;
      grouped.set(item.id, current);
    });
    const escapeHtml = (text) => {
      if (text == null) return "";
      const div = document.createElement('div');
      div.textContent = text;
      return div.innerHTML;
    };
    items.innerHTML = [...grouped.values()].map((item) => `<article class="checkout-item"><img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.name)}"><div><strong>${escapeHtml(item.name)}</strong><small>${item.quantity} × ${currency.format(item.price)}</small></div><b>${currency.format(item.price * item.quantity)}</b></article>`).join("");
    const subtotal = cart.reduce((sum, item) => sum + Number(item.price || 0), 0);
    const shipping = subtotal >= 800000 ? 0 : 40000;
    const pointsInput = document.querySelector("[data-points-input]");
    const pointsMessage = document.querySelector("[data-points-message]");
    const discountRow = document.querySelector("[data-points-discount-row]");
    const discountValue = document.querySelector("[data-points-discount]");
    const maximumPoints = Math.min(availablePoints, Math.floor((subtotal + shipping) / redemptionRate));
    document.querySelector("[data-checkout-subtotal]").textContent = currency.format(subtotal);
    document.querySelector("[data-checkout-shipping]").textContent = shipping ? currency.format(shipping) : "Miễn phí";
    document.querySelector("[data-checkout-points]").textContent = Math.floor(subtotal / pointsRate).toLocaleString("vi-VN");
    pointsInput.max = String(maximumPoints);

    const updatePoints = () => {
      const requested = Math.max(0, Number.parseInt(pointsInput.value || "0", 10) || 0);
      const used = Math.min(requested, maximumPoints);
      const discount = used * redemptionRate;
      pointsInput.value = String(used);
      discountRow.hidden = used === 0;
      discountValue.textContent = `-${currency.format(discount)}`;
      document.querySelector("[data-checkout-total]").textContent = currency.format(Math.max(0, subtotal + shipping - discount));
      pointsMessage.textContent = availablePoints === 0 ? "Bạn chưa có điểm để sử dụng." : `Có thể dùng tối đa ${maximumPoints.toLocaleString("vi-VN")} điểm.`;
    };
    pointsInput.addEventListener("input", updatePoints);
    document.querySelector("[data-use-max-points]").addEventListener("click", () => { pointsInput.value = String(maximumPoints); updatePoints(); });
    updatePoints();
  }

  const updateSubmitLabel = () => {
    submit.textContent = document.querySelector('input[name="payment_method"]:checked')?.value === "bank_transfer"
      ? "Đặt hàng và xem thông tin chuyển khoản"
      : "Xác nhận đặt hoa";
  };
  document.querySelectorAll('input[name="payment_method"]').forEach((input) => input.addEventListener("change", updateSubmitLabel));
  updateSubmitLabel();
}
