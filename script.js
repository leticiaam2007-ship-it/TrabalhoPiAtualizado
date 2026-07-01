async function loadProducts() {
  const res = await fetch('api/produtos.php', { headers: { Accept: 'application/json' } });
  if (!res.ok) {
    throw new Error('Falha ao buscar produtos');
  }

  const payload = await res.json();
  if (!payload.ok || !Array.isArray(payload.data)) {
    throw new Error(payload.message || 'Resposta invalida da API de produtos');
  }

  return payload.data.map((p) => ({
    id: String(p.id),
    name: p.name || '',
    desc: p.desc || '',
    price: Number(p.price || 0),
    category: p.category || 'geral',
    image: p.image || '',
    isMarmita: !!p.isMarmita,
    marmitaConfig: p.marmitaConfig || null,
  }));
}

function saveCart(cart) {
  localStorage.setItem('carrinho', JSON.stringify(cart));
}

function normalizeQty(item) {
  const raw = item?.quantidade ?? item?.qtd ?? item?.qty ?? item?.quantity ?? 1;
  const qty = Number(raw);
  if (!Number.isFinite(qty) || qty <= 0) {
    return 1;
  }
  return Math.max(1, Math.round(qty));
}

function normalizeCartItem(item) {
  if (!item || typeof item !== 'object') {
    return null;
  }

  const normalized = {
    ...item,
    quantidade: normalizeQty(item),
  };

  if (!normalized.name && normalized.nome) {
    normalized.name = String(normalized.nome);
  }

  return normalized;
}

function loadCart() {
  try {
    const raw = JSON.parse(localStorage.getItem('carrinho') || '[]');
    if (!Array.isArray(raw)) {
      return [];
    }
    return raw
      .map(normalizeCartItem)
      .filter((item) => item && item.quantidade > 0);
  } catch (e) {
    return [];
  }
}

function formatMoneyValue(value) {
  return Number(value || 0).toFixed(2).replace('.', ',');
}

function formatMoney(value) {
  return `R$ ${formatMoneyValue(value)}`;
}

const produtosEl = document.getElementById('produtos');
const carrinhoEl = document.getElementById('carrinho');
const itensCarrinhoEl = document.getElementById('itensCarrinho');
const totalEl = document.getElementById('total');
const contadorEl = document.getElementById('contador');
const abrirCarrinhoBtn = document.getElementById('abrirCarrinho');
const fecharCarrinhoBtn = document.getElementById('fecharCarrinho');
const limparCarrinhoBtn = document.getElementById('limparCarrinho');
const finalizarBtn = document.getElementById('finalizar');
const menuCategoriasToggleEl = document.getElementById('menuCategoriasToggle');
const menuCategoriasEl = document.getElementById('menuCategorias');
const menuCategoriasListaEl = document.getElementById('menuCategoriasLista');
const menuUsuarioToggleEl = document.getElementById('menuUsuarioToggle');
const menuUsuarioEl = document.getElementById('menuUsuario');
const menuOverlayEl = document.getElementById('menuOverlay');

let produtos = [];
let carrinho = [];
let categoryScrollSpyFrame = 0;

