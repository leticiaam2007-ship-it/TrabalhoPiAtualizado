function loadProducts() {
  try { return JSON.parse(localStorage.getItem('produtos') || '[]'); } catch (e) { return []; }
}
function saveCart(cart) {
  localStorage.setItem('carrinho', JSON.stringify(cart));
}
function loadCart() {
  try { return JSON.parse(localStorage.getItem('carrinho') || '[]'); } catch (e) { return []; }
}

const urlParams = new URLSearchParams(window.location.search);
const marmitaId = urlParams.get('id');
const produtos = loadProducts();
const marmita = produtos.find(p => p.id === marmitaId && p.isMarmita);

const marmitaTamanho = document.getElementById('marmitaTamanho');
const marmitaOpcoes = document.getElementById('marmitaOpcoes');
const marmitaValor = document.getElementById('marmitaValor');
const formMarmita = document.getElementById('formMarmita');

if (!marmita) {
  document.body.innerHTML = '<h2>Marmita não encontrada!</h2>';
  throw new Error('Marmita não encontrada');
}

function renderTamanhos() {
  const cfg = marmita.marmitaConfig || {};
  marmitaTamanho.innerHTML = `
    <option value="P">Pequena - R$ ${Number(cfg.precoP || 0).toFixed(2)}</option>
    <option value="M">Média - R$ ${Number(cfg.precoM || 0).toFixed(2)}</option>
    <option value="G">Grande - R$ ${Number(cfg.precoG || 0).toFixed(2)}</option>
  `;
}
function renderOpcoes() {
  const cfg = marmita.marmitaConfig || {};
  let html = '';
  
  html += `<div class="field"><label>Carboidrato</label>`;
  (cfg.carbos || []).forEach((c, i) => {
    html += `<label style="margin-right:10px;">
      <input type="radio" name="carbo" value="${c}" ${i === 0 ? 'checked' : ''}> ${c}
    </label>`;
  });
  html += `</div>`;
  
  html += `<div class="field"><label>Proteínas</label>`;
  (cfg.proteinas || []).forEach((p) => {
    html += `<label style="margin-right:10px;">
      <input type="checkbox" name="proteina" value="${p}"> ${p}
    </label>`;
  });
  html += `</div>`;
  
  html += `<div class="field"><label>Saladas</label>`;
  (cfg.saladas || []).forEach((s) => {
    html += `<label style="margin-right:10px;">
      <input type="checkbox" name="salada" value="${s}"> ${s}
    </label>`;
  });
  html += `</div>`;
  
  html += `<div class="field"><label>Adicionais</label>`;
  (cfg.adicionais || []).forEach((a) => {
    html += `<label style="margin-right:10px;">
      <input type="checkbox" name="adicional" value="${a}"> ${a}
    </label>`;
  });
  html += `</div>`;
  marmitaOpcoes.innerHTML = html;
}
function atualizarValor() {
  const tamanho = marmitaTamanho.value;
  const cfg = marmita.marmitaConfig || {};
  let valor = 0;
  if (tamanho === 'P') valor = Number(cfg.precoP || 0);
  if (tamanho === 'M') valor = Number(cfg.precoM || 0);
  if (tamanho === 'G') valor = Number(cfg.precoG || 0);
  marmitaValor.textContent = `Valor: R$ ${valor.toFixed(2)}`;
}

marmitaTamanho.addEventListener('change', atualizarValor);

formMarmita.onsubmit = (e) => {
  e.preventDefault();
  const tamanho = marmitaTamanho.value;
  const cfg = marmita.marmitaConfig || {};
  let valor = 0;
  if (tamanho === 'P') valor = Number(cfg.precoP || 0);
  if (tamanho === 'M') valor = Number(cfg.precoM || 0);
  if (tamanho === 'G') valor = Number(cfg.precoG || 0);

  const carbo = formMarmita.querySelector('input[name="carbo"]:checked')?.value || '';
  const proteinas = Array.from(formMarmita.querySelectorAll('input[name="proteina"]:checked')).map(i => i.value);
  const saladas = Array.from(formMarmita.querySelectorAll('input[name="salada"]:checked')).map(i => i.value);
  const adicionais = Array.from(formMarmita.querySelectorAll('input[name="adicional"]:checked')).map(i => i.value);

  if (!carbo) return alert('Escolha um carboidrato!');

  
  const carrinho = loadCart();
  carrinho.push({
    id: marmita.id,
    name: marmita.name,
    image: marmita.image,
    isMarmita: true,
    tamanho,
    valor,
    carbo,
    proteinas,
    saladas,
    adicionais,
    quantidade: 1
  });
  saveCart(carrinho);
  window.location.href = "index.html";
};

renderTamanhos();
renderOpcoes();
atualizarValor();