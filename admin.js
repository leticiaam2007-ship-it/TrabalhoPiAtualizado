async function apiListProducts() {
  const res = await fetch('api/produtos.php', { headers: { Accept: 'application/json' } });
  if (!res.ok) throw new Error('Falha ao carregar produtos');

  const payload = await res.json();
  if (!payload.ok || !Array.isArray(payload.data)) {
    throw new Error(payload.message || 'Resposta invalida da API');
  }

  return payload.data.map((p) => ({
    id: String(p.id),
    name: p.name || '',
    desc: p.desc || '',
    price: Number(p.price || 0),
    category: p.category || 'produtos',
    image: p.image || '',
    isMarmita: !!p.isMarmita,
    marmitaConfig: p.marmitaConfig || null,
  }));
}

function formatMoneyValue(value) {
  return Number(value || 0).toFixed(2).replace('.', ',');
}

async function apiSaveProduct(produto) {
  const res = await fetch('api/produtos.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(produto),
  });

  let payload = {};
  try {
    payload = await res.json();
  } catch (_err) {
    payload = {};
  }

  if (!res.ok || !payload.ok) {
    throw new Error(payload.message || 'Erro ao salvar produto');
  }

  return payload;
}

async function apiDeleteProduct(id) {
  const res = await fetch(`api/produtos.php?id=${encodeURIComponent(id)}`, {
    method: 'DELETE',
  });

  let payload = {};
  try {
    payload = await res.json();
  } catch (_err) {
    payload = {};
  }

  if (!res.ok || !payload.ok) {
    throw new Error(payload.message || 'Erro ao remover produto');
  }

  return payload;
}

const tabProdutos = document.getElementById('tabProdutos');
const tabMarmitas = document.getElementById('tabMarmitas');
const secProdutos = document.getElementById('secProdutos');
const secMarmitas = document.getElementById('secMarmitas');
const form = document.getElementById('formProduto');
const formMarmita = document.getElementById('formMarmita');
const listaProdutosEl = document.getElementById('listaProdutos');

let productsCache = [];
let marmitaCarbos = [];
let marmitaProteinas = [];
let marmitaSaladas = [];
let marmitaAdicionais = [];

const MARMITA_CATEGORIES = {
  carbo: {
    legacyKey: 'carbos',
    listId: 'marmitaListaCarbos',
    nameId: 'marmitaCategoriaCarboNome',
    includedId: 'marmitaCarboInclusos',
    extraId: 'marmitaCarboValorExtra',
    defaultName: 'Carboidrato',
    defaultIncluded: 1,
    defaultExtra: 3,
    required: true,
  },
  proteinas: {
    legacyKey: 'proteinas',
    listId: 'marmitaListaProteinas',
    nameId: 'marmitaCategoriaProteinasNome',
    includedId: 'marmitaProteinasInclusos',
    extraId: 'marmitaProteinasValorExtra',
    defaultName: 'Proteinas',
    defaultIncluded: 1,
    defaultExtra: 5,
    required: true,
  },
  saladas: {
    legacyKey: 'saladas',
    listId: 'marmitaListaSaladas',
    nameId: 'marmitaCategoriaSaladasNome',
    includedId: 'marmitaSaladasInclusos',
    extraId: 'marmitaSaladasValorExtra',
    defaultName: 'Saladas',
    defaultIncluded: 1,
    defaultExtra: 2,
    required: true,
  },
  adicionais: {
    legacyKey: 'adicionais',
    listId: 'marmitaListaAdicionais',
    nameId: 'marmitaCategoriaAdicionaisNome',
    includedId: 'marmitaAdicionaisInclusos',
    extraId: 'marmitaAdicionaisValorExtra',
    defaultName: 'Adicionais',
    defaultIncluded: 0,
    defaultExtra: 4,
    required: false,
  },
};

function marmitaCategoryKeys() {
  return Object.keys(MARMITA_CATEGORIES);
}

function getMarmitaItems(key) {
  if (key === 'carbo') return marmitaCarbos;
  if (key === 'proteinas') return marmitaProteinas;
  if (key === 'saladas') return marmitaSaladas;
  if (key === 'adicionais') return marmitaAdicionais;
  return [];
}

function setMarmitaItems(key, items) {
  const normalizedItems = Array.isArray(items) ? items.map(String).filter(Boolean) : [];

  if (key === 'carbo') marmitaCarbos = normalizedItems;
  if (key === 'proteinas') marmitaProteinas = normalizedItems;
  if (key === 'saladas') marmitaSaladas = normalizedItems;
  if (key === 'adicionais') marmitaAdicionais = normalizedItems;
}