function normalizeText(value) {
  return String(value ?? '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '');
}

function categoryBaseId(categoryName) {
  const base = normalizeText(categoryName)
    .toLowerCase()
    .replace(/[^a-z0-9]+/g, '-')
    .replace(/^-+|-+$/g, '');

  if (base.includes('salgad')) return 'salgados';
  if (base.includes('marmit')) return 'marmitas';
  if (base.includes('combo')) return 'combos';
  if (base.includes('produto')) return 'produtos';
  if (base.includes('refriger')) return 'refrigerantes';
  if (base.includes('bebid')) return 'bebidas';
  return base || 'categoria';
}

function categorySortIndex(categoryName) {
  const order = ['salgados', 'marmitas', 'combos', 'produtos', 'refrigerantes'];
  const baseId = categoryBaseId(categoryName);
  const index = order.indexOf(baseId);
  return index >= 0 ? index : order.length;
}

function buildUniqueCategoryId(baseId, usedIds) {
  let id = baseId;
  let suffix = 2;
  while (usedIds.has(id)) {
    id = `${baseId}-${suffix}`;
    suffix += 1;
  }
  usedIds.add(id);
  return id;
}

function buildCategoryMenuEntries(categoryEntries) {
  return [...categoryEntries].sort((a, b) => {
    const priority = categorySortIndex(a.label) - categorySortIndex(b.label);
    if (priority !== 0) return priority;
    return normalizeText(a.label).localeCompare(normalizeText(b.label), 'pt-BR');
  });
}

function renderCategoryMenu(categoryEntries) {
  if (!menuCategoriasListaEl) return;

  const entries = buildCategoryMenuEntries(categoryEntries);
  if (entries.length === 0) {
    return;
  }

  menuCategoriasListaEl.innerHTML = entries.map((entry) => `
    <a href="#${entry.id}" class="menu-item menu-item-link" data-menu-target="${entry.id}">
      <span>${escapeHtml(entry.label)}</span>
    </a>
  `).join('');

  setActiveCategoryMenuItem(window.location.hash.replace('#', ''));
  requestCategoryMenuActiveSync();
}

function setOverlayVisible(visible) {
  if (!menuOverlayEl) return;
  menuOverlayEl.hidden = !visible;
}

function isCategoriasMenuOpen() {
  return !!menuCategoriasEl && menuCategoriasEl.classList.contains('open');
}

function isUsuarioMenuOpen() {
  return !!menuUsuarioEl && menuUsuarioEl.classList.contains('open');
}

function syncOverlayState() {
  setOverlayVisible(isCategoriasMenuOpen() || isUsuarioMenuOpen());
}

function closeCategoriasMenu() {
  if (!menuCategoriasEl || !menuCategoriasToggleEl) return;
  menuCategoriasEl.classList.remove('open');
  menuCategoriasEl.hidden = true;
  menuCategoriasEl.setAttribute('aria-hidden', 'true');
  menuCategoriasToggleEl.classList.remove('is-open');
  menuCategoriasToggleEl.setAttribute('aria-expanded', 'false');
  menuCategoriasToggleEl.setAttribute('aria-label', 'Abrir menu de categorias');
  syncOverlayState();
}

function closeUsuarioMenu() {
  if (!menuUsuarioEl || !menuUsuarioToggleEl) return;
  menuUsuarioEl.classList.remove('open');
  menuUsuarioEl.hidden = true;
  menuUsuarioEl.setAttribute('aria-hidden', 'true');
  menuUsuarioToggleEl.setAttribute('aria-expanded', 'false');
  menuUsuarioToggleEl.setAttribute('aria-label', 'Abrir menu do usuario');
  syncOverlayState();
}

function toggleCategoriasMenu() {
  if (!menuCategoriasEl || !menuCategoriasToggleEl) return;
  syncFixedTopbarOffset();
  const shouldOpen = !isCategoriasMenuOpen();

  if (shouldOpen) {
    closeUsuarioMenu();
    menuCategoriasEl.hidden = false;
    menuCategoriasEl.classList.add('open');
    menuCategoriasEl.setAttribute('aria-hidden', 'false');
    menuCategoriasToggleEl.classList.add('is-open');
    menuCategoriasToggleEl.setAttribute('aria-expanded', 'true');
    menuCategoriasToggleEl.setAttribute('aria-label', 'Fechar menu de categorias');
  } else {
    closeCategoriasMenu();
  }
  syncOverlayState();
}

function toggleUsuarioMenu() {
  if (!menuUsuarioEl || !menuUsuarioToggleEl) return;
  syncFixedTopbarOffset();
  const shouldOpen = !isUsuarioMenuOpen();

  if (shouldOpen) {
    closeCategoriasMenu();
    menuUsuarioEl.hidden = false;
    menuUsuarioEl.classList.add('open');
    menuUsuarioEl.setAttribute('aria-hidden', 'false');
    menuUsuarioToggleEl.setAttribute('aria-expanded', 'true');
    menuUsuarioToggleEl.setAttribute('aria-label', 'Fechar menu do usuario');
  } else {
    closeUsuarioMenu();
  }
  syncOverlayState();
}

function closeAllFloatingMenus() {
  closeCategoriasMenu();
  closeUsuarioMenu();
}

function syncFixedTopbarOffset() {
  const topbar = document.querySelector('.site-topbar-fixed');
  if (!topbar) return;

  const offset = Math.ceil(topbar.getBoundingClientRect().height + 12);
  document.documentElement.style.setProperty('--fixed-topbar-bottom', `${offset}px`);
}

function setActiveCategoryMenuItem(targetId) {
  if (!menuCategoriasListaEl) return;

  menuCategoriasListaEl.querySelectorAll('[data-menu-target]').forEach((item) => {
    const isActive = item.getAttribute('data-menu-target') === targetId;
    item.classList.toggle('is-active', isActive);

    if (isActive) {
      item.setAttribute('aria-current', 'true');
    } else {
      item.removeAttribute('aria-current');
    }
  });
}

function getCategorySpyOffset() {
  const topbar = document.querySelector('.topbar');
  return (topbar ? topbar.offsetHeight : 76) + 24;
}

function findCurrentCategorySectionId() {
  if (!menuCategoriasListaEl) return '';

  const menuTargets = new Set(
    Array.from(menuCategoriasListaEl.querySelectorAll('[data-menu-target]'))
      .map((item) => item.getAttribute('data-menu-target'))
      .filter(Boolean)
  );
  const sections = Array.from(document.querySelectorAll('.catalog-section[id]'))
    .filter((section) => menuTargets.has(section.id));

  if (sections.length === 0) return '';

  const offset = getCategorySpyOffset();
  let activeId = '';

  sections.forEach((section) => {
    if (section.getBoundingClientRect().top <= offset) {
      activeId = section.id;
    }
  });

  return activeId;
}

function syncCategoryMenuActiveFromScroll() {
  setActiveCategoryMenuItem(findCurrentCategorySectionId());
}

function requestCategoryMenuActiveSync() {
  if (categoryScrollSpyFrame) return;

  categoryScrollSpyFrame = window.requestAnimationFrame(() => {
    categoryScrollSpyFrame = 0;
    syncCategoryMenuActiveFromScroll();
  });
}

function scrollToCategorySection(targetId) {
  const section = document.getElementById(targetId);
  if (!section) return;

  const topbar = document.querySelector('.topbar');
  const offset = topbar ? topbar.offsetHeight + 12 : 88;
  const targetTop = section.getBoundingClientRect().top + window.scrollY - offset;

  window.scrollTo({
    top: Math.max(0, targetTop),
    behavior: 'smooth',
  });
}

function enableProductCardsDrag(cards) {
  let isDragging = false;
  let moved = false;
  let blockNextClick = false;
  let pointerId = null;
  let startX = 0;
  let startScrollLeft = 0;

  cards.addEventListener('pointerdown', (event) => {
    if (!window.matchMedia('(max-width: 480px)').matches) return;
    if (event.pointerType === 'touch') return;
    if (event.button !== undefined && event.button !== 0) return;

    isDragging = true;
    moved = false;
    pointerId = event.pointerId;
    startX = event.clientX;
    startScrollLeft = cards.scrollLeft;
    cards.classList.add('is-dragging');
    cards.setPointerCapture(event.pointerId);
  });

  cards.addEventListener('pointermove', (event) => {
    if (!isDragging || event.pointerId !== pointerId) return;

    const deltaX = event.clientX - startX;
    if (Math.abs(deltaX) > 4) {
      moved = true;
    }

    if (moved) {
      event.preventDefault();
      cards.scrollLeft = startScrollLeft - deltaX;
    }
  });

  const stopDrag = (event) => {
    if (!isDragging || event.pointerId !== pointerId) return;

    isDragging = false;
    pointerId = null;
    cards.classList.remove('is-dragging');

    if (moved) {
      blockNextClick = true;
      window.setTimeout(() => {
        blockNextClick = false;
      }, 0);
    }

    if (cards.hasPointerCapture(event.pointerId)) {
      cards.releasePointerCapture(event.pointerId);
    }
  };

  cards.addEventListener('pointerup', stopDrag);
  cards.addEventListener('pointercancel', stopDrag);
  cards.addEventListener('pointerleave', stopDrag);
  cards.addEventListener('click', (event) => {
    if (!blockNextClick) return;

    event.preventDefault();
    event.stopPropagation();
    blockNextClick = false;
  }, true);
}

async function renderProdutos() {
  if (!produtosEl) return;

  try {
    produtos = await loadProducts();
  } catch (err) {
    produtos = [];
  }

  produtosEl.innerHTML = '';

  const categorias = {};
  produtos.forEach((p) => {
    const categoria = p.category || 'geral';
    if (!categorias[categoria]) categorias[categoria] = [];
    categorias[categoria].push(p);
  });

  const usedSectionIds = new Set();
  const categoryEntries = [];

  Object.keys(categorias).sort((a, b) => {
    const priority = categorySortIndex(a) - categorySortIndex(b);
    if (priority !== 0) return priority;
    return normalizeText(a).localeCompare(normalizeText(b), 'pt-BR');
  }).forEach((cat) => {
    const baseId = categoryBaseId(cat);
    const sectionId = buildUniqueCategoryId(baseId, usedSectionIds);
    categoryEntries.push({ label: cat, id: sectionId, baseId });

    const section = document.createElement('section');
    section.id = sectionId;
    section.className = 'catalog-section';
    section.innerHTML = `<div class="section-title">${cat.charAt(0).toUpperCase() + cat.slice(1)}</div>
      <div class="cards"></div>`;

    const cards = section.querySelector('.cards');
    categorias[cat].forEach((prod) => {
      const card = document.createElement('div');
      card.className = 'card-item';
      card.innerHTML = `
        <div class="thumb" style="width:100%;height:180px;overflow:hidden;">
          <img src="${prod.image || 'https://via.placeholder.com/400x180'}" alt="${escapeHtml(prod.name)}" style="width:100%;height:100%;object-fit:cover;display:block;">
        </div>
        <div class="body">
          <h4>${escapeHtml(prod.name)}</h4>
          <p>${escapeHtml(prod.desc || '')}</p>
          <div class="price">${
            prod.isMarmita
              ? 'A partir de ' + formatMoney(prod.marmitaConfig?.precoP || 0)
              : formatMoney(prod.price || 0)
          }</div>
          <div class="buy">
            <button class="btn primary" data-id="${prod.id}" data-marmita="${!!prod.isMarmita}">
              ${prod.isMarmita ? 'Montar Marmita' : 'Adicionar'}
            </button>
          </div>
        </div>
      `;
      cards.appendChild(card);
    });

    enableProductCardsDrag(cards);
    produtosEl.appendChild(section);
  });

  renderCategoryMenu(categoryEntries);
}

if (produtosEl) {
  produtosEl.addEventListener('click', (e) => {
    const btn = e.target.closest('button[data-id]');
    if (!btn) return;

    const id = btn.getAttribute('data-id');
    const produto = produtos.find((p) => String(p.id) === String(id));
    if (!produto) return;

    if (produto.isMarmita) {
      window.location.href = `marmita.html?id=${produto.id}`;
      return;
    }

    addToCart({ ...produto, quantidade: 1 });
  });
}

function renderCarrinho() {
  carrinho = loadCart();
  if (!itensCarrinhoEl || !totalEl || !contadorEl) return;

  itensCarrinhoEl.innerHTML = '';
  let total = 0;

  carrinho.forEach((item, idx) => {
    const quantidade = Math.max(1, Number(item.quantidade || 1));
    const valor = item.isMarmita ? Number(item.valor ?? item.price ?? 0) : Number(item.price ?? 0);
    const carbos = Array.isArray(item.carbos) && item.carbos.length > 0
      ? item.carbos
      : (item.carbo ? [item.carbo] : []);
    const extrasInfo = item.isMarmita && (Number(item.valorBase || 0) > 0 || Number(item.valorExtras || 0) > 0)
      ? `<b>Base:</b> ${formatMoney(item.valorBase || 0)}<br>
          <b>Adicionais:</b> ${formatMoney(item.valorExtras || 0)}<br>`
      : '';
    const desc = item.isMarmita
      ? `<div style="font-size:12px;color:#666">
          <b>Tam:</b> ${escapeHtml(item.tamanho || '-') }<br>
          ${extrasInfo}
          <b>Carbo:</b> ${carbos.map(escapeHtml).join(', ') || '-'}<br>
          <b>Proteinas:</b> ${(item.proteinas || []).map(escapeHtml).join(', ') || '-'}<br>
          <b>Saladas:</b> ${(item.saladas || []).map(escapeHtml).join(', ') || '-'}<br>
          <b>Adicionais:</b> ${(item.adicionais || []).map(escapeHtml).join(', ') || '-'}
        </div>`
      : '';

    const itemHtml = document.createElement('div');
    itemHtml.className = 'cart-item';
    itemHtml.innerHTML = `
      <img src="${item.image || 'https://via.placeholder.com/60x60'}" alt="">
      <div style="flex:1">
        <div style="font-weight:600">${escapeHtml(item.name)}</div>
        ${desc}
        <div style="display:flex;align-items:center;gap:8px;margin-top:4px;">
          <button class="btn small" data-dec="${idx}" style="padding:2px 7px;">-</button>
          <span style="font-size:13px;color:#888;min-width:22px;text-align:center;">${quantidade}</span>
          <button class="btn small" data-inc="${idx}" style="padding:2px 7px;">+</button>
        </div>
      </div>
      <div style="font-weight:700">${formatMoney(valor * quantidade)}</div>
      <button class="btn small" data-remove="${idx}" style="margin-left:5px;">X</button>
    `;

    itensCarrinhoEl.appendChild(itemHtml);
    total += valor * quantidade;
  });

  totalEl.textContent = formatMoneyValue(total);
  contadorEl.textContent = String(carrinho.reduce((s, i) => s + Math.max(1, Number(i.quantidade || 1)), 0));
}

if (itensCarrinhoEl) {
  itensCarrinhoEl.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-remove],[data-inc],[data-dec]');
    if (!btn) return;

    const idx = Number(btn.getAttribute('data-remove') ?? btn.getAttribute('data-inc') ?? btn.getAttribute('data-dec'));
    if (!Number.isInteger(idx)) return;

    if (!carrinho[idx]) return;

    if (btn.hasAttribute('data-remove')) {
      carrinho.splice(idx, 1);
    } else if (btn.hasAttribute('data-inc')) {
      carrinho[idx].quantidade = Math.max(1, Number(carrinho[idx].quantidade || 1) + 1);
    } else if (btn.hasAttribute('data-dec')) {
      const novaQtd = Number(carrinho[idx].quantidade || 1) - 1;
      if (novaQtd <= 0) {
        carrinho.splice(idx, 1);
      } else {
        carrinho[idx].quantidade = novaQtd;
      }
    }

    saveCart(carrinho);
    renderCarrinho();
  });
}

