// 1. Pegamos o ID da marmita que veio pela URL
const urlParams = new URLSearchParams(window.location.search);
const marmitaId = urlParams.get('id');

// Elementos do HTML
const marmitaTamanho = document.getElementById('marmitaTamanho');
const marmitaOpcoes = document.getElementById('marmitaOpcoes');
const marmitaValor = document.getElementById('marmitaValor');
const formMarmita = document.getElementById('formMarmita');

let dadosMarmitaLocal = null;

// 2. FUNÇÃO QUE BUSCA OS DADOS NO BANCO (Substitui o loadProducts)
async function buscarDadosMarmita() {
    try {
        // Chamamos o Controller para buscar os dados do MySQL
        const response = await fetch(`../controllers/marmitaController.php?id=${marmitaId}`);
        const data = await response.json();
        
        if (!data) {
            document.body.innerHTML = '<h2>Marmita não encontrada no Banco!</h2>';
            return;
        }

        dadosMarmitaLocal = data;
        renderTamanhos(data.marmitaConfig);
        renderOpcoes(data.marmitaConfig);
        atualizarValor();
    } catch (error) {
        console.error("Erro ao carregar marmita:", error);
    }
}

// 3. FUNÇÕES DE RENDERIZAÇÃO (VIEW)
function renderTamanhos(cfg) {
    marmitaTamanho.innerHTML = `
        <option value="P">Pequena - R$ ${Number(cfg.precoP || 0).toFixed(2)}</option>
        <option value="M">Média - R$ ${Number(cfg.precoM || 0).toFixed(2)}</option>
        <option value="G">Grande - R$ ${Number(cfg.precoG || 0).toFixed(2)}</option>
    `;
}

function renderOpcoes(cfg) {
    let html = '';
    
    // Carboidratos
    html += `<div class="field"><label>Carboidrato</label>`;
    (cfg.carbos || []).forEach((c, i) => {
        html += `<label><input type="radio" name="carbo" value="${c}" ${i === 0 ? 'checked' : ''}> ${c}</label>`;
    });
    html += `</div>`;
    
    // Proteínas
    html += `<div class="field"><label>Proteínas</label>`;
    (cfg.proteinas || []).forEach((p) => {
        html += `<label><input type="checkbox" name="proteina" value="${p}"> ${p}</label>`;
    });
    html += `</div>`;

    marmitaOpcoes.innerHTML = html;
}

function atualizarValor() {
    const tamanho = marmitaTamanho.value;
    const cfg = dadosMarmitaLocal.marmitaConfig;
    let valor = 0;
    if (tamanho === 'P') valor = Number(cfg.precoP);
    if (tamanho === 'M') valor = Number(cfg.precoM);
    if (tamanho === 'G') valor = Number(cfg.precoG);
    marmitaValor.textContent = `Valor: R$ ${valor.toFixed(2)}`;
}

// 4. ENVIO DO PEDIDO (CONTROLLER)
formMarmita.onsubmit = async (e) => {
    e.preventDefault();

    const carbo = formMarmita.querySelector('input[name="carbo"]:checked')?.value;
    if (!carbo) return alert('Escolha um carboidrato!');

    const pedido = {
        id_marmita: marmitaId,
        tamanho: marmitaTamanho.value,
        carbo: carbo,
        proteinas: Array.from(formMarmita.querySelectorAll('input[name="proteina"]:checked')).map(i => i.value),
        quantidade: 1
    };

    // MÁGICA DO MVC: Envia para o PHP salvar no MySQL
    const res = await fetch('../controllers/marmitaController.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(pedido)
    });

    const resultado = await res.json();
    if (resultado.success) {
        alert('Pedido salvo no Banco de Dados!');
        window.location.href = "index.html";
    }
};

// Inicialização
marmitaTamanho.addEventListener('change', atualizarValor);
buscarDadosMarmita();