function numberInputValue(id, fallback) {
  const value = Number(document.getElementById(id)?.value ?? fallback);
  return Number.isFinite(value) && value >= 0 ? value : fallback;
}

function setMarmitaCategoryFields(key, data = {}) {
  const meta = MARMITA_CATEGORIES[key];
  if (!meta) return;

  document.getElementById(meta.nameId).value = String(data.nome || meta.defaultName);
  document.getElementById(meta.includedId).value = String(Number(data.inclusos ?? meta.defaultIncluded));
  document.getElementById(meta.extraId).value = Number(data.valorExtra ?? meta.defaultExtra).toFixed(2);
}

function getMarmitaCategoryConfig(key) {
  const meta = MARMITA_CATEGORIES[key];
  const nome = String(document.getElementById(meta.nameId)?.value || meta.defaultName).trim() || meta.defaultName;
  const inclusos = Math.max(0, Math.round(numberInputValue(meta.includedId, meta.defaultIncluded)));
  const valorExtra = numberInputValue(meta.extraId, meta.defaultExtra);

  return {
    nome,
    itens: getMarmitaItems(key).slice(),
    inclusos,
    valorExtra,
    obrigatoria: meta.required,
  };
}

function normalizeMarmitaCategoryConfig(config, key) {
  const meta = MARMITA_CATEGORIES[key];
  const current = config?.categorias?.[key] || {};
  const legacyItems = Array.isArray(config?.[meta.legacyKey]) ? config[meta.legacyKey] : [];

  return {
    nome: current.nome || meta.defaultName,
    itens: Array.isArray(current.itens) ? current.itens : legacyItems,
    inclusos: Number(current.inclusos ?? current.quantidadeInclusa ?? meta.defaultIncluded),
    valorExtra: Number(current.valorExtra ?? current.valorAdicional ?? meta.defaultExtra),
    obrigatoria: current.obrigatoria ?? meta.required,
  };
}

function applyMarmitaCategoriesConfig(config = null) {
  marmitaCategoryKeys().forEach((key) => {
    const normalized = normalizeMarmitaCategoryConfig(config || {}, key);
    setMarmitaItems(key, normalized.itens);
    setMarmitaCategoryFields(key, normalized);
    renderMarmitaList(MARMITA_CATEGORIES[key].listId, getMarmitaItems(key));
  });
}

function buildMarmitaCategoriesConfig() {
  return marmitaCategoryKeys().reduce((acc, key) => {
    acc[key] = getMarmitaCategoryConfig(key);
    return acc;
  }, {});
}

tabProdutos.onclick = () => {
  secProdutos.style.display = '';
  secMarmitas.style.display = 'none';
  tabProdutos.classList.add('primary');
  tabProdutos.classList.remove('outline');
  tabMarmitas.classList.remove('primary');
  tabMarmitas.classList.add('outline');
};

tabMarmitas.onclick = () => {
  secProdutos.style.display = 'none';
  secMarmitas.style.display = '';
  tabMarmitas.classList.add('primary');
  tabMarmitas.classList.remove('outline');
  tabProdutos.classList.remove('primary');
  tabProdutos.classList.add('outline');
};

function renderMarmitaList(containerId, arr) {
  const el = document.getElementById(containerId);
  el.innerHTML = '';

  arr.forEach((v, i) => {
    const row = document.createElement('div');
    row.className = 'marmita-item-row';
    row.innerHTML = `<span>${escapeHtml(v)}</span><div><button class="btn small" data-idx="${i}" data-list="${containerId}">Remover</button></div>`;
    el.appendChild(row);
  });
}

async function refreshProducts() {
  try {
    productsCache = await apiListProducts();
    renderListaProdutos();
    window.dispatchEvent(new Event('produtosAtualizados'));
  } catch (err) {
    alert(err.message || 'Erro ao carregar produtos');
    productsCache = [];
    renderListaProdutos();
  }
}