if (limparCarrinhoBtn) {
  limparCarrinhoBtn.onclick = () => {
    if (!confirm('Limpar carrinho?')) return;
    carrinho = [];
    saveCart(carrinho);
    renderCarrinho();
  };
}

if (finalizarBtn) {
  finalizarBtn.onclick = async () => {
    carrinho = loadCart();
    if (carrinho.length === 0) {
      alert('Carrinho vazio!');
      return;
    }

    window.location.href = 'finalizar_compra.php';
  };
}

function setCartOpen(open) {
  if (!carrinhoEl) return;
  carrinhoEl.classList.toggle('open', open);
  document.body.classList.toggle('cart-is-open', open);
}

if (abrirCarrinhoBtn) {
  abrirCarrinhoBtn.onclick = () => setCartOpen(true);
}

if (fecharCarrinhoBtn) {
  fecharCarrinhoBtn.onclick = () => setCartOpen(false);
}

if (menuCategoriasToggleEl) {
  menuCategoriasToggleEl.addEventListener('click', () => {
    toggleCategoriasMenu();
  });
}

if (menuUsuarioToggleEl) {
  menuUsuarioToggleEl.addEventListener('click', () => {
    toggleUsuarioMenu();
  });
}

if (menuOverlayEl) {
  menuOverlayEl.addEventListener('click', () => {
    closeAllFloatingMenus();
  });
}

