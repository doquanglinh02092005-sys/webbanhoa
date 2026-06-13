let products = window.FLORIST_PRODUCTS;

function loadCart() {
  try {
    return JSON.parse(localStorage.getItem("linh-florist-cart")) || [];
  } catch {
    return [];
  }
}

function saveCart(cart) {
  localStorage.setItem("linh-florist-cart", JSON.stringify(cart));
}

const initialQuery = new URLSearchParams(window.location.search).get("search") || "";
const state = { query: initialQuery, categories: [], colors: [], occasion: "all", price: "all", sort: "featured", cart: loadCart() };
const currency = new Intl.NumberFormat("vi-VN", { style: "currency", currency: "VND", maximumFractionDigits: 0 });

const grid = document.querySelector("[data-product-grid]");
const resultCount = document.querySelector("[data-result-count]");
const emptyState = document.querySelector("[data-empty-state]");
const activeFilters = document.querySelector("[data-active-filters]");
const filterPanel = document.querySelector("[data-filters]");
const overlay = document.querySelector("[data-overlay]");
const cartDrawer = document.querySelector("[data-cart-drawer]");
const mobileNav = document.querySelector("[data-mobile-nav]");
const toast = document.querySelector("[data-toast]");
const categoryFilters = document.querySelector("[data-category-filters]");
const colorFilters = document.querySelector("[data-color-filters]");
const catalogStatus = document.querySelector("[data-catalog-status]");
const collectionTitle = document.querySelector("[data-collection-title]");
const emptyTitle = document.querySelector("[data-empty-title]");
const emptyMessage = document.querySelector("[data-empty-message]");
const occasionLabels = { all: "Bộ sưu tập hoa tươi", birthday: "Hoa sinh nhật", love: "Hoa tình yêu", congratulations: "Hoa chúc mừng", bouquet: "Bó hoa", basket: "Giỏ hoa", wedding: "Hoa cưới", seasonal: "Hoa theo mùa", sale: "Sản phẩm khuyến mãi" };
let toastTimer;

function normalizeText(value) {
  return value.normalize("NFD").replace(/[\u0300-\u036f]/g, "").toLowerCase();
}

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
    const loyaltyLoginHint = document.querySelector("[data-loyalty-login-hint]");
    if (!session.authenticated) {
      guestActions.hidden = false;
      link.hidden = true;
      mobileLogin.hidden = false;
      mobileRegister.hidden = false;
      mobileLink.hidden = true;
      loyaltyLoginHint.hidden = false;
      return;
    }

    guestActions.hidden = true;
    link.hidden = false;
    mobileLogin.hidden = true;
    mobileRegister.hidden = true;
    mobileLink.hidden = false;
    loyaltyLoginHint.hidden = true;
    link.href = session.user.role === "admin" ? "admin/index.php" : "account.php";
    greeting.textContent = session.user.role === "admin" ? "Quyền truy cập" : "Xin chào";
    label.textContent = session.user.role === "admin" ? "Quản trị" : session.user.full_name.split(" ").pop();
    mobileLink.href = link.href;
    mobileLink.textContent = session.user.role === "admin" ? "Trang quản trị" : `Tài khoản · ${session.user.full_name}`;
  } catch {
    // Trang bán hàng vẫn hoạt động khi PHP server chưa được bật.
  }
}

async function hydrateCatalog() {
  try {
    const response = await fetch("api/products.php", { cache: "no-store" });
    if (!response.ok) throw new Error("Product API unavailable");
    const databaseProducts = await response.json();
    if (!Array.isArray(databaseProducts) || !databaseProducts.length) throw new Error("Product API returned no products");
    products = databaseProducts;
    catalogStatus.hidden = true;
    renderDynamicFilters();
    renderProducts();
  } catch {
    catalogStatus.hidden = false;
  }
}

function colorSwatch(color) {
  return {
    "Hồng": "#e8a8b5",
    "Trắng": "#f4f1e9",
    "Vàng": "#e7c766",
    "Tím": "#af8bb7",
    "Pastel": "linear-gradient(135deg,#efb8c8,#b8d9df,#ead6a7)",
    "Đỏ": "#d96b76",
    "Cam": "#e9a066",
    "Xanh": "#8eb6a0",
  }[color] || "#d8cec8";
}

