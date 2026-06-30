<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Painel Admin - Cantina</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  <style>

  @import url('https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap');

body {
  background-color: #8C6E54;
  color: #8C6E54;
  font-family: "Sora", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
  margin: 0;
  overflow-x: hidden;
}

.topbar {
  position: fixed;
  top: 0;
  width: 100%;
  background-color: #402B1F;
  color: #fff;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 40px;
  z-index: 100;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
}

.logo {
  font-weight: 700;
  font-size: 1.3rem;
}

.topbar .btn {
  background: #8C6E54;
  color: #402B1F;
  border: none;
  padding: 8px 16px;
  border-radius: 8px;
  text-decoration: none;
  transition: 0.2s;
}

.topbar .btn:hover {
  background: #BFA288;
  color: #8C6E54;
}

.container.admin-grid {
  margin-top: 100px;
  padding: 40px;
  display: grid;
  grid-template-columns: 1fr;
  gap: 30px;
  transition: all 0.3s ease;
}

@media (min-width: 1000px) {
  .container.admin-grid {
    grid-template-columns: 1fr 1fr;
    align-items: start;
  }
}

.card.admin-card {
  background: #BFA288;
  border-radius: 16px;
  padding: 25px;
  box-shadow: 0 0 12px rgba(0, 0, 0, 0.4);
  transition: transform 0.3s ease, box-shadow 0.3s ease, opacity 0.3s ease;
  opacity: 1;
}

.card.admin-card.hidden {
  opacity: 0;
  transform: translateY(15px);
  pointer-events: none;
}

.card.admin-card h2 {
  color: #402B1F;
  margin-bottom: 20px;
  font-weight: 600;
  font-size: 1.2rem;
}

.card.admin-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 0 20px rgba(240, 74, 74, 0.25);
}

.tabs {
  grid-column: 1 / -1;
  display: flex;
  justify-content: center;
  gap: 10px;
}

.tabs .btn {
  border-radius: 10px;
  font-weight: 500;
  transition: all 0.3s ease;
}

.tabs .btn.primary {
  background: #402B1F;
  color: #BFA288;
}

.tabs .btn.outline {
  border: 1px solid #402B1F;
  color: #402B1F;
  background: transparent;
}

.tabs .btn:hover {
  background: #402B1F;
  color: #fff;
}

.field {
  margin-bottom: 15px;
  display: flex;
  flex-direction: column;
}

label {
  margin-bottom: 6px;
  color: #402B1F;
  font-size: 0.95rem;
}

input, select, textarea {
  background: #D9CCC5;
  color: #402B1F;
  border: 1px solid #402B1F;
  border-radius: 8px;
  padding: 10px;
  font-family: inherit;
  font-size: 0.95rem;
}

input:focus, select:focus, textarea:focus {
  border-color: #402B1F;
  outline: none;
}

.field-inline {
  display: flex;
  gap: 10px;
}

.btn {
  cursor: pointer;
  border: none;
  border-radius: 8px;
  padding: 10px 16px;
  font-family: inherit;
  font-size: 1rem;
  font-weight: 600;
  transition: all 0.2s ease;
}

.btn.primary {
  background: #402B1F;
  color: #d9c5ac;
}

.btn.primary:hover {
  background: #d9c5ac;
}

.btn.outline {
  background: transparent;
  border: 1px solid #402B1F;
  color: #402B1F;
}

.btn.outline:hover {
  background: #D9CCC5;
  color: #BFA288;
}

.btn.small {
  padding: 6px 10px;
  font-size: 0.9rem;
  background: #402B1F;
  color: #d9c5ac;
  font-family: inherit;
}

.marmita-lists {
  display: grid;
  grid-template-columns: 1fr;
  gap: 16px;
  margin: 18px 0;
}

.marmita-category-admin {
  background: rgba(217, 204, 197, 0.42);
  border: 1px solid rgba(64, 43, 31, 0.18);
  border-radius: 14px;
  padding: 14px;
}

.marmita-category-admin h3 {
  margin: 0 0 12px;
  color: #402B1F;
  font-size: 1.05rem;
  font-weight: 700;
}

.marmita-category-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.2fr) minmax(120px, 0.55fr) minmax(140px, 0.65fr);
  gap: 10px;
}

.marmita-category-items {
  margin-top: 12px;
  display: grid;
  gap: 8px;
}

.marmita-item-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  background: rgba(255, 255, 255, 0.32);
  border-radius: 8px;
  padding: 7px 8px;
  color: #402B1F;
}

.marmita-add-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) auto;
  gap: 8px;
  margin-top: 10px;
}