if (menuCategoriasListaEl) {
  menuCategoriasListaEl.addEventListener('click', (event) => {
    const link = event.target.closest('[data-menu-target]');
    if (!link) return;

    event.preventDefault();
    const targetId = String(link.getAttribute('data-menu-target') || '').trim();
    if (!targetId) return;

    scrollToCategorySection(targetId);
    setActiveCategoryMenuItem(targetId);
    closeCategoriasMenu();
  });
}

window.addEventListener('scroll', requestCategoryMenuActiveSync, { passive: true });
window.addEventListener('resize', () => {
  syncFixedTopbarOffset();
  requestCategoryMenuActiveSync();
});
window.addEventListener('load', () => {
  syncFixedTopbarOffset();
  requestCategoryMenuActiveSync();
});

document.addEventListener('click', (event) => {
  const target = event.target;
  if (!(target instanceof Element)) return;

  if (
    isCategoriasMenuOpen() &&
    menuCategoriasEl &&
    menuCategoriasToggleEl &&
    !menuCategoriasEl.contains(target) &&
    !menuCategoriasToggleEl.contains(target)
  ) {
    closeCategoriasMenu();
  }

  if (
    isUsuarioMenuOpen() &&
    menuUsuarioEl &&
    menuUsuarioToggleEl &&
    !menuUsuarioEl.contains(target) &&
    !menuUsuarioToggleEl.contains(target)
  ) {
    closeUsuarioMenu();
  }
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape') {
    closeAllFloatingMenus();
  }
});

