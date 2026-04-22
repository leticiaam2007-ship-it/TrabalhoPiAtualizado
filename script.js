// ...existing code...



async function loadProducts() {
  // tenta buscar do servidor, se falhar usa localStorage
  try {
    const res = await fetch('produtos/cantina');
    if (!res.ok) throw new Error('Falha ao buscar produtos');
    const rows = await res.json();
    // normaliza campos do banco MySQL para o formato usado pelo frontend
    return rows.map(p => ({
      id: String(p.id),
      name: p.nome || p.name,
      desc: p.descricao || p.desc || '',
      price: Number(p.preco ?? p.price ?? 0),
      category: p.categoria || p.category || 'Geral',
      image: p.image || p.imagem || '',
      isMarmita: !!p.isMarmita,
      marmitaConfig: p.marmitaConfig ? (typeof p.marmitaConfig === 'string' ? JSON.parse(p.marmitaConfig) : p.marmitaConfig) : null
    }));
  } catch (e) {
    try {
      return JSON.parse(localStorage.getItem('produtos') || '[]');
    } catch (_) {
      return [];
    }
  }
}

function saveCart(cart) {
  localStorage.setItem('carrinho', JSON.stringify(cart));
}
function loadCart() {
  try {
    return JSON.parse(localStorage.getItem('carrinho') || '[]');
  } catch (e) {
    return [];
  }
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

let produtos = [];
let carrinho = [];

async function renderProdutos() {
  produtos = await loadProducts();
  if (!produtosEl) return;
  produtosEl.innerHTML = '';

  const categorias = {};
  produtos.forEach(p => {
    if (!categorias[p.category]) categorias[p.category] = [];
    categorias[p.category].push(p);
  });

  Object.keys(categorias).forEach(cat => {
    const section = document.createElement('section');
    section.innerHTML = `<div class="section-title">${cat.charAt(0).toUpperCase() + cat.slice(1)}</div>
      <div class="cards"></div>`;
    const cards = section.querySelector('.cards');
    categorias[cat].forEach(prod => {
      const card = document.createElement('div');
      card.className = 'card-item';
      card.innerHTML = `
        <div class="thumb" style="width:100%;height:180px;overflow:hidden;">
          <img src="${prod.image || 'https://via.placeholder.com/400x180'}" alt="${escapeHtml(prod.name)}" style="width:100%;height:100%;object-fit:cover;display:block;">
        </div>
        <div class="body">
          <h4>${escapeHtml(prod.name)}</h4>
          <p>${escapeHtml(prod.desc || '')}</p>
          <div class="price">${prod.isMarmita ? 'A partir de R$ ' + Number(prod.marmitaConfig?.precoP || 0).toFixed(2) : 'R$ ' + Number(prod.price || 0).toFixed(2)}</div>
          <div class="buy">
            <button class="btn primary" data-id="${prod.id}" data-marmita="${!!prod.isMarmita}">
              ${prod.isMarmita ? 'Montar Marmita' : 'Adicionar'}
            </button>
          </div>
        </div>
      `;
      cards.appendChild(card);
    });
    produtosEl.appendChild(section);
  });
}

produtosEl && produtosEl.addEventListener('click', (e) => {
  const btn = e.target.closest('button[data-id]');
  if (!btn) return;
  const id = btn.getAttribute('data-id');
  const produto = produtos.find(p => String(p.id) === String(id));
  if (!produto) return;
  if (produto.isMarmita) {
    window.location.href = `marmita.html?id=${produto.id}`;
  } else {
    addToCart({ ...produto, quantidade: 1 });
  }
});

function renderCarrinho() {
  carrinho = loadCart();
  if (!itensCarrinhoEl || !totalEl || !contadorEl) return;

  itensCarrinhoEl.innerHTML = '';
  let total = 0;
  carrinho.forEach((item, idx) => {
    const valor = item.isMarmita ? (item.valor ?? item.price ?? 0) : (item.price ?? 0);
    const desc = item.isMarmita
      ? `<div style="font-size:12px;color:#666">
          <b>Tam:</b> ${escapeHtml(item.tamanho || '-') }<br>
          <b>Carbo:</b> ${escapeHtml(item.carbo || '-') }<br>
          <b>Proteínas:</b> ${(item.proteinas || []).map(escapeHtml).join(', ') || '-'}<br>
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
        <div style="font-size:13px;color:#888">Qtd: ${item.quantidade}</div>
      </div>
      <div style="font-weight:700">R$ ${(valor * item.quantidade).toFixed(2)}</div>
      <button class="btn small" data-remove="${idx}" style="margin-left:5px;">✕</button>
    `;
    itensCarrinhoEl.appendChild(itemHtml);
    total += valor * item.quantidade;
  });

  totalEl.textContent = total.toFixed(2);
  contadorEl && (contadorEl.textContent = carrinho.reduce((s, i) => s + i.quantidade, 0));
}

itensCarrinhoEl && itensCarrinhoEl.addEventListener('click', (e) => {
  const btn = e.target.closest('[data-remove]');
  if (!btn) return;
  const idx = Number(btn.getAttribute('data-remove'));
  if (Number.isInteger(idx)) {
    carrinho.splice(idx, 1);
    saveCart(carrinho);
    renderCarrinho();
  }
});

if (limparCarrinhoBtn) {
  limparCarrinhoBtn.onclick = () => {
    if (confirm('Limpar carrinho?')) {
      carrinho = [];
      saveCart(carrinho);
      renderCarrinho();
    }
  };
}

if (finalizarBtn) {
  finalizarBtn.onclick = async () => {
    carrinho = loadCart();
    if (carrinho.length === 0) return alert('Carrinho vazio!');
    // monta payload simples para /api/pedidos
    const itensApi = carrinho.map(it => ({
      id_produto: Number(it.id),
      quantidade: it.quantidade,
      preco_unitario: Number(it.price ?? it.valor ?? 0)
    }));
    const valor_total = itensApi.reduce((s, it) => s + it.quantidade * it.preco_unitario, 0);
    try {
      const res = await fetch('/api/pedidos', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id_usuario: null, itens: itensApi, valor_total })
      });
      if (!res.ok) throw new Error('Falha ao enviar pedido');
      alert('Pedido finalizado com sucesso!');
      carrinho = [];
      saveCart(carrinho);
      renderCarrinho();
    } catch (err) {
      console.error(err);
      alert('Erro ao finalizar pedido. Tente novamente.');
    }
  };
}