function renderDynamicFilters() {
  const categoryCounts = new Map();
  const colorCounts = new Map();
  products.forEach((product) => {
    if (product.category) categoryCounts.set(product.category, (categoryCounts.get(product.category) || 0) + 1);
    if (product.color) colorCounts.set(product.color, (colorCounts.get(product.color) || 0) + 1);
  });

  state.categories = state.categories.filter((value) => categoryCounts.has(value));
  state.colors = state.colors.filter((value) => colorCounts.has(value));
  categoryFilters.innerHTML = [...categoryCounts.entries()].map(([category, count]) => `
    <label><input type="checkbox" name="category" value="${escapeHtml(category)}" ${state.categories.includes(category) ? "checked" : ""} /> <span>${escapeHtml(category.startsWith("Hoa ") ? category : `Hoa ${category}`)}</span><small>${count}</small></label>
  `).join("");
  colorFilters.innerHTML = [...colorCounts.entries()].map(([color, count]) => `
    <label><input type="checkbox" name="color" value="${escapeHtml(color)}" ${state.colors.includes(color) ? "checked" : ""} /> <i style="--swatch: ${colorSwatch(color)}"></i><span>${escapeHtml(color)}</span><small>${count}</small></label>
  `).join("");
}

function productCard(product) {
  return `
    <article class="product-card">
      <div class="product-image">
        <span class="product-badge">${escapeHtml(product.badge)}</span>
        <button class="wishlist-button" type="button" aria-label="Thêm ${escapeHtml(product.name)} vào yêu thích" data-wishlist="${product.id}">♡</button>
        <a class="product-image-link" href="product.html?id=${product.id}" aria-label="Xem chi tiết ${escapeHtml(product.name)}">
          <img src="${escapeHtml(product.image)}" alt="${escapeHtml(product.name)}" loading="lazy" />
        </a>
        <button class="quick-add" type="button" data-add-cart="${product.id}">Thêm vào giỏ</button>
      </div>
      <div class="product-info">
        <p class="product-category">Hoa ${escapeHtml(product.category)}</p>
        <h3 class="product-name"><a href="product.html?id=${product.id}">${escapeHtml(product.name)}</a></h3>
        <div class="product-pricing">
          <span class="product-price">${currency.format(product.price)}</span>
          <span class="product-compare">${currency.format(product.compare)}</span>
        </div>
      </div>
    </article>`;
}

function getFilteredProducts() {
  const currentOccasion = String(state.occasion || "all").trim().toLowerCase();
  const filtered = products.filter((product) => {
    const matchesQuery = !state.query || normalizeText(`${product.name} ${product.category} ${product.color}`).includes(normalizeText(state.query));
    const matchesCategory = !state.categories.length || state.categories.includes(product.category);
    const matchesColor = !state.colors.length || state.colors.includes(product.color);
    const productOccasion = String(product.occasion || "").trim().toLowerCase();
    const comparePrice = Number(product.compare_price ?? product.compare ?? 0);
    const matchesOccasion = currentOccasion === "all"
      || (currentOccasion === "sale" ? comparePrice > Number(product.price || 0) : productOccasion === currentOccasion);
    const matchesPrice = state.price === "all"
      || (state.price === "under-500" && product.price < 500000)
      || (state.price === "500-1000" && product.price >= 500000 && product.price <= 1000000)
      || (state.price === "over-1000" && product.price > 1000000);
    return matchesQuery && matchesCategory && matchesColor && matchesOccasion && matchesPrice;
  });

  return filtered.sort((a, b) => {
    if (state.sort === "price-asc") return a.price - b.price;
    if (state.sort === "price-desc") return b.price - a.price;
    if (state.sort === "name-asc") return a.name.localeCompare(b.name, "vi");
    return a.id - b.id;
  });
}

function renderProducts() {
  const visibleProducts = getFilteredProducts();
  grid.innerHTML = visibleProducts.map(productCard).join("");
  resultCount.textContent = visibleProducts.length;
  grid.hidden = visibleProducts.length === 0;
  emptyState.hidden = visibleProducts.length !== 0;
  collectionTitle.textContent = occasionLabels[state.occasion] || occasionLabels.all;
  emptyTitle.textContent = state.occasion === "all" ? "Chưa tìm thấy mẫu hoa phù hợp" : "Hiện chưa có sản phẩm trong nhóm này.";
  emptyMessage.textContent = state.occasion === "all" ? "Hãy thử bỏ bớt bộ lọc hoặc tìm bằng từ khóa khác." : "Bạn có thể chọn Tất cả sản phẩm hoặc thử một bộ lọc khác.";
  document.querySelectorAll("[data-occasion]").forEach((link) => link.classList.toggle("active", link.dataset.occasion === state.occasion));
  renderActiveFilters();
}