function addToCart(produto) {
  carrinho = loadCart();
  const quantidadeNova = Math.max(1, Number(produto?.quantidade || 1));

  if (produto.isMarmita) {
    const idx = carrinho.findIndex((item) =>
      item.isMarmita &&
      item.id === produto.id &&
      item.tamanho === produto.tamanho &&
      JSON.stringify(item.carbos || (item.carbo ? [item.carbo] : [])) === JSON.stringify(produto.carbos || (produto.carbo ? [produto.carbo] : [])) &&
      item.carbo === produto.carbo &&
      JSON.stringify(item.proteinas || []) === JSON.stringify(produto.proteinas || []) &&
      JSON.stringify(item.saladas || []) === JSON.stringify(produto.saladas || []) &&
      JSON.stringify(item.adicionais || []) === JSON.stringify(produto.adicionais || [])
    );

    if (idx >= 0) {
      carrinho[idx].quantidade = Math.max(1, Number(carrinho[idx].quantidade || 1)) + quantidadeNova;
    } else {
      carrinho.push({ ...produto, quantidade: quantidadeNova });
    }
  } else {
    const idx = carrinho.findIndex((item) => !item.isMarmita && item.id === produto.id);
    if (idx >= 0) {
      carrinho[idx].quantidade = Math.max(1, Number(carrinho[idx].quantidade || 1)) + quantidadeNova;
    } else {
      carrinho.push({ ...produto, quantidade: quantidadeNova });
    }
  }

  saveCart(carrinho);
  renderCarrinho();
}

window.addEventListener('produtosAtualizados', async () => {
  await renderProdutos();
});

window.addEventListener('DOMContentLoaded', async () => {
  closeAllFloatingMenus();
  const params = new URLSearchParams(window.location.search);
  if (params.get('checkout') === 'ok') {
    alert('Pedido finalizado com sucesso!');
    params.delete('checkout');
    const qs = params.toString();
    const target = `${window.location.pathname}${qs ? `?${qs}` : ''}`;
    window.history.replaceState({}, '', target);
  }

  await renderProdutos();
  renderCarrinho();
});

function escapeHtml(s) {
  if (s == null) return '';
  return String(s)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}