abrirCarrinhoBtn && (abrirCarrinhoBtn.onclick = () => carrinhoEl && carrinhoEl.classList.add('open'));
fecharCarrinhoBtn && (fecharCarrinhoBtn.onclick = () => carrinhoEl && carrinhoEl.classList.remove('open'));

function addToCart(produto) {
  carrinho = loadCart();
  if (produto.isMarmita) {
    const idx = carrinho.findIndex(item =>
      item.isMarmita &&
      item.id === produto.id &&
      item.tamanho === produto.tamanho &&
      item.carbo === produto.carbo &&
      JSON.stringify(item.proteinas || []) === JSON.stringify(produto.proteinas || []) &&
      JSON.stringify(item.saladas || []) === JSON.stringify(produto.saladas || []) &&
      JSON.stringify(item.adicionais || []) === JSON.stringify(produto.adicionais || [])
    );
    if (idx >= 0) {
      carrinho[idx].quantidade += produto.quantidade;
    } else {
      carrinho.push({ ...produto });
    }
  } else {
    const idx = carrinho.findIndex(item => !item.isMarmita && item.id === produto.id);
    if (idx >= 0) {
      carrinho[idx].quantidade += produto.quantidade;
    } else {
      carrinho.push({ ...produto });
    }
  }
  saveCart(carrinho);
  renderCarrinho();
}

window.addEventListener('produtosAtualizados', async () => {
  await renderProdutos();
});
window.addEventListener('DOMContentLoaded', async () => {
  await renderProdutos();
  renderCarrinho();
});

// simples escape para inserir texto no HTML
function escapeHtml(s) {
  if (s == null) return '';
  return String(s)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}