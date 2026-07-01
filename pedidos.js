const ordersListEl = document.getElementById('ordersList');
const ordersStatusEl = document.getElementById('ordersStatus');
const reportBarsEl = document.getElementById('reportBars');
const reportMonthEl = document.getElementById('reportMonth');
const monthlyRevenueBarsEl = document.getElementById('monthlyRevenueBars');
const draggableMobileLists = [ordersListEl, reportBarsEl, monthlyRevenueBarsEl].filter(Boolean);

function currentMonthRef() {
  const now = new Date();
  const month = String(now.getMonth() + 1).padStart(2, '0');
  return `${now.getFullYear()}-${month}`;
}

function brl(value) {
  return Number(value || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function monthLabel(monthRef) {
  const [year, month] = String(monthRef || '').split('-').map(Number);
  if (!year || !month) return String(monthRef || 'Mes');

  const date = new Date(year, month - 1, 1);
  return date.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
}

function escapeHtml(value) {
  return String(value ?? '')
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}

async function loadDashboard(monthRef) {
  const qs = new URLSearchParams({
    action: 'dashboard',
    month: monthRef,
  });

  const res = await fetch(`api/pedidos.php?${qs.toString()}`, {
    headers: { Accept: 'application/json' },
  });

  let payload = {};
  try {
    payload = await res.json();
  } catch (_err) {
    payload = {};
  }

  if (!res.ok || !payload.ok) {
    throw new Error(payload.message || 'Erro ao carregar pedidos e relatorio.');
  }

  return payload.data || {};
}

async function sendDelivered(orderId) {
  const res = await fetch('api/pedidos.php?action=deliver', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ pedido_id: orderId }),
  });

  let payload = {};
  try {
    payload = await res.json();
  } catch (_err) {
    payload = {};
  }

  if (!res.ok || !payload.ok) {
    throw new Error(payload.message || 'Erro ao atualizar status do pedido.');
  }
}

function normalizeItemText(item) {
  const name = String(item.nome || item.nome_produto || 'Item');
  const qty = Math.max(1, Number(item.quantidade || 1));
  const subtotal = Number(item.subtotal || (qty * Number(item.preco_unitario || 0)));
  return `${qty}x ${name} - ${brl(subtotal)}`;
}

function itemDescriptionHtml(item) {
  const description = String(item?.descricao || '').trim();
  if (description === '') {
    return '';
  }

  return `<div class="item-desc">${escapeHtml(description)}</div>`;
}

function paymentTypeLabel(type) {
  const t = String(type || '').toLowerCase().trim();
  if (t === 'dinheiro') return 'Dinheiro';
  if (t === 'cartao') return 'Cartao';
  if (t === 'pix') return 'Pix';
  return t || 'Nao informado';
}

function paymentStatusLabel(status) {
  const s = String(status || '').toLowerCase().trim();
  if (s === 'pendente') return 'Pendente';
  if (s === 'pago') return 'Pago';
  if (s === 'cancelado') return 'Cancelado';
  return s || '';
}

function buildItemConfigHtml(configuration) {
  if (!configuration || typeof configuration !== 'object') {
    return '';
  }

  const lines = [];
  const tamanho = String(configuration.tamanho || '').trim();
  const carbos = Array.isArray(configuration.carbos) && configuration.carbos.length > 0
    ? configuration.carbos
    : (configuration.carbo ? [String(configuration.carbo)] : []);
  const proteinas = Array.isArray(configuration.proteinas) ? configuration.proteinas : [];
  const saladas = Array.isArray(configuration.saladas) ? configuration.saladas : [];
  const adicionais = Array.isArray(configuration.adicionais) ? configuration.adicionais : [];
  const valorBase = Number(configuration.valor_base || 0);
  const valorExtras = Number(configuration.valor_extras || 0);

  if (tamanho !== '') lines.push(`<div><b>Tamanho:</b> ${escapeHtml(tamanho)}</div>`);
  if (valorBase > 0) lines.push(`<div><b>Valor base:</b> ${escapeHtml(brl(valorBase))}</div>`);
  if (valorExtras > 0) lines.push(`<div><b>Adicionais:</b> ${escapeHtml(brl(valorExtras))}</div>`);
  if (carbos.length > 0) lines.push(`<div><b>Carbo:</b> ${carbos.map(escapeHtml).join(', ')}</div>`);
  if (proteinas.length > 0) lines.push(`<div><b>Proteinas:</b> ${proteinas.map(escapeHtml).join(', ')}</div>`);
  if (saladas.length > 0) lines.push(`<div><b>Saladas:</b> ${saladas.map(escapeHtml).join(', ')}</div>`);
  if (adicionais.length > 0) lines.push(`<div><b>Adicionais:</b> ${adicionais.map(escapeHtml).join(', ')}</div>`);

  if (lines.length === 0) {
    return '';
  }

  return `<div class="item-config">${lines.join('')}</div>`;
}

