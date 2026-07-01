const abaButtons = Array.from(document.querySelectorAll('[data-aba]'));
const paineis = Array.from(document.querySelectorAll('[data-painel]'));

function ativarAba(aba) {
  const alvo = String(aba || 'dados').toLowerCase();

  abaButtons.forEach((btn) => {
    if (btn.dataset.aba === alvo) {
      btn.classList.add('ativo');
    } else {
      btn.classList.remove('ativo');
    }
  });

  paineis.forEach((painel) => {
    if (painel.dataset.painel === alvo) {
      painel.classList.add('ativo');
    } else {
      painel.classList.remove('ativo');
    }
  });

  const url = new URL(window.location.href);
  url.searchParams.set('aba', alvo);
  window.history.replaceState({}, '', url.toString());
}

abaButtons.forEach((btn) => {
  btn.addEventListener('click', () => {
    ativarAba(btn.dataset.aba || 'dados');
  });
});

window.addEventListener('DOMContentLoaded', () => {
  const url = new URL(window.location.href);
  const aba = url.searchParams.get('aba') || 'dados';
  ativarAba(aba);
});

