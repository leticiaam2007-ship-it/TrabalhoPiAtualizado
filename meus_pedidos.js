const listaAtuaisEl = document.getElementById('listaAtuais');
const listaHistoricoEl = document.getElementById('listaHistorico');
const filtroButtons = Array.from(document.querySelectorAll('[data-filtro]'));

let filtroAtual = 'todos';
let cancelandoPedidoId = null;

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

function formatDateTime(value) {
  const raw = String(value || '').trim();
  if (raw === '') {
    return 'Data nao informada';
  }

  const normalized = raw.includes('T') ? raw : raw.replace(' ', 'T');
  const dt = new Date(normalized);
  if (Number.isNaN(dt.getTime())) {
    return raw;
  }

  return dt.toLocaleString('pt-BR');
}

function statusPedidoInfo(status) {
  const s = String(status || '').toLowerCase().trim();
  if (s === 'pendente') return { label: 'Pendente', cls: 'pendente' };
  if (s === 'preparando') return { label: 'Em preparo', cls: 'preparando' };
  if (s === 'pronto') return { label: 'Pronto', cls: 'pronto' };
  if (s === 'finalizado' || s === 'entregue') return { label: 'Entregue', cls: s };
  if (s === 'cancelado') return { label: 'Cancelado', cls: 'cancelado' };
  return { label: s || 'Desconhecido', cls: 'desconhecido' };
}

function statusPagamentoInfo(status) {
  const s = String(status || '').toLowerCase().trim();
  if (s === 'pago') return { label: 'Pago', cls: 'pag_pago' };
  if (s === 'pendente') return { label: 'Aguardando pagamento', cls: 'pag_pendente' };
  if (s === 'cancelado') return { label: 'Nao pago', cls: 'pag_cancelado' };
  return { label: 'Nao informado', cls: 'pag_nao_informado' };
}

function formaPagamentoLabel(tipo) {
  const t = String(tipo || '').toLowerCase().trim();
  if (t === 'dinheiro') return 'Dinheiro';
  if (t === 'pix') return 'Pix';
  if (t === 'cartao') return 'Cartao';
  return 'Forma de pagamento nao informada';
}

function podeCancelarPedido(status) {
  const s = String(status || '').toLowerCase().trim();
  return s !== 'finalizado' && s !== 'entregue' && s !== 'cancelado';
}

function configItemHtml(config) {
  if (!config || typeof config !== 'object') {
    return '';
  }

  const partes = [];
  const tamanho = String(config.tamanho || '').trim();
  const carbos = Array.isArray(config.carbos) && config.carbos.length > 0
    ? config.carbos
    : (config.carbo ? [String(config.carbo)] : []);
  const proteinas = Array.isArray(config.proteinas) ? config.proteinas : [];
  const saladas = Array.isArray(config.saladas) ? config.saladas : [];
  const adicionais = Array.isArray(config.adicionais) ? config.adicionais : [];
  const valorBase = Number(config.valor_base || 0);
  const valorExtras = Number(config.valor_extras || 0);

  if (tamanho !== '') partes.push(`<div><b>Tamanho:</b> ${escapeHtml(tamanho)}</div>`);
  if (valorBase > 0) partes.push(`<div><b>Valor base:</b> ${escapeHtml(brl(valorBase))}</div>`);
  if (valorExtras > 0) partes.push(`<div><b>Adicionais:</b> ${escapeHtml(brl(valorExtras))}</div>`);
  if (carbos.length > 0) partes.push(`<div><b>Carbo:</b> ${carbos.map(escapeHtml).join(', ')}</div>`);
  if (proteinas.length > 0) partes.push(`<div><b>Proteinas:</b> ${proteinas.map(escapeHtml).join(', ')}</div>`);
  if (saladas.length > 0) partes.push(`<div><b>Saladas:</b> ${saladas.map(escapeHtml).join(', ')}</div>`);
  if (adicionais.length > 0) partes.push(`<div><b>Adicionais:</b> ${adicionais.map(escapeHtml).join(', ')}</div>`);

  if (partes.length === 0) {
    return '';
  }

  return `<div class="item-config">${partes.join('')}</div>`;
}

function itemPedidoHtml(item) {
  const nome = String(item.nome || 'Produto');
  const descricao = String(item.descricao || '').trim();
  const quantidade = Math.max(1, Number(item.quantidade || 1));
  const precoUnitario = Number(item.preco_unitario || 0);
  const subtotal = Number(item.subtotal || (quantidade * precoUnitario));

  return `
    <li class="item-linha">
      <div class="item-topo">${escapeHtml(nome)}</div>
      ${descricao !== '' ? `<div class="item-desc">${escapeHtml(descricao)}</div>` : ''}
      <div>${quantidade} unidade(s) - ${brl(precoUnitario)} cada - Subtotal: ${brl(subtotal)}</div>
      ${configItemHtml(item.configuracao)}
    </li>
  `;
}

