function uid(){ return Date.now().toString(36) + Math.random().toString(36).slice(2,6); }

function loadProducts(){ try { return JSON.parse(localStorage.getItem('produtos') || '[]'); } catch(e){ return []; } }
function saveProducts(list){
  localStorage.setItem('produtos', JSON.stringify(list));
  window.dispatchEvent(new Event('produtosAtualizados'));
}


const tabProdutos = document.getElementById('tabProdutos');
const tabMarmitas = document.getElementById('tabMarmitas');
const secProdutos = document.getElementById('secProdutos');
const secMarmitas = document.getElementById('secMarmitas');


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


const form = document.getElementById('formProduto');
const listaProdutosEl = document.getElementById('listaProdutos');

form.addEventListener('submit', async (e) => {
  e.preventDefault();
  const id = document.getElementById('produtoId').value || uid();
  const name = document.getElementById('nome').value.trim() || 'Sem nome';
  const category = document.getElementById('categoria').value;
  const price = Number(document.getElementById('preco').value || 0);
  const desc = document.getElementById('descricao').value.trim();
  let image = document.getElementById('fotoUrl').value.trim();
  const file = document.getElementById('fotoFile').files[0];

  if(file){
    try {
      image = await fileToBase64(file);
    } catch(err){
      alert('Erro ao processar imagem. Tente novamente.');
      return;
    }
  }

  
  if(category === 'marmitas') {
    alert('Cadastre marmitas na aba Marmitas!');
    return;
  }

  const produto = { id, name, category, price, desc, image, isMarmita: false };

  
  const list = loadProducts();
  const idx = list.findIndex(p => p.id === id);
  if(idx >= 0){
    list[idx] = produto;
  } else {
    list.push(produto);
  }
  saveProducts(list);
  resetForm();
  renderListaProdutos();
  alert('Produto salvo com sucesso.');
});

function resetForm(){
  form.reset();
  document.getElementById('produtoId').value = '';
}


const formMarmita = document.getElementById('formMarmita');
let marmitaCarbos = [], marmitaProteinas = [], marmitaSaladas = [], marmitaAdicionais = [];

function renderMarmitaList(containerId, arr){
  const el = document.getElementById(containerId);
  el.innerHTML = '';
  arr.forEach((v, i) => {
    const row = document.createElement('div');
    row.style.display = 'flex';
    row.style.justifyContent = 'space-between';
    row.style.marginBottom = '6px';
    row.innerHTML = `<span>${v}</span><div><button class="btn small" data-idx="${i}" data-list="${containerId}">Remover</button></div>`;
    el.appendChild(row);
  });
}


document.getElementById('marmitaAddCarbo').addEventListener('click', ()=>{
  const input = document.getElementById('marmitaNovoCarbo');
  const val = input.value.trim();
  if(!val) return;
  marmitaCarbos.push(val);
  input.value = '';
  renderMarmitaList('marmitaListaCarbos', marmitaCarbos);
});
document.getElementById('marmitaAddProteina').addEventListener('click', ()=>{
  const input = document.getElementById('marmitaNovoProteina');
  const val = input.value.trim();
  if(!val) return;
  marmitaProteinas.push(val);
  input.value = '';
  renderMarmitaList('marmitaListaProteinas', marmitaProteinas);
});
document.getElementById('marmitaAddSalada').addEventListener('click', ()=>{
  const input = document.getElementById('marmitaNovoSalada');
  const val = input.value.trim();
  if(!val) return;
  marmitaSaladas.push(val);
  input.value = '';
  renderMarmitaList('marmitaListaSaladas', marmitaSaladas);
});
document.getElementById('marmitaAddAdicional').addEventListener('click', ()=>{
  const input = document.getElementById('marmitaNovoAdicional');
  const val = input.value.trim();
  if(!val) return;
  marmitaAdicionais.push(val);
  input.value = '';
  renderMarmitaList('marmitaListaAdicionais', marmitaAdicionais);
});


document.addEventListener('click', (e) => {
  const target = e.target;
  if(target.closest('#marmitaListaCarbos button')){
    marmitaCarbos.splice(Number(target.dataset.idx), 1);
    renderMarmitaList('marmitaListaCarbos', marmitaCarbos);
  }
  if(target.closest('#marmitaListaProteinas button')){
    marmitaProteinas.splice(Number(target.dataset.idx), 1);
    renderMarmitaList('marmitaListaProteinas', marmitaProteinas);
  }
  if(target.closest('#marmitaListaSaladas button')){
    marmitaSaladas.splice(Number(target.dataset.idx), 1);
    renderMarmitaList('marmitaListaSaladas', marmitaSaladas);
  }
  if(target.closest('#marmitaListaAdicionais button')){
    marmitaAdicionais.splice(Number(target.dataset.idx), 1);
    renderMarmitaList('marmitaListaAdicionais', marmitaAdicionais);
  }

  
  if(target.matches('#listaProdutos .edit')){
    const id = target.dataset.id;
    carregarParaEdicao(id);
  }
  
  if(target.matches('#listaProdutos .remove')){
    const id = target.dataset.id;
    if(confirm('Remover produto?')){
      removerProduto(id);
    }
  }
});


