function saveCart(cart) {
  localStorage.setItem('carrinho', JSON.stringify(cart));
}

function loadCart() {
  try {
    return JSON.parse(localStorage.getItem('carrinho') || '[]');
  } catch (_err) {
    return [];
  }
}

function brl(value) {
  return Number(value || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}

const checkoutItemsEl = document.getElementById('checkoutItems');
const checkoutEmptyEl = document.getElementById('checkoutEmpty');
const subtotalValueEl = document.getElementById('subtotalValue');
const totalValueEl = document.getElementById('totalValue');
const checkoutFormEl = document.getElementById('checkoutForm');
const checkoutStatusEl = document.getElementById('checkoutStatus');
const confirmBtnEl = document.getElementById('confirmBtn');

let cart = [];

function getItemUnitValue(item) {
  return item.isMarmita ? Number(item.valor ?? item.price ?? 0) : Number(item.price ?? 0);
}

function normalizeCart() {
  cart = loadCart().filter((item) => item && Number(item.quantidade || 0) > 0);
  saveCart(cart);
}

function totalAmount() {
  return cart.reduce((sum, item) => sum + (getItemUnitValue(item) * Number(item.quantidade || 0)), 0);
}

function itemDescription(item) {
  if (!item.isMarmita) {
    return '';
  }

  const carbos = Array.isArray(item.carbos) && item.carbos.length > 0
    ? item.carbos
    : (item.carbo ? [item.carbo] : []);
  const valorBase = Number(item.valorBase || 0);
  const valorExtras = Number(item.valorExtras || 0);
  const priceLines = valorBase > 0 || valorExtras > 0
    ? `
      <div><b>Valor base:</b> ${escapeHtml(brl(valorBase))}</div>
      <div><b>Adicionais:</b> ${escapeHtml(brl(valorExtras))}</div>
    `
    : '';

  return `
    <div><b>Tamanho:</b> ${escapeHtml(item.tamanho || '-')}</div>
    ${priceLines}
    <div><b>Carbo:</b> ${carbos.map(escapeHtml).join(', ') || '-'}</div>
    <div><b>Proteinas:</b> ${(item.proteinas || []).map(escapeHtml).join(', ') || '-'}</div>
    <div><b>Saladas:</b> ${(item.saladas || []).map(escapeHtml).join(', ') || '-'}</div>
    <div><b>Adicionais:</b> ${(item.adicionais || []).map(escapeHtml).join(', ') || '-'}</div>
  `;
}

function renderCart() {
  normalizeCart();
  checkoutItemsEl.innerHTML = '';

  if (cart.length === 0) {
    checkoutEmptyEl.style.display = '';
    checkoutFormEl.style.display = 'none';
    checkoutStatusEl.textContent = '';
    setTimeout(() => {
      window.location.href = 'index.php';
    }, 1200);
    return;
  }

  checkoutEmptyEl.style.display = 'none';
  checkoutFormEl.style.display = '';

  cart.forEach((item, index) => {
    const qty = Math.max(1, Number(item.quantidade || 1));
    const unit = getItemUnitValue(item);

    const row = document.createElement('article');
    row.className = 'checkout-item';
    row.innerHTML = `
      <img src="${item.image || 'https://via.placeholder.com/58x58'}" alt="">
      <div>
        <div class="title">${escapeHtml(item.name || 'Produto')}</div>
        <div class="meta">
          <div>Unitario: ${escapeHtml(brl(unit))}</div>
          ${itemDescription(item)}
        </div>
      </div>
      <div>
        <div class="item-actions">
          <button class="qty-btn" type="button" data-dec="${index}">-</button>
          <span class="qty-value">${qty}</span>
          <button class="qty-btn" type="button" data-inc="${index}">+</button>
        </div>
        <div style="margin-top:4px;text-align:right;font-size:0.82rem;font-weight:700;">${escapeHtml(brl(unit * qty))}</div>
        <div style="margin-top:3px;text-align:right;">
          <button class="remove-btn" type="button" data-remove="${index}">Remover</button>
        </div>
      </div>
    `;
    checkoutItemsEl.appendChild(row);
  });

  const total = totalAmount();
  subtotalValueEl.textContent = brl(total);
  totalValueEl.textContent = brl(total);
}

function buildApiItems() {
  return cart.map((item) => ({
    id_produto: Number(item.id),
    nome_produto: String(item.name || ''),
    quantidade: Number(item.quantidade || 1),
    preco_unitario: Number(getItemUnitValue(item)),
    configuracao: item.isMarmita
      ? {
          tamanho: item.tamanho || null,
          carbo: item.carbo || null,
          carbos: Array.isArray(item.carbos) ? item.carbos : (item.carbo ? [item.carbo] : []),
          proteinas: Array.isArray(item.proteinas) ? item.proteinas : [],
          saladas: Array.isArray(item.saladas) ? item.saladas : [],
          adicionais: Array.isArray(item.adicionais) ? item.adicionais : [],
          valor_base: Number(item.valorBase || 0),
          valor_extras: Number(item.valorExtras || 0),
          valor_total: Number(getItemUnitValue(item)),
          extras_por_categoria: item.extrasPorCategoria || {},
        }
      : null,
  }));
}

async function submitOrder(paymentType) {
  const res = await fetch('api/pedidos.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      itens: buildApiItems(),
      pagamento: { tipo: paymentType },
    }),
  });

  let payload = {};
  try {
    payload = await res.json();
  } catch (_err) {
    payload = {};
  }

  if (!res.ok || !payload.ok) {
    if (res.status === 401) {
      window.location.href = 'login.php?erro=login&next=' + encodeURIComponent('finalizar_compra.php');
      return;
    }

    throw new Error(payload.message || 'Erro ao finalizar pedido.');
  }
}

document.addEventListener('click', (event) => {
  const btn = event.target.closest('button[data-inc],button[data-dec],button[data-remove]');
  if (!btn) {
    return;
  }

  const index = Number(btn.dataset.inc ?? btn.dataset.dec ?? btn.dataset.remove ?? -1);
  if (!Number.isInteger(index) || index < 0 || !cart[index]) {
    return;
  }

  if (btn.dataset.inc !== undefined) {
    cart[index].quantidade = Math.max(1, Number(cart[index].quantidade || 1) + 1);
  } else if (btn.dataset.dec !== undefined) {
    const novaQtd = Number(cart[index].quantidade || 1) - 1;
    if (novaQtd <= 0) {
      cart.splice(index, 1);
    } else {
      cart[index].quantidade = novaQtd;
    }
  } else if (btn.dataset.remove !== undefined) {
    cart.splice(index, 1);
  }

  saveCart(cart);
  renderCart();
});

if (checkoutFormEl) {
  checkoutFormEl.addEventListener('submit', async (event) => {
    event.preventDefault();
    normalizeCart();

    if (cart.length === 0) {
      renderCart();
      return;
    }

    const paymentType = checkoutFormEl.querySelector('input[name="pagamento"]:checked')?.value || '';
    if (!paymentType) {
      checkoutStatusEl.textContent = 'Escolha uma forma de pagamento.';
      return;
    }

    confirmBtnEl.disabled = true;
    checkoutStatusEl.textContent = 'Confirmando pedido...';

    try {
      await submitOrder(paymentType);
      localStorage.removeItem('carrinho');
      window.location.href = 'index.php?checkout=ok';
    } catch (err) {
      checkoutStatusEl.textContent = err.message || 'Nao foi possivel finalizar.';
      confirmBtnEl.disabled = false;
    }
  });
}

renderCart();