function cardPedidoHtml(order) {
  const pedidoStatus = statusPedidoInfo(order.status);
  const pagamentoStatus = statusPagamentoInfo(order.pagamento?.status);
  const pagamentoTipo = formaPagamentoLabel(order.pagamento?.tipo);
  const itens = Array.isArray(order.itens) ? order.itens : [];
  const id = Number(order.id || 0);
  const cancelando = cancelandoPedidoId === id;

  return `
    <article class="card-pedido">
      <div class="pedido-cabecalho">
        <div>
          <div class="pedido-id">Pedido #${id}</div>
          <div class="pedido-meta">Data e horario: ${escapeHtml(formatDateTime(order.criado_em))}</div>
          <div class="pedido-meta">Forma de pagamento: ${escapeHtml(pagamentoTipo)}</div>
        </div>
        <div class="etiquetas">
          <span class="tag ${pedidoStatus.cls}">${escapeHtml(pedidoStatus.label)}</span>
          <span class="tag ${pagamentoStatus.cls}">${escapeHtml(pagamentoStatus.label)}</span>
        </div>
      </div>
      <ul class="itens">
        ${itens.map(itemPedidoHtml).join('')}
      </ul>
      <div class="total">Total do pedido: ${brl(order.valor_total || 0)}</div>
      ${podeCancelarPedido(order.status) ? `
        <div class="pedido-actions">
          <button class="btn btn-cancelar" type="button" data-cancelar-pedido="${id}" ${cancelando ? 'disabled' : ''}>
            ${cancelando ? 'Cancelando...' : 'Cancelar pedido'}
          </button>
        </div>
      ` : ''}
    </article>
  `;
}

function renderLista(container, orders, message) {
  const list = Array.isArray(orders) ? orders : [];
  if (list.length === 0) {
    container.innerHTML = `<div class="vazio"><p>${escapeHtml(message)}</p><a class="btn" href="index.php">Ver produtos</a></div>`;
    return;
  }

  container.innerHTML = list.map(cardPedidoHtml).join('');
}

async function loadMeusPedidos(filtro) {
  const qs = new URLSearchParams({ action: 'meus_pedidos', filtro });
  const res = await fetch(`api/pedidos.php?${qs.toString()}`, {
    headers: { Accept: 'application/json' },
  });

  let payload = {};
  try {
    payload = await res.json();
  } catch (_err) {
    payload = {};
  }

  if (res.status === 401) {
    window.location.href = 'login.php?erro=login&next=' + encodeURIComponent('meus_pedidos.php');
    return null;
  }

  if (!res.ok || !payload.ok) {
    throw new Error(payload.message || 'Erro ao carregar seus pedidos.');
  }

  return payload.data || { pedidos_atuais: [], historico: [] };
}

async function cancelarPedido(pedidoId) {
  const res = await fetch('api/pedidos.php?action=cancelar', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ pedido_id: pedidoId }),
  });

  let payload = {};
  try {
    payload = await res.json();
  } catch (_err) {
    payload = {};
  }

  if (res.status === 401) {
    window.location.href = 'login.php?erro=login&next=' + encodeURIComponent('meus_pedidos.php');
    return;
  }

  if (!res.ok || !payload.ok) {
    throw new Error(payload.message || 'Nao foi possivel cancelar o pedido.');
  }
}

function setFiltroAtivo(valor) {
  filtroAtual = valor;
  filtroButtons.forEach((button) => {
    if (button.dataset.filtro === valor) {
      button.classList.add('ativo');
    } else {
      button.classList.remove('ativo');
    }
  });
}

async function refresh() {
  listaAtuaisEl.innerHTML = '<div class="vazio"><p>Carregando pedidos atuais...</p></div>';
  listaHistoricoEl.innerHTML = '<div class="vazio"><p>Carregando historico...</p></div>';

  try {
    const data = await loadMeusPedidos(filtroAtual);
    if (!data) return;
    renderLista(listaAtuaisEl, data.pedidos_atuais || [], 'Nenhum pedido atual encontrado para este filtro.');
    renderLista(listaHistoricoEl, data.historico || [], 'Nenhum pedido no historico para este filtro.');
  } catch (err) {
    const msg = escapeHtml(err.message || 'Falha ao carregar pedidos.');
    listaAtuaisEl.innerHTML = `<div class="vazio"><p>${msg}</p></div>`;
    listaHistoricoEl.innerHTML = `<div class="vazio"><p>${msg}</p></div>`;
  }
}

filtroButtons.forEach((button) => {
  button.addEventListener('click', () => {
    const filtro = String(button.dataset.filtro || 'todos');
    setFiltroAtivo(filtro);
    refresh();
  });
});

document.addEventListener('click', async (event) => {
  const button = event.target.closest('[data-cancelar-pedido]');
  if (!button) {
    return;
  }

  const pedidoId = Number(button.dataset.cancelarPedido || 0);
  if (!Number.isInteger(pedidoId) || pedidoId <= 0 || cancelandoPedidoId !== null) {
    return;
  }

  if (!window.confirm('Cancelar este pedido?')) {
    return;
  }

  cancelandoPedidoId = pedidoId;
  button.disabled = true;
  button.textContent = 'Cancelando...';

  try {
    await cancelarPedido(pedidoId);
    await refresh();
  } catch (err) {
    alert(err.message || 'Nao foi possivel cancelar o pedido.');
  } finally {
    cancelandoPedidoId = null;
    if (document.body.contains(button)) {
      button.disabled = false;
      button.textContent = 'Cancelar pedido';
    }
  }
});

setFiltroAtivo('todos');
refresh();