function renderOrders(orders) {
  ordersListEl.innerHTML = '';

  if (!Array.isArray(orders) || orders.length === 0) {
    ordersStatusEl.textContent = 'Nenhum pedido aberto no momento.';
    ordersListEl.innerHTML = '<p class="empty-state">Sem pedidos pendentes.</p>';
    return;
  }

  ordersStatusEl.textContent = `${orders.length} pedido(s) em aberto.`;

  orders.forEach((order) => {
    const id = Number(order.id || 0);
    const clientName = String(order.usuario_nome || '').trim() || String(order.usuario_email || '').trim() || 'Usuario sem nome';
    const total = brl(order.valor_total || 0);
    const items = Array.isArray(order.itens) ? order.itens : [];
    const paymentType = String(order.pagamento?.tipo || '').trim();
    const paymentStatus = String(order.pagamento?.status || '').trim();
    const paymentText = paymentType
      ? `${paymentTypeLabel(paymentType)}${paymentStatus ? ` (${paymentStatusLabel(paymentStatus)})` : ''}`
      : 'Nao informado';

    const card = document.createElement('article');
    card.className = 'order-card';
    card.innerHTML = `
      <h3 class="order-title">Pedido #${id}</h3>
      <div class="order-meta">Cliente: ${escapeHtml(clientName)}</div>
      <div class="order-meta">Valor: ${escapeHtml(total)}</div>
      <div class="order-meta">Pagamento: ${escapeHtml(paymentText)}</div>
      <h4 class="order-items-title">Itens</h4>
      <ul class="order-items">
        ${items.map((item) => `
          <li>
            <div class="item-line">${escapeHtml(normalizeItemText(item))}</div>
            ${itemDescriptionHtml(item)}
            ${buildItemConfigHtml(item.configuracao)}
          </li>
        `).join('')}
      </ul>
      <button type="button" class="btn-deliver" data-order-id="${id}">Entregue</button>
    `;

    if (items.length === 0) {
      const list = card.querySelector('.order-items');
      list.innerHTML = '<li>Lista de itens pedidos</li>';
    }

    ordersListEl.appendChild(card);
  });
}

function renderReport(report) {
  reportBarsEl.innerHTML = '';

  const items = Array.isArray(report?.items) ? report.items : [];
  if (items.length === 0) {
    reportBarsEl.innerHTML = '<p class="empty-state">Sem vendas finalizadas no mes selecionado.</p>';
    return;
  }

  const maxQty = items.reduce((max, item) => Math.max(max, Number(item.quantidade_vendida || 0)), 0) || 1;

  items.forEach((item) => {
    const product = String(item.produto || 'Produto');
    const qty = Number(item.quantidade_vendida || 0);
    const width = Math.max(6, Math.round((qty / maxQty) * 100));
    const faturamento = brl(item.faturamento || 0);

    const row = document.createElement('div');
    row.className = 'bar-row';
    row.innerHTML = `
      <div class="bar-label" title="${escapeHtml(product)}">${escapeHtml(product)}</div>
      <div class="bar-wrap" title="Faturamento: ${escapeHtml(faturamento)}">
        <div class="bar-fill" style="width:${width}%"></div>
      </div>
      <div class="bar-value">${qty}</div>
    `;
    reportBarsEl.appendChild(row);
  });
}