function renderActiveFilters() {
  const chips = [];
  if (state.query) chips.push({ label: `Tìm: ${state.query}`, type: "query", value: state.query });
  if (state.occasion !== "all") chips.push({ label: occasionLabels[state.occasion], type: "occasion", value: state.occasion });
  state.categories.forEach((value) => chips.push({ label: value, type: "category", value }));
  state.colors.forEach((value) => chips.push({ label: value, type: "color", value }));
  if (state.price !== "all") {
    const labels = { "under-500": "Dưới 500.000đ", "500-1000": "500.000đ - 1.000.000đ", "over-1000": "Trên 1.000.000đ" };
    chips.push({ label: labels[state.price], type: "price", value: state.price });
  }
  activeFilters.innerHTML = chips.map((chip) => `<button class="filter-chip" type="button" data-remove-filter="${chip.type}" data-filter-value="${chip.value}">${chip.label}<span>×</span></button>`).join("");
}

function updateStateFromInputs() {
  state.categories = [...document.querySelectorAll('input[name="category"]:checked')].map((input) => input.value);
  state.colors = [...document.querySelectorAll('input[name="color"]:checked')].map((input) => input.value);
  state.price = document.querySelector('input[name="price"]:checked')?.value || "all";
  renderProducts();
}

function clearFilters() {
  state.query = "";
  state.categories = [];
  state.colors = [];
  state.occasion = "all";
  state.price = "all";
  document.querySelector("#site-search").value = "";
  document.querySelectorAll('.filters input[type="checkbox"]').forEach((input) => { input.checked = false; });
  document.querySelector('input[name="price"][value="all"]').checked = true;
  renderProducts();
}

function removeFilter(type, value) {
  if (type === "query") {
    state.query = "";
    document.querySelector("#site-search").value = "";
  }
  if (type === "category") {
    state.categories = state.categories.filter((item) => item !== value);
    const input = document.querySelector(`input[name="category"][value="${CSS.escape(value)}"]`);
    if (input) input.checked = false;
  }
  if (type === "color") {
    state.colors = state.colors.filter((item) => item !== value);
    const input = document.querySelector(`input[name="color"][value="${CSS.escape(value)}"]`);
    if (input) input.checked = false;
  }
  if (type === "occasion") state.occasion = "all";
  if (type === "price") {
    state.price = "all";
    document.querySelector('input[name="price"][value="all"]').checked = true;
  }
  renderProducts();
}

function showToast(message) {
  toast.textContent = message;
  toast.classList.add("show");
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove("show"), 2300);
}

function renderCart() {
  const cartItems = document.querySelector("[data-cart-items]");
  const cartEmpty = document.querySelector("[data-cart-empty]");
  const total = state.cart.reduce((sum, item) => sum + item.price, 0);
  document.querySelector("[data-cart-count]").textContent = state.cart.length;
  document.querySelector("[data-cart-total]").textContent = currency.format(total);
  document.querySelector("[data-drawer-total]").textContent = currency.format(total);
  cartEmpty.hidden = state.cart.length > 0;
  cartItems.innerHTML = state.cart.map((item) => `
    <article class="cart-item">
      <img src="${escapeHtml(item.image)}" alt="${escapeHtml(item.name)}" />
      <div><h4>${escapeHtml(item.name)}</h4><p>${currency.format(item.price)}</p></div>
      <button type="button" aria-label="Xóa ${escapeHtml(item.name)}" data-remove-cart="${item.cartId}">×</button>
    </article>`).join("");
}

function addToCart(productId) {
  const product = products.find((item) => item.id === productId);
  if (!product) return;
  if (product.stock !== undefined && product.stock <= 0) {
    showToast("Sản phẩm đã hết hàng");
    return;
  }
  const currentQuantity = state.cart.filter(item => item.id === productId).length;
  if (product.stock !== undefined && currentQuantity >= product.stock) {
    showToast(`Chỉ còn ${product.stock} sản phẩm trong kho`);
    return;
  }
  state.cart.push({ id: product.id, name: product.name, price: product.price, image: product.image, cartId: `${product.id}-${Date.now()}` });
  saveCart(state.cart);
  renderCart();
  showToast(`Đã thêm “${product.name}” vào giỏ`);
}