@media (max-width: 900px) {
  html,
  body {
    max-width: 100%;
    overflow-x: hidden;
  }

  .container.admin-grid {
    width: 100%;
    max-width: 100%;
    margin: 16px auto 0;
    padding: 16px;
    gap: 16px;
    grid-template-columns: 1fr;
  }

  .tabs {
    width: 100%;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0;
    margin-bottom: 16px !important;
    overflow: hidden;
    border: 1px solid #402B1F;
    border-radius: 12px;
  }

  .tabs .btn {
    width: 100%;
    min-height: 50px;
    border-radius: 0;
    padding: 12px 8px;
    text-align: center;
    white-space: normal;
  }

  .card.admin-card {
    width: 100%;
    max-width: 100%;
    padding: 18px 16px;
    border-radius: 14px;
    overflow: hidden;
  }

  .card.admin-card:hover {
    transform: none;
  }

  .field {
    width: 100%;
    margin-bottom: 14px;
  }

  label {
    font-size: 1rem;
  }

  input,
  select,
  textarea {
    width: 100%;
    min-height: 48px;
    padding: 12px;
    font-size: 16px;
  }

  textarea {
    min-height: 96px;
  }

  .field-inline,
  .marmita-category-grid,
  .marmita-add-row {
    grid-template-columns: 1fr;
    flex-direction: column;
  }

  .form-actions {
    display: grid;
    grid-template-columns: 1fr;
    gap: 10px;
  }

  .form-actions .btn,
  .marmita-add-row .btn,
  .btn.small {
    width: 100%;
    min-height: 48px;
  }

  .marmita-category-admin {
    max-width: 100%;
    padding: 12px;
  }

  .marmita-item-row {
    align-items: stretch;
    flex-direction: column;
  }

  #listaProdutos {
    max-height: none;
    overflow-y: visible;
    padding: 12px;
  }

  .produto-line {
    display: grid;
    grid-template-columns: 1fr;
    gap: 12px;
    max-width: 100%;
  }
}

#listaProdutos {
  background: #d9c5ac;
  color: #402B1F;
  border-radius: 8px;
  padding: 15px;
  max-height: 500px;
  overflow-y: auto;
}

#listaProdutos::-webkit-scrollbar {
  width: 6px;
}

#listaProdutos::-webkit-scrollbar-thumb {
  background-color: #402B1F;
  border-radius: 10px;
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(15px); }
  to { opacity: 1; transform: translateY(0); }
}

.admin-card {
  animation: fadeIn 0.4s ease;
}

.admin-mobile-menu,
.site-topbar-mobile-menu-toggle {
  display: none;
}

@media (max-width: 900px) {
  .site-admin-topbar {
    position: sticky !important;
    top: 0;
    height: 82px !important;
    min-height: 82px !important;
    display: grid !important;
    grid-template-columns: minmax(0, 1fr) auto !important;
    align-items: center !important;
    gap: 12px !important;
    padding: 0 16px !important;
    background: #402B1F !important;
  }

  .site-admin-topbar .site-topbar-brand {
    grid-column: 1 !important;
    width: auto !important;
    justify-self: start !important;
    justify-content: flex-start !important;
    order: 0 !important;
  }

  .site-admin-topbar .site-topbar-logo {
    width: 88px !important;
    height: 64px !important;
    max-width: 92px !important;
    object-fit: contain !important;
  }

  .site-admin-topbar .site-topbar-actions,
  .site-admin-topbar .site-topbar-left {
    display: none !important;
  }

  .site-admin-topbar .site-topbar-mobile-menu-toggle {
    display: inline-flex !important;
    grid-column: 2 !important;
    justify-self: end !important;
    width: 54px !important;
    min-width: 54px !important;
    height: 54px !important;
    min-height: 54px !important;
    padding: 0 !important;
    border: 0 !important;
    border-radius: 999px !important;
    background: transparent !important;
    color: #D9CCC5 !important;
    box-shadow: none !important;
    transform: none !important;
  }

  .site-admin-topbar .hamburger-line {
    width: 30px;
    height: 3.5px;
    background: #D9CCC5;
  }

  .admin-mobile-menu {
    position: fixed !important;
    top: 88px !important;
    left: 16px !important;
    right: 16px !important;
    z-index: 170 !important;
    display: grid !important;
    gap: 6px !important;
    max-height: 0 !important;
    overflow: hidden !important;
    padding: 0 12px !important;
    border: 1px solid rgba(217, 204, 197, 0.18) !important;
    border-radius: 14px !important;
    background: #402B1F !important;
    box-shadow: 0 14px 28px rgba(0, 0, 0, 0.28) !important;
    opacity: 0 !important;
    pointer-events: none !important;
    transform: translateY(-8px) !important;
    transition: max-height 260ms ease, opacity 220ms ease, padding 260ms ease, transform 260ms ease !important;
  }

  .admin-mobile-menu[hidden] {
    display: none !important;
  }

  .admin-mobile-menu.open {
    display: grid !important;
    max-height: 260px !important;
    padding: 10px 12px !important;
    opacity: 1 !important;
    pointer-events: auto !important;
    transform: translateY(0) !important;
  }

  .admin-mobile-menu a {
    min-height: 48px;
    display: flex;
    align-items: center;
    padding: 10px 12px;
    border-radius: 10px;
    color: #D9CCC5;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
  }

  .admin-mobile-menu a:hover,
  .admin-mobile-menu a:focus-visible {
    background: #BFA288;
    color: #402B1F;
    outline: none;
  }
}
  </style>
  <link rel="stylesheet" href="topbar.css" />