if (form) {
  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const id = document.getElementById('produtoId').value.trim();
    const name = document.getElementById('nome').value.trim();
    const category = document.getElementById('categoria').value;
    const priceRaw = document.getElementById('preco').value;
    const price = Number(priceRaw);
    const desc = document.getElementById('descricao').value.trim();
    let image = document.getElementById('fotoUrl').value.trim();
    const file = document.getElementById('fotoFile').files[0];

    if (!name) {
      alert('Informe o nome do produto.');
      return;
    }

    if (!category) {
      alert('Selecione a categoria do produto.');
      return;
    }

    if (priceRaw === '' || !Number.isFinite(price) || price <= 0) {
      alert('Informe um preco valido maior que zero.');
      return;
    }

    if (category === 'marmitas') {
      alert('Cadastre marmitas na aba Marmitas!');
      return;
    }

    if (file) {
      try {
        image = await fileToBase64(file);
      } catch (_err) {
        alert('Erro ao processar imagem. Tente novamente.');
        return;
      }
    }

    const produto = {
      id: id ? Number(id) : undefined,
      name,
      category,
      price,
      desc,
      image,
      isMarmita: false,
      marmitaConfig: null,
    };

    try {
      await apiSaveProduct(produto);
      resetForm();
      await refreshProducts();
      alert('Produto salvo com sucesso.');
    } catch (err) {
      alert(err.message || 'Erro ao salvar produto.');
    }
  });
}

if (formMarmita) {
  formMarmita.addEventListener('submit', async (e) => {
    e.preventDefault();

    const id = document.getElementById('marmitaId').value.trim();
    const name = document.getElementById('marmitaNome').value.trim() || 'Sem nome';
    const desc = document.getElementById('marmitaDesc').value.trim();
    let image = document.getElementById('marmitaFotoUrl').value.trim();
    const file = document.getElementById('marmitaFotoFile').files[0];

    if (file) {
      try {
        image = await fileToBase64(file);
      } catch (_err) {
        alert('Erro ao processar imagem. Tente novamente.');
        return;
      }
    }

    const precoP = Number(document.getElementById('marmitaPrecoP').value || 0);
    const precoM = Number(document.getElementById('marmitaPrecoM').value || 0);
    const precoG = Number(document.getElementById('marmitaPrecoG').value || 0);
    const categorias = buildMarmitaCategoriesConfig();

    const missingRequiredCategory = marmitaCategoryKeys().find((key) =>
      categorias[key].obrigatoria && categorias[key].itens.length === 0
    );
    if (missingRequiredCategory) {
      alert(`Adicione pelo menos 1 item em ${categorias[missingRequiredCategory].nome}.`);
      return;
    }

    const produto = {
      id: id ? Number(id) : undefined,
      name,
      category: 'marmitas',
      price: precoP,
      desc,
      image,
      isMarmita: true,
      marmitaConfig: {
        precoP,
        precoM,
        precoG,
        categorias,
        carbos: categorias.carbo.itens.slice(),
        proteinas: categorias.proteinas.itens.slice(),
        saladas: categorias.saladas.itens.slice(),
        adicionais: categorias.adicionais.itens.slice(),
      },
    };

    try {
      await apiSaveProduct(produto);
      resetFormMarmita();
      await refreshProducts();
      alert('Marmita salva com sucesso.');
    } catch (err) {
      alert(err.message || 'Erro ao salvar marmita.');
    }
  });
}

document.getElementById('marmitaAddCarbo').addEventListener('click', () => {
  const input = document.getElementById('marmitaNovoCarbo');
  const val = input.value.trim();
  if (!val) return;
  marmitaCarbos.push(val);
  input.value = '';
  renderMarmitaList('marmitaListaCarbos', marmitaCarbos);
});

document.getElementById('marmitaAddProteina').addEventListener('click', () => {
  const input = document.getElementById('marmitaNovoProteina');
  const val = input.value.trim();
  if (!val) return;
  marmitaProteinas.push(val);
  input.value = '';
  renderMarmitaList('marmitaListaProteinas', marmitaProteinas);
});

document.getElementById('marmitaAddSalada').addEventListener('click', () => {
  const input = document.getElementById('marmitaNovoSalada');
  const val = input.value.trim();
  if (!val) return;
  marmitaSaladas.push(val);
  input.value = '';
  renderMarmitaList('marmitaListaSaladas', marmitaSaladas);
});

document.getElementById('marmitaAddAdicional').addEventListener('click', () => {
  const input = document.getElementById('marmitaNovoAdicional');
  const val = input.value.trim();
  if (!val) return;
  marmitaAdicionais.push(val);
  input.value = '';
  renderMarmitaList('marmitaListaAdicionais', marmitaAdicionais);
});