function removeFromCart(cartId) {
  state.cart = state.cart.filter((item) => item.cartId !== cartId);
  saveCart(state.cart);
  renderCart();
}

function openPanel(panel) {
  [cartDrawer, mobileNav, filterPanel].forEach((element) => element.classList.remove("active"));
  panel.classList.add("active");
  if (panel.hasAttribute("data-cart-drawer") || panel.hasAttribute("data-mobile-nav")) panel.setAttribute("aria-hidden", "false");
  overlay.classList.add("active");
  document.body.classList.add("no-scroll");
}

function closePanels() {
  [cartDrawer, mobileNav, filterPanel].forEach((element) => element.classList.remove("active"));
  cartDrawer.setAttribute("aria-hidden", "true");
  mobileNav.setAttribute("aria-hidden", "true");
  overlay.classList.remove("active");
  document.body.classList.remove("no-scroll");
}

document.querySelector("[data-search-form]").addEventListener("submit", (event) => {
  event.preventDefault();
  state.query = document.querySelector("#site-search").value.trim();
  renderProducts();
  document.querySelector("#products").scrollIntoView({ behavior: "smooth", block: "start" });
});

filterPanel.addEventListener("change", updateStateFromInputs);
document.querySelector("[data-sort]").addEventListener("change", (event) => { state.sort = event.target.value; renderProducts(); });
document.querySelectorAll("[data-clear-filters]").forEach((button) => button.addEventListener("click", clearFilters));
document.querySelectorAll(".filter-group-title").forEach((button) => {
  button.addEventListener("click", () => {
    const content = button.nextElementSibling;
    const expanded = button.getAttribute("aria-expanded") === "true";
    button.setAttribute("aria-expanded", String(!expanded));
    content.hidden = expanded;
  });
});

document.addEventListener("click", (event) => {
  const addButton = event.target.closest("[data-add-cart]");
  const wishlistButton = event.target.closest("[data-wishlist]");
  const removeChip = event.target.closest("[data-remove-filter]");
  const removeCartButton = event.target.closest("[data-remove-cart]");
  const occasionLink = event.target.closest("[data-occasion]");
  if (occasionLink) {
    event.preventDefault();
    const selectedOccasion = String(occasionLink.dataset.occasion || "all").trim().toLowerCase();
    state.occasion = Object.prototype.hasOwnProperty.call(occasionLabels, selectedOccasion) ? selectedOccasion : "all";
    renderProducts();
    closePanels();
    document.querySelector("#products").scrollIntoView({ behavior: "smooth", block: "start" });
  }
  if (addButton) addToCart(Number(addButton.dataset.addCart));
  if (wishlistButton) {
    wishlistButton.classList.toggle("active");
    wishlistButton.textContent = wishlistButton.classList.contains("active") ? "♥" : "♡";
  }
  if (removeChip) removeFilter(removeChip.dataset.removeFilter, removeChip.dataset.filterValue);
  if (removeCartButton) removeFromCart(removeCartButton.dataset.removeCart);
});

document.querySelector("[data-cart-open]").addEventListener("click", () => openPanel(cartDrawer));
document.querySelector("[data-cart-close]").addEventListener("click", closePanels);
document.querySelector("[data-checkout]").addEventListener("click", () => { window.location.href = "checkout.php"; });
document.querySelector("[data-menu-toggle]").addEventListener("click", () => openPanel(mobileNav));
document.querySelector("[data-menu-close]").addEventListener("click", closePanels);
document.querySelector("[data-filter-open]").addEventListener("click", () => openPanel(filterPanel));
document.querySelector("[data-filter-close]").addEventListener("click", closePanels);
overlay.addEventListener("click", closePanels);
document.addEventListener("keydown", (event) => { if (event.key === "Escape") closePanels(); });
document.querySelector("[data-close-announcement]").addEventListener("click", (event) => { event.currentTarget.parentElement.hidden = true; });
document.querySelectorAll(".mobile-nav a").forEach((link) => link.addEventListener("click", closePanels));
document.querySelector(".newsletter form").addEventListener("submit", (event) => { event.preventDefault(); showToast("Cảm ơn bạn đã đăng ký!"); event.currentTarget.reset(); });

document.querySelector("#site-search").value = initialQuery;
renderDynamicFilters();
renderProducts();
renderCart();
hydrateAccount();
hydrateCatalog();
