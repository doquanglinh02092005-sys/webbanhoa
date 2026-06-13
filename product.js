let products = window.FLORIST_PRODUCTS;
const currency = new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND", maximumFractionDigits: 0 });
const productId = Number(new URLSearchParams(window.location.search).get("id")) || 1;
let product = products.find((item) => item.id === productId) || products[0];

function loadCart() {
  try { return JSON.parse(localStorage.getItem("linh-florist-cart")) || []; } catch { return []; }
}

function saveCart(cart) {
  localStorage.setItem("linh-florist-cart", JSON.stringify(cart));
}

let cart = loadCart();
let toastTimer;
const overlay = document.querySelector("[data-overlay]");
const cartDrawer = document.querySelector("[data-cart-drawer]");
const mobileNav = document.querySelector("[data-mobile-nav]");

function escapeHtml(text) {
  if (text == null) return "";
  const div = document.createElement('div');
  div.textContent = text;
  return div.innerHTML;
}

async function hydrateAccount() {
  try {
    const response = await fetch("api/session.php", { credentials: "same-origin", cache: "no-store" });
    if (!response.ok) return;
    const session = await response.json();
    const link = document.querySelector("[data-account-link]");
    const greeting = document.querySelector("[data-account-greeting]");
    const label = document.querySelector("[data-account-label]");
    const guestActions = document.querySelector("[data-guest-auth]");
    const mobileLink = document.querySelector("[data-mobile-account-link]");
    const mobileLogin = document.querySelector("[data-mobile-login]");
    const mobileRegister = document.querySelector("[data-mobile-register]");
    if (!session.authenticated) {
      guestActions.hidden = false;
      link.hidden = true;
      mobileLogin.hidden = false;
      mobileRegister.hidden = false;
      mobileLink.hidden = true;
      return;
    }

    guestActions.hidden = true;
    link.hidden = false;
    mobileLogin.hidden = true;
    mobileRegister.hidden = true;
    mobileLink.hidden = false;
    link.href = session.user.role === "admin" ? "admin/index.php" : "account.php";
    greeting.textContent = session.user.role === "admin" ? "Quyền truy cập" : "Xin chào";
    label.textContent = session.user.role === "admin" ? "Quản trị" : session.user.full_name.split(" ").pop();
    mobileLink.href = link.href;
    mobileLink.textContent = session.user.role === "admin" ? "Trang quản trị" : `Tài khoản · ${session.user.full_name}`;
  } catch {
    // Cho phép giao diện tĩnh tiếp tục hoạt động khi PHP chưa chạy.
  }
}

async function hydrateCatalog() {
  try {
    const response = await fetch("api/products.php", { cache: "no-store" });
    if (!response.ok) return;
    const databaseProducts = await response.json();
    if (!Array.isArray(databaseProducts) || !databaseProducts.length) return;
    products = databaseProducts;
    product = products.find((item) => item.id === productId) || products[0];
    renderProduct();
  } catch {
    // Giữ sản phẩm mẫu khi API chưa sẵn sàng.
  }
}

function renderProduct() {
  const saving = Math.max(product.compare - product.price, 0);
  document.title = `${product.name} | Linh Florist`;
  document.querySelector("[data-breadcrumb-name]").textContent = product.name;
  document.querySelector("[data-detail-category]").textContent = `Hoa ${product.category} · ${product.color}`;
  document.querySelector("[data-detail-name]").textContent = product.name;
  document.querySelector("[data-detail-price]").textContent = currency.format(product.price);
  document.querySelector("[data-detail-compare]").textContent = currency.format(product.compare);
  document.querySelector("[data-saving]").textContent = saving ? `Tiết kiệm ${currency.format(saving)}` : "";
  const stockLabel = document.querySelector("[data-detail-stock]");
  if (stockLabel) {
    const stock = Number(product.stock);
    stockLabel.textContent = Number.isFinite(stock)
      ? (stock > 0 ? `Còn ${stock} sản phẩm · Có thể đặt ngay` : "Tạm hết hàng")
      : "Hoa tươi được chuẩn bị theo đơn";
    stockLabel.classList.toggle("out-of-stock", Number.isFinite(stock) && stock <= 0);
  }
  document.querySelector("[data-detail-badge]").textContent = product.badge;
  document.querySelector("[data-detail-color]").textContent = product.color;
  const image = document.querySelector("[data-detail-image]");
  const thumb = document.querySelector("[data-detail-thumb]");
  image.src = product.image;
  image.alt = product.name;
  thumb.src = product.image;
  thumb.alt = product.name;

  const related = products.filter((item) => item.id !== product.id).slice(0, 4);
  document.querySelector("[data-related-products]").innerHTML = related.map((item) => `
    <article class="product-card">
      <div class="product-image">
        <span class="product-badge">${escapeHtml(item.badge)}</span>
        <a class="product-image-link" href="product.html?id=${item.id}"><img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.name)}" loading="lazy" /></a>
        <button class="quick-add" type="button" data-related-add="${item.id}">Thêm vào giỏ</button>
      </div>
      <div class="product-info"><p class="product-category">Hoa ${escapeHtml(item.category)}</p><h3 class="product-name"><a href="product.html?id=${item.id}">${escapeHtml(item.name)}</a></h3><div class="product-pricing"><span class="product-price">${currency.format(item.price)}</span><span class="product-compare">${currency.format(item.compare)}</span></div></div>
    </article>`).join("");
}