function renderMonthlyRevenueReport(months) {
  if (!monthlyRevenueBarsEl) return;

  monthlyRevenueBarsEl.innerHTML = '';

  if (!Array.isArray(months) || months.length === 0) {
    monthlyRevenueBarsEl.innerHTML = '<p class="empty-state">Ainda nao ha vendas finalizadas para comparar por mes.</p>';
    return;
  }

  const maxRevenue = months.reduce((max, month) => Math.max(max, Number(month.faturamento || 0)), 0) || 1;

  months.forEach((month) => {
    const revenue = Number(month.faturamento || 0);
    const width = Math.max(5, Math.round((revenue / maxRevenue) * 100));
    const orders = Number(month.total_pedidos || 0);
    const row = document.createElement('div');
    row.className = 'monthly-total';
    row.innerHTML = `
      <div class="bar-label">${escapeHtml(monthLabel(month.mes))}</div>
      <div class="bar-wrap" title="${orders} pedido(s)">
        <div class="bar-fill" style="width:${width}%"></div>
      </div>
      <div class="monthly-value">${escapeHtml(brl(revenue))}</div>
    `;
    monthlyRevenueBarsEl.appendChild(row);
  });
}

function enableDragScroll(container) {
  let isDown = false;
  let startX = 0;
  let startScrollLeft = 0;

  container.addEventListener('pointerdown', (event) => {
    if (!window.matchMedia('(max-width: 640px)').matches) return;
    if (event.pointerType === 'touch') return;

    isDown = true;
    startX = event.clientX;
    startScrollLeft = container.scrollLeft;
    container.setPointerCapture(event.pointerId);
  });

  container.addEventListener('pointermove', (event) => {
    if (!isDown) return;
    event.preventDefault();
    container.scrollLeft = startScrollLeft - (event.clientX - startX);
  });

  const stopDrag = (event) => {
    if (!isDown) return;
    isDown = false;
    if (container.hasPointerCapture(event.pointerId)) {
      container.releasePointerCapture(event.pointerId);
    }
  };

  container.addEventListener('pointerup', stopDrag);
  container.addEventListener('pointercancel', stopDrag);
  container.addEventListener('pointerleave', stopDrag);
}

draggableMobileLists.forEach(enableDragScroll);

async function refreshDashboard() {
  const monthRef = (reportMonthEl.value || currentMonthRef()).trim();
  ordersStatusEl.textContent = 'Carregando pedidos...';
  reportBarsEl.innerHTML = '<p class="empty-state">Carregando relatorio...</p>';
  if (monthlyRevenueBarsEl) {
    monthlyRevenueBarsEl.innerHTML = '<p class="empty-state">Carregando valores mensais...</p>';
  }

  try {
    const data = await loadDashboard(monthRef);
    renderOrders(data.pedidos || []);
    renderReport(data.relatorio || { items: [] });
    renderMonthlyRevenueReport(data.faturamento_mensal || []);
  } catch (err) {
    ordersStatusEl.textContent = err.message || 'Falha ao carregar dados.';
    ordersListEl.innerHTML = '<p class="empty-state">Erro ao listar pedidos.</p>';
    reportBarsEl.innerHTML = '<p class="empty-state">Erro ao carregar relatorio.</p>';
    if (monthlyRevenueBarsEl) {
      monthlyRevenueBarsEl.innerHTML = '<p class="empty-state">Erro ao carregar valores mensais.</p>';
    }
  }
}

document.addEventListener('click', async (event) => {
  const button = event.target.closest('.btn-deliver[data-order-id]');
  if (!button) return;

  const orderId = Number(button.dataset.orderId || 0);
  if (orderId <= 0) return;

  button.disabled = true;
  const oldText = button.textContent;
  button.textContent = 'Entregando...';

  try {
    await sendDelivered(orderId);
    await refreshDashboard();
  } catch (err) {
    alert(err.message || 'Nao foi possivel marcar o pedido como entregue.');
    button.disabled = false;
    button.textContent = oldText;
  }
});

if (reportMonthEl) {
  reportMonthEl.value = currentMonthRef();
  reportMonthEl.addEventListener('change', () => {
    refreshDashboard();
  });
}

refreshDashboard();
