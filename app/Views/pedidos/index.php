<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Pedidos e Relatorio de Vendas</title>
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap');

    :root {
      --bg: #8c6e54;
      --panel: #bfa288;
      --panel-soft: #c7aa91;
      --card: #8f7c68;
      --header: #402b1f;
      --text: #1e110b;
      --bar: #4b7be0;
    }

    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      min-height: 100vh;
      font-family: "Sora", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
      background: var(--bg);
      color: var(--text);
    }

    .layout {
      max-width: 1440px;
      margin: 24px auto 0;
      padding: 0 20px 28px;
      display: grid;
      grid-template-columns: minmax(320px, 0.9fr) minmax(430px, 1.1fr);
      gap: 22px;
      align-items: start;
    }

    .orders-shell,
    .report-shell {
      background: var(--panel);
      border-radius: 20px;
      padding: 22px;
      box-shadow: 0 10px 24px rgba(64, 43, 31, 0.2);
    }

    .orders-shell {
      min-height: 240px;
    }

    .report-shell {
      display: grid;
      gap: 24px;
    }

    .panel-heading,
    .report-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 16px;
    }

    .panel-title,
    .report-title,
    .monthly-report-title {
      margin: 0;
      color: #1e110b;
      font-weight: 700;
      line-height: 1.05;
    }

    .panel-title {
      font-size: clamp(1.25rem, 2vw, 1.65rem);
    }

    .orders-list {
      display: flex;
      flex-direction: column;
      gap: 18px;
    }

    .order-card {
      background: var(--card);
      border-radius: 16px;
      padding: 16px;
      color: #000;
      box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.05);
    }

    .order-title {
      margin: 0;
      font-size: 1.35rem;
      line-height: 1.1;
      font-weight: 700;
    }

    .order-meta {
      margin-top: 8px;
      font-size: 0.9rem;
      line-height: 1.3;
    }

    .order-items-title {
      margin: 16px 0 8px;
      font-size: 1.3rem;
      font-weight: 700;
      line-height: 1;
    }

    .order-items {
      margin: 0 0 18px;
      padding-left: 22px;
      font-size: 1rem;
      line-height: 1.45;
    }

    .order-items li + li {
      margin-top: 5px;
    }

    .item-line {
      font-weight: 700;
      font-size: 0.94rem;
    }

    .item-desc {
      margin-top: 4px;
      font-size: 0.85rem;
      line-height: 1.35;
      color: #2f1b11;
      background: rgba(217, 204, 197, 0.38);
      border-radius: 8px;
      padding: 6px 8px;
    }

    .item-config {
      margin-top: 4px;
      font-size: 0.85rem;
      line-height: 1.35;
      color: #2e1a11;
      background: rgba(217, 204, 197, 0.45);
      border-radius: 8px;
      padding: 6px 8px;
    }

    .item-config b {
      font-weight: 700;
    }

    .btn-deliver {
      display: block;
      width: 74%;
      margin: 0 auto;
      border-radius: 999px;
      border: 2px solid #f2e5dc;
      background: #4a2e20;
      color: #fff;
      font-family: inherit;
      font-weight: 700;
      font-size: 0.95rem;
      padding: 10px 16px;
      cursor: pointer;
    }

    .btn-deliver:disabled {
      cursor: wait;
      opacity: 0.6;
    }

    .report-title {
      font-size: clamp(2rem, 4vw, 3rem);
      max-width: 360px;
    }

    #reportMonth {
      border: 1px solid #7d6453;
      background: #d9ccc5;
      border-radius: 9px;
      padding: 8px 10px;
      color: #2d1a12;
      font-family: inherit;
      font-weight: 700;
    }

    .report-bars {
      margin-top: 12px;
      display: flex;
      flex-direction: column;
      gap: 10px;
    }

    .bar-row {
      display: grid;
      grid-template-columns: minmax(120px, 170px) minmax(160px, 1fr) minmax(64px, auto);
      align-items: center;
      gap: 10px;
      min-height: 28px;
      font-size: 0.92rem;
    }

    .bar-label {
      text-align: right;
      color: #25160f;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .bar-wrap {
      position: relative;
      height: 16px;
      border-radius: 999px;
      background: rgba(64, 43, 31, 0.14);
    }

    .bar-fill {
      height: 100%;
      border-radius: 999px;
      background: var(--bar);
    }

    .bar-value {
      font-size: 0.9rem;
      font-weight: 700;
      color: #25160f;
      text-align: right;
    }

    .monthly-report {
      border-top: 1px solid rgba(64, 43, 31, 0.2);
      padding-top: 18px;
    }

    .monthly-report-title {
      font-size: clamp(1.25rem, 2.4vw, 1.75rem);
    }

    .monthly-report-note {
      margin: 5px 0 0;
      color: #4b2d1f;
      font-size: 0.88rem;
    }

    .monthly-total {
      display: grid;
      grid-template-columns: minmax(112px, 160px) minmax(160px, 1fr) minmax(104px, auto);
      align-items: center;
      gap: 10px;
      min-height: 30px;
      font-size: 0.9rem;
    }

    .monthly-value {
      color: #25160f;
      font-weight: 700;
      text-align: right;
      white-space: nowrap;
    }

    .empty-state {
      margin: 0;
      font-size: 1rem;
      opacity: 0.8;
      padding: 8px 0;
    }

    .status {
      margin: 0 0 10px;
      font-size: 0.9rem;
      font-weight: 700;
      color: #41281c;
    }

    .pedidos-topbar {
      position: sticky;
      top: 0;
      z-index: 150;
      min-height: 98px;
      display: grid;
      grid-template-columns: minmax(0, 1fr) auto minmax(0, 1fr);
      align-items: center;
      gap: 14px;
      padding: 9px clamp(12px, 3vw, 34px);
      background: var(--header);
      color: #d9ccc5;
      border-bottom: 1px solid rgba(217, 204, 197, 0.16);
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.34);
    }

    .pedidos-brand {
      grid-column: 2;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 0;
    }

    .pedidos-logo {
      display: block;
      width: auto;
      height: clamp(74px, 6vw, 90px);
      max-width: 100%;
      object-fit: contain;
    }

    .pedidos-actions {
      grid-column: 3;
      min-width: 0;
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 10px;
      flex-wrap: wrap;
    }

    .pedidos-actions:first-of-type {
      grid-column: 1;
      justify-content: flex-start;
    }

    .pedidos-topbar-btn,
    .pedidos-actions .btn-back {
      min-height: 34px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 6px 10px;
      border: 1px solid rgba(217, 204, 197, 0.24);
      border-radius: 9px;
      background: #bfa288;
      color: #402b1f;
      font-size: 1rem;
      font-weight: 600;
      line-height: 1.1;
      text-align: center;
      text-decoration: none;
      white-space: nowrap;
      transition: background-color 220ms ease, color 220ms ease, transform 220ms ease;
    }

    .pedidos-topbar-btn:hover,
    .pedidos-topbar-btn:focus-visible,
    .pedidos-actions .btn-back:hover,
    .pedidos-actions .btn-back:focus-visible {
      background: #d9ccc5;
      color: #402b1f;
      transform: translateY(-1px);
      outline: none;
    }

    .pedidos-mobile-menu-toggle,
    .pedidos-mobile-menu {
      display: none;
    }

    @media (max-width: 1080px) {
      .layout {
        grid-template-columns: 1fr;
      }

      .orders-shell,
      .report-shell {
        min-height: auto;
      }

      .report-title {
        font-size: 2.3rem;
      }
    }

    @media (max-width: 640px) {
      .layout {
        padding: 0 12px 16px;
        gap: 16px;
      }

      .orders-shell,
      .report-shell {
        border-radius: 18px;
        padding: 16px 14px;
      }

      .order-title {
        font-size: 1.7rem;
      }

      .order-items-title {
        font-size: 1.9rem;
      }

      .orders-list,
      .report-bars {
        display: flex;
        flex-direction: row;
        gap: 12px;
        overflow-x: auto;
        overflow-y: hidden;
        overscroll-behavior-x: contain;
        scroll-snap-type: x mandatory;
        -webkit-overflow-scrolling: touch;
        padding: 2px 2px 12px;
        margin-inline: -2px;
        cursor: grab;
      }

      .orders-list:active,
      .report-bars:active {
        cursor: grabbing;
      }

      .orders-list::-webkit-scrollbar,
      .report-bars::-webkit-scrollbar {
        height: 8px;
      }

      .orders-list::-webkit-scrollbar-track,
      .report-bars::-webkit-scrollbar-track {
        background: rgba(64, 43, 31, 0.12);
        border-radius: 999px;
      }

      .orders-list::-webkit-scrollbar-thumb,
      .report-bars::-webkit-scrollbar-thumb {
        background: rgba(64, 43, 31, 0.55);
        border-radius: 999px;
      }

      .order-card,
      .bar-row,
      .monthly-total,
      .empty-state {
        flex: 0 0 min(84vw, 320px);
        scroll-snap-align: start;
      }

      .order-card {
        max-height: 430px;
        overflow-y: auto;
      }

      .report-header {
        flex-direction: column;
        align-items: flex-start;
      }

      .bar-row,
      .monthly-total {
        grid-template-columns: 1fr;
        gap: 4px;
      }

      .bar-label,
      .bar-value,
      .monthly-value {
        text-align: left;
      }
    }

    @media (max-width: 900px) {
      .pedidos-topbar {
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

      .pedidos-brand {
        grid-column: 1 !important;
        width: auto !important;
        justify-self: start !important;
        justify-content: flex-start !important;
        order: 0 !important;
      }

      .pedidos-logo {
        content: url("imagens/logo-mobile.png");
        width: 88px !important;
        height: 64px !important;
        max-width: 92px !important;
        object-fit: contain !important;
      }

      .pedidos-actions {
        display: none !important;
      }

      .pedidos-mobile-menu-toggle {
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

      .pedidos-mobile-menu-toggle .hamburger-line {
        width: 30px;
        height: 3.5px;
        background: #D9CCC5;
      }

      .pedidos-mobile-menu-toggle.is-open .hamburger-line:nth-child(1) {
        transform: translateY(9.5px) rotate(45deg);
      }

      .pedidos-mobile-menu-toggle.is-open .hamburger-line:nth-child(3) {
        transform: translateY(-9.5px) rotate(-45deg);
      }

      .pedidos-mobile-menu {
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

      .pedidos-mobile-menu[hidden] {
        display: none !important;
      }

      .pedidos-mobile-menu.open {
        display: grid !important;
        max-height: 260px !important;
        padding: 10px 12px !important;
        opacity: 1 !important;
        pointer-events: auto !important;
        transform: translateY(0) !important;
      }

      .pedidos-mobile-menu a {
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

      .pedidos-mobile-menu a:hover,
      .pedidos-mobile-menu a:focus-visible {
        background: #BFA288;
        color: #402B1F;
        outline: none;
      }
    }
  </style>
  <link rel="stylesheet" href="topbar.css" />
</head>
<body>
  <header class="pedidos-topbar">
    <nav class="pedidos-actions" aria-label="Navegacao secundaria">
      <a class="pedidos-topbar-btn" href="admin.php">Voltar ao admin</a>
    </nav>
    <a class="pedidos-brand" href="index.php" aria-label="Inicio">
      <picture>
        <source media="(max-width: 640px)" srcset="imagens/logo-mobile.png">
        <img class="pedidos-logo" src="imagens/logo-topbar.png" alt="Sabores Tecnicos - Cantina Online">
      </picture>
    </a>
    <nav class="pedidos-actions" aria-label="Navegacao principal">
      <a class="pedidos-topbar-btn" href="index.php">Voltar ao Site</a>
      <a class="pedidos-topbar-btn" href="deslogar.php">Deslogar</a>
    </nav>
    <button
      id="pedidosMobileMenuToggle"
      class="menu-toggle pedidos-mobile-menu-toggle"
      type="button"
      aria-label="Abrir menu de pedidos"
      aria-expanded="false"
      aria-controls="pedidosMobileMenu"
      data-mobile-menu-toggle
    >
      <span class="hamburger-line"></span>
      <span class="hamburger-line"></span>
      <span class="hamburger-line"></span>
    </button>
  </header>

  <nav id="pedidosMobileMenu" class="pedidos-mobile-menu" aria-hidden="true" hidden>
    <a href="admin.php">Voltar ao admin</a>
    <a href="index.php">Voltar ao Site</a>
    <a href="deslogar.php">Deslogar</a>
  </nav>

  <main class="layout">
    <section class="orders-shell">
      <div class="panel-heading">
        <h2 class="panel-title">Pedidos abertos</h2>
      </div>
      <p id="ordersStatus" class="status">Carregando pedidos...</p>
      <div id="ordersList" class="orders-list"></div>
    </section>

    <section class="report-shell">
      <div class="report-header">
        <h2 class="report-title">Relatório de vendas</h2>
        <input type="month" id="reportMonth" />
      </div>
      <div id="reportBars" class="report-bars"></div>
      <section class="monthly-report">
        <div class="panel-heading">
          <div>
            <h3 class="monthly-report-title">Valor vendido por mês</h3>
            <p class="monthly-report-note">Total vendido em cada mês finalizado.</p>
          </div>
        </div>
        <div id="monthlyRevenueBars" class="report-bars"></div>
      </section>
    </section>
  </main>

  <script src="topbar.js"></script>
  <script src="pedidos.js"></script>
</body>
</html>