formMarmita.addEventListener('submit', async (e) => {
  e.preventDefault();
  const id = document.getElementById('marmitaId').value || uid();
  const name = document.getElementById('marmitaNome').value.trim() || 'Sem nome';
  const desc = document.getElementById('marmitaDesc').value.trim();
  let image = document.getElementById('marmitaFotoUrl').value.trim();
  const file = document.getElementById('marmitaFotoFile').files[0];
  if(file){
    try {
      image = await fileToBase64(file);
    } catch(err){
      alert('Erro ao processar imagem. Tente novamente.');
      return;
    }
  }
  const precoP = Number(document.getElementById('marmitaPrecoP').value || 0);
  const precoM = Number(document.getElementById('marmitaPrecoM').value || 0);
  const precoG = Number(document.getElementById('marmitaPrecoG').value || 0);

  const produto = {
    id,
    name,
    category: 'marmitas',
    price: precoP, 
    desc,
    image,
    isMarmita: true,
    marmitaConfig: {
      precoP, precoM, precoG,
      carbos: marmitaCarbos.slice(),
      proteinas: marmitaProteinas.slice(),
      saladas: marmitaSaladas.slice(),
      adicionais: marmitaAdicionais.slice()
    }
  };

  
  const list = loadProducts();
  const idx = list.findIndex(p => p.id === id);
  if(idx >= 0){
    list[idx] = produto;
  } else {
    list.push(produto);
  }
  saveProducts(list);
  resetFormMarmita();
  renderListaProdutos();
  alert('Marmita salva com sucesso.');
});

function resetFormMarmita(){
  formMarmita.reset();
  document.getElementById('marmitaId').value = '';
  marmitaCarbos = []; marmitaProteinas = []; marmitaSaladas = []; marmitaAdicionais = [];
  renderMarmitaList('marmitaListaCarbos', marmitaCarbos);
  renderMarmitaList('marmitaListaProteinas', marmitaProteinas);
  renderMarmitaList('marmitaListaSaladas', marmitaSaladas);
  renderMarmitaList('marmitaListaAdicionais', marmitaAdicionais);
}


function renderListaProdutos(){
  const list = loadProducts();
  listaProdutosEl.innerHTML = '';
  if(list.length === 0){
    listaProdutosEl.textContent = 'Nenhum produto cadastrado.';
    return;
  }
  list.forEach(p => {
    const line = document.createElement('div');
    line.className = 'produto-line';
    line.innerHTML = `
  <div style="display:flex;align-items:center;gap:10px">
    <img src="${p.image || 'https://via.placeholder.com/120x90'}" alt="${p.name}" style="max-width:80px;max-height:80px;object-fit:cover;border-radius:6px;" />
    <div>
      <div style="font-weight:700">${p.name}</div>
      <div style="font-size:13px;color:#666">${p.category} - R$ ${Number(p.price || 0).toFixed(2)}</div>
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


function carregarParaEdicao(id){
  const list = loadProducts();
  const p = list.find(x => x.id === id);
  if(!p) return;
  if(p.isMarmita){
    
    tabMarmitas.click();
    document.getElementById('marmitaId').value = p.id;
    document.getElementById('marmitaNome').value = p.name;
    document.getElementById('marmitaDesc').value = p.desc || '';
    document.getElementById('marmitaFotoUrl').value = p.image || '';
    document.getElementById('marmitaFotoFile').value = '';
    document.getElementById('marmitaPrecoP').value = p.marmitaConfig.precoP || '';
    document.getElementById('marmitaPrecoM').value = p.marmitaConfig.precoM || '';
    document.getElementById('marmitaPrecoG').value = p.marmitaConfig.precoG || '';
    marmitaCarbos = p.marmitaConfig.carbos ? p.marmitaConfig.carbos.slice() : [];
    marmitaProteinas = p.marmitaConfig.proteinas ? p.marmitaConfig.proteinas.slice() : [];
    marmitaSaladas = p.marmitaConfig.saladas ? p.marmitaConfig.saladas.slice() : [];
    marmitaAdicionais = p.marmitaConfig.adicionais ? p.marmitaConfig.adicionais.slice() : [];
    renderMarmitaList('marmitaListaCarbos', marmitaCarbos);
    renderMarmitaList('marmitaListaProteinas', marmitaProteinas);
    renderMarmitaList('marmitaListaSaladas', marmitaSaladas);
    renderMarmitaList('marmitaListaAdicionais', marmitaAdicionais);
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


function removerProduto(id){
  const list = loadProducts();
  const idx = list.findIndex(p => p.id === id);
  if(idx >= 0){
    list.splice(idx, 1);
    saveProducts(list);
    renderListaProdutos();
  }
}


function fileToBase64(file){
  return new Promise((res, rej) => {
    const fr = new FileReader();
    fr.onload = () => res(fr.result);
    fr.onerror = rej;
    fr.readAsDataURL(file);
  });
}


renderListaProdutos();
tabProdutos.click();


document.getElementById('resetForm').addEventListener('click', (e) => {
  e.preventDefault();
  resetForm();
});

document.getElementById('resetFormMarmita').addEventListener('click', (e) => {
  e.preventDefault();
  resetFormMarmita();
});