</head>
<body>
  <header class="topbar site-topbar site-topbar-mobile-simple site-admin-topbar">
    <a class="site-topbar-brand" href="index.php" aria-label="Inicio">
      <picture class="site-topbar-logo-wrap">
        <source media="(max-width: 640px)" srcset="imagens/logo-mobile.png">
        <img class="site-topbar-logo" src="imagens/logo-topbar.png" alt="Sabores Tecnicos - Cantina Online">
      </picture>
    </a>
    <nav class="site-topbar-actions">
      <a href="pedidos.php" class="btn site-topbar-btn">Pedidos</a>
      <a href="index.php" class="btn site-topbar-btn">Voltar ao Site</a>
      <a href="deslogar.php" class="btn site-topbar-btn">Deslogar</a>
    </nav>
    <button
      id="adminMobileMenuToggle"
      class="menu-toggle site-topbar-mobile-menu-toggle"
      type="button"
      aria-label="Abrir menu administrativo"
      aria-expanded="false"
      aria-controls="adminMobileMenu"
      data-mobile-menu-toggle
    >
      <span class="hamburger-line"></span>
      <span class="hamburger-line"></span>
      <span class="hamburger-line"></span>
    </button>
  </header>

  <nav id="adminMobileMenu" class="site-mobile-dropdown admin-mobile-menu" aria-hidden="true" hidden>
    <a href="pedidos.php">Pedidos</a>
    <a href="index.php">Voltar ao Site</a>
    <a href="deslogar.php">Deslogar</a>
  </nav>

  <main class="container admin-grid">
    <div class="tabs" style="margin-bottom:20px;">
      <button id="tabProdutos" class="btn primary">Produtos</button>
      <button id="tabMarmitas" class="btn outline">Marmitas</button>
    </div>

    <section id="secProdutos" class="card admin-card">
      <h2>Adicionar / Editar Produto</h2>
      <form id="formProduto">
        <input type="hidden" id="produtoId" />
        <div class="field">
          <label>Nome</label>
          <input id="nome" />
        </div>
        <div class="field">
          <label>Categoria</label>
          <select id="categoria">
            <option value="salgados">Salgados</option>
            <option value="produtos">Produtos</option>
            <option value="refrigerantes">Bebidas</option>
            <option value="combos">Combos</option>
          </select>
        </div>
        <div class="field">
          <label>Preco</label>
          <input id="preco" type="number" step="0.01" min="0" />
        </div>
        <div class="field">
          <label>Descricao</label>
          <textarea id="descricao"></textarea>
        </div>
        <div class="field">
          <label>Foto (URL ou upload)</label>
          <input id="fotoUrl" placeholder="http(s) ou escolha um arquivo" />
          <input id="fotoFile" type="file" accept="image/*" />
        </div>
        <div class="form-actions">
          <button type="submit" class="btn primary">Salvar Produto</button>
          <button type="button" id="resetForm" class="btn outline">Limpar</button>
        </div>
      </form>
    </section>

    <section id="secMarmitas" class="card admin-card" style="display:none;">
      <h2>Adicionar / Editar Marmita</h2>
      <form id="formMarmita">
        <input type="hidden" id="marmitaId" />
        <div class="field">
          <label>Nome</label>
          <input id="marmitaNome" />
        </div>
        <div class="field-inline">
          <div class="field">
            <label>Preço P</label>
            <input id="marmitaPrecoP" type="number" step="0.01" min="0" />
          </div>
          <div class="field">
            <label>Preço M</label>
            <input id="marmitaPrecoM" type="number" step="0.01" min="0" />
          </div>
          <div class="field">
            <label>Preço G</label>
            <input id="marmitaPrecoG" type="number" step="0.01" min="0" />
          </div>
        </div>
        <div class="field">
          <label>Descricao</label>
          <textarea id="marmitaDesc"></textarea>
        </div>
        <div class="field">
          <label>Foto (URL ou upload)</label>
          <input id="marmitaFotoUrl" placeholder="http(s) ou escolha um arquivo" />
          <input id="marmitaFotoFile" type="file" accept="image/*" />
        </div>
        <div class="marmita-lists">
          <div class="marmita-category-admin">
            <h3>Carboidrato</h3>
            <div class="marmita-category-grid">
              <div class="field">
                <label>Nome da categoria</label>
                <input id="marmitaCategoriaCarboNome" value="Carboidrato" />
              </div>
              <div class="field">
                <label>Inclusos gratis</label>
                <input id="marmitaCarboInclusos" type="number" min="0" step="1" value="1" />
              </div>
              <div class="field">
                <label>Valor extra</label>
                <input id="marmitaCarboValorExtra" type="number" min="0" step="0.01" value="3.00" />
              </div>
            </div>
            <label>Itens disponiveis</label>
            <div id="marmitaListaCarbos"></div>
            <div class="marmita-add-row">
              <input id="marmitaNovoCarbo" placeholder="Adicionar carbo" />
              <button type="button" id="marmitaAddCarbo" class="btn small">Adicionar</button>
            </div>
          </div>
          <div class="marmita-category-admin">
            <h3>Proteinas</h3>
            <div class="marmita-category-grid">
              <div class="field">
                <label>Nome da categoria</label>
                <input id="marmitaCategoriaProteinasNome" value="Proteinas" />
              </div>
              <div class="field">
                <label>Inclusos gratis</label>
                <input id="marmitaProteinasInclusos" type="number" min="0" step="1" value="1" />
              </div>
              <div class="field">
                <label>Valor extra</label>
                <input id="marmitaProteinasValorExtra" type="number" min="0" step="0.01" value="5.00" />
              </div>
            </div>
            <label>Itens disponiveis</label>
            <div id="marmitaListaProteinas"></div>
            <div class="marmita-add-row">
              <input id="marmitaNovoProteina" placeholder="Adicionar proteina" />
              <button type="button" id="marmitaAddProteina" class="btn small">Adicionar</button>
            </div>
          </div>
          <div class="marmita-category-admin">
            <h3>Saladas</h3>
            <div class="marmita-category-grid">
              <div class="field">
                <label>Nome da categoria</label>
                <input id="marmitaCategoriaSaladasNome" value="Saladas" />
              </div>
              <div class="field">
                <label>Inclusos gratis</label>
                <input id="marmitaSaladasInclusos" type="number" min="0" step="1" value="1" />
              </div>
              <div class="field">
                <label>Valor extra</label>
                <input id="marmitaSaladasValorExtra" type="number" min="0" step="0.01" value="2.00" />
              </div>
            </div>
            <label>Itens disponiveis</label>
            <div id="marmitaListaSaladas"></div>
            <div class="marmita-add-row">
              <input id="marmitaNovoSalada" placeholder="Adicionar salada" />
              <button type="button" id="marmitaAddSalada" class="btn small">Adicionar</button>
            </div>
          </div>
          <div class="marmita-category-admin">
            <h3>Adicionais</h3>
            <div class="marmita-category-grid">
              <div class="field">
                <label>Nome da categoria</label>
                <input id="marmitaCategoriaAdicionaisNome" value="Adicionais" />
              </div>
              <div class="field">
                <label>Inclusos gratis</label>
                <input id="marmitaAdicionaisInclusos" type="number" min="0" step="1" value="0" />
              </div>
              <div class="field">
                <label>Valor extra</label>
                <input id="marmitaAdicionaisValorExtra" type="number" min="0" step="0.01" value="4.00" />
              </div>
            </div>
            <label>Itens disponiveis</label>
            <div id="marmitaListaAdicionais"></div>
            <div class="marmita-add-row">
              <input id="marmitaNovoAdicional" placeholder="Adicionar adicional" />
              <button type="button" id="marmitaAddAdicional" class="btn small">Adicionar</button>
            </div>
          </div>
        </div>
        <div class="form-actions">
          <button type="submit" class="btn primary">Salvar Marmita</button>
          <button type="button" id="resetFormMarmita" class="btn outline">Limpar</button>
        </div>
      </form>
    </section>

    <section class="card admin-card">
      <h2>Produtos Cadastrados</h2>
      <div id="listaProdutos"></div>
    </section>
  </main>

  <script src="topbar.js"></script>
  <script src="admin.js"></script>
</body>
</html>