function renderCart() {
  const total = cart.reduce((sum, item) => sum + item.price, 0);
  document.querySelector("[data-cart-count]").textContent = cart.length;
  document.querySelector("[data-cart-total]").textContent = currency.format(total);
  document.querySelector("[data-drawer-total]").textContent = currency.format(total);
  document.querySelector("[data-cart-empty]").hidden = cart.length > 0;
  document.querySelector("[data-cart-items]").innerHTML = cart.map((item) => `<article class="cart-item"><img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.name)}" /><div><h4>${escapeHtml(item.name)}</h4><p>${currency.format(item.price)}</p></div><button type="button" aria-label="Xóa ${escapeHtml(item.name)}" data-remove-cart="${item.cartId}">×</button></article>`).join("");
}

function showToast(message) {
  const toast = document.querySelector("[data-toast]");
  toast.textContent = message;
  toast.classList.add("show");
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove("show"), 2300);
}

function addProductToCart(item, quantity = 1) {
  if (item.stock !== undefined && item.stock <= 0) {
    showToast("Sản phẩm đã hết hàng");
    return;
  }
  const currentQuantity = cart.filter(c => c.id === item.id).length;
  if (item.stock !== undefined && currentQuantity + quantity > item.stock) {
    showToast(`Chỉ còn ${item.stock} sản phẩm trong kho`);
    return;
  }
  const cardMessage = document.querySelector("[data-card-message]")?.value.trim() || "";
  const deliveryDate = document.querySelector("[data-delivery-date]")?.value || "";
  for (let index = 0; index < quantity; index += 1) {
    cart.push({ id: item.id, name: item.name, price: item.price, image: item.image, cardMessage, deliveryDate, cartId: `${item.id}-${Date.now()}-${index}` });
  }
  saveCart(cart);
  renderCart();
  showToast(`Đã thêm ${quantity} × “${item.name}” vào giỏ`);
}

function openPanel(panel) {
  [cartDrawer, mobileNav].forEach((element) => element.classList.remove("active"));
  panel.classList.add("active");
  panel.setAttribute("aria-hidden", "false");
  overlay.classList.add("active");
  document.body.classList.add("no-scroll");
}

function closePanels() {
  [cartDrawer, mobileNav].forEach((element) => { element.classList.remove("active"); element.setAttribute("aria-hidden", "true"); });
  overlay.classList.remove("active");
  document.body.classList.remove("no-scroll");
}

function currentQuantity() {
  const input = document.querySelector("[data-quantity]");
  const value = Math.min(20, Math.max(1, Number(input.value) || 1));
  input.value = value;
  return value;
}

const deliveryInput = document.querySelector("[data-delivery-date]");
deliveryInput.min = new Date().toISOString().split("T")[0];

document.querySelector("[data-quantity-minus]").addEventListener("click", () => { document.querySelector("[data-quantity]").value = Math.max(1, currentQuantity() - 1); });
document.querySelector("[data-quantity-plus]").addEventListener("click", () => { document.querySelector("[data-quantity]").value = Math.min(20, currentQuantity() + 1); });
document.querySelector("[data-quantity]").addEventListener("change", currentQuantity);
document.querySelector("[data-card-message]").addEventListener("input", (event) => { document.querySelector("[data-message-count]").textContent = event.target.value.length; });
document.querySelector("[data-detail-add]").addEventListener("click", () => addProductToCart(product, currentQuantity()));
document.querySelector("[data-buy-now]").addEventListener("click", () => { addProductToCart(product, currentQuantity()); openPanel(cartDrawer); });
document.querySelector("[data-detail-wishlist]").addEventListener("click", (event) => { event.currentTarget.classList.toggle("active"); event.currentTarget.textContent = event.currentTarget.classList.contains("active") ? "♥" : "♡"; });

document.querySelectorAll(".detail-accordion > button").forEach((button) => button.addEventListener("click", () => {
  const expanded = button.getAttribute("aria-expanded") === "true";
  button.setAttribute("aria-expanded", String(!expanded));
  button.nextElementSibling.hidden = expanded;
}));

document.addEventListener("click", (event) => {
  const relatedAdd = event.target.closest("[data-related-add]");
  const removeButton = event.target.closest("[data-remove-cart]");
  if (relatedAdd) addProductToCart(products.find((item) => item.id === Number(relatedAdd.dataset.relatedAdd)));
  if (removeButton) { cart = cart.filter((item) => item.cartId !== removeButton.dataset.removeCart); saveCart(cart); renderCart(); }
});

document.querySelector("[data-detail-search]").addEventListener("submit", (event) => { event.preventDefault(); const query = document.querySelector("#detail-search").value.trim(); window.location.href = `index.html?search=${encodeURIComponent(query)}#products`; });
document.querySelector("[data-cart-open]").addEventListener("click", () => openPanel(cartDrawer));
document.querySelector("[data-cart-close]").addEventListener("click", closePanels);
document.querySelector("[data-checkout]").addEventListener("click", () => { window.location.href = "checkout.php"; });
document.querySelector("[data-menu-toggle]").addEventListener("click", () => openPanel(mobileNav));
document.querySelector("[data-menu-close]").addEventListener("click", closePanels);
overlay.addEventListener("click", closePanels);
document.addEventListener("keydown", (event) => { if (event.key === "Escape") closePanels(); });
document.querySelector("[data-close-announcement]").addEventListener("click", (event) => { event.currentTarget.parentElement.hidden = true; });
document.querySelector(".newsletter form").addEventListener("submit", (event) => { event.preventDefault(); showToast("Cảm ơn bạn đã đăng ký!"); event.currentTarget.reset(); });

renderProduct();
renderCart();
hydrateAccount();
hydrateCatalog();