document.addEventListener('click', async (e) => {
  const target = e.target;

  if (target.closest('#marmitaListaCarbos button')) {
    marmitaCarbos.splice(Number(target.dataset.idx), 1);
    renderMarmitaList('marmitaListaCarbos', marmitaCarbos);
  }

  if (target.closest('#marmitaListaProteinas button')) {
    marmitaProteinas.splice(Number(target.dataset.idx), 1);
    renderMarmitaList('marmitaListaProteinas', marmitaProteinas);
  }

  if (target.closest('#marmitaListaSaladas button')) {
    marmitaSaladas.splice(Number(target.dataset.idx), 1);
    renderMarmitaList('marmitaListaSaladas', marmitaSaladas);
  }

  if (target.closest('#marmitaListaAdicionais button')) {
    marmitaAdicionais.splice(Number(target.dataset.idx), 1);
    renderMarmitaList('marmitaListaAdicionais', marmitaAdicionais);
  }

  if (target.matches('#listaProdutos .edit')) {
    const id = target.dataset.id;
    carregarParaEdicao(id);
  }

  if (target.matches('#listaProdutos .remove')) {
    const id = target.dataset.id;
    if (!confirm('Remover produto?')) return;

    try {
      await apiDeleteProduct(id);
      await refreshProducts();
      alert('Produto removido com sucesso.');
    } catch (err) {
      alert(err.message || 'Erro ao remover produto.');
    }
  }
});

function resetForm() {
  form.reset();
  document.getElementById('produtoId').value = '';
}

function resetFormMarmita() {
  formMarmita.reset();
  document.getElementById('marmitaId').value = '';
  applyMarmitaCategoriesConfig();
}

function renderListaProdutos() {
  listaProdutosEl.innerHTML = '';

  if (productsCache.length === 0) {
    listaProdutosEl.textContent = 'Nenhum produto cadastrado.';
    return;
  }

  productsCache.forEach((p) => {
    const line = document.createElement('div');
    line.className = 'produto-line';
    line.innerHTML = `
  <div style="display:flex;align-items:center;gap:10px">
    <img src="${p.image || 'https://via.placeholder.com/120x90'}" alt="${escapeHtml(p.name)}" style="max-width:80px;max-height:80px;object-fit:cover;border-radius:6px;" />
    <div>
      <div style="font-weight:700">${escapeHtml(p.name)}</div>
      <div style="font-size:13px;color:#666">${escapeHtml(p.category)} - R$ ${formatMoneyValue(p.price || 0)}</div>
    </div>
  </div>
  <div>
    <button class="btn small edit" data-id="${p.id}">Editar</button>
    <button class="btn small remove" data-id="${p.id}">Remover</button>
  </div>
`;
    listaProdutosEl.appendChild(line);
  });
}

function carregarParaEdicao(id) {
  const p = productsCache.find((x) => String(x.id) === String(id));
  if (!p) return;

  if (p.isMarmita) {
    tabMarmitas.click();
    document.getElementById('marmitaId').value = p.id;
    document.getElementById('marmitaNome').value = p.name;
    document.getElementById('marmitaDesc').value = p.desc || '';
    document.getElementById('marmitaFotoUrl').value = p.image || '';
    document.getElementById('marmitaFotoFile').value = '';
    document.getElementById('marmitaPrecoP').value = p.marmitaConfig?.precoP || '';
    document.getElementById('marmitaPrecoM').value = p.marmitaConfig?.precoM || '';
    document.getElementById('marmitaPrecoG').value = p.marmitaConfig?.precoG || '';
    applyMarmitaCategoriesConfig(p.marmitaConfig || {});
  } else {
    tabProdutos.click();
    document.getElementById('produtoId').value = p.id;
    document.getElementById('nome').value = p.name;
    document.getElementById('categoria').value = p.category;
    document.getElementById('preco').value = p.price || 0;
    document.getElementById('descricao').value = p.desc || '';
    document.getElementById('fotoUrl').value = p.image || '';
    document.getElementById('fotoFile').value = '';
  }
}

function fileToBase64(file) {
  return new Promise((res, rej) => {
    const fr = new FileReader();
    fr.onload = () => res(fr.result);
    fr.onerror = rej;
    fr.readAsDataURL(file);
  });
}

function escapeHtml(s) {
  if (s == null) return '';
  return String(s)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}

document.getElementById('resetForm').addEventListener('click', (e) => {
  e.preventDefault();
  resetForm();
});

document.getElementById('resetFormMarmita').addEventListener('click', (e) => {
  e.preventDefault();
  resetFormMarmita();
});

tabProdutos.click();
refreshProducts();
