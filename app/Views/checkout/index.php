<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Finalizar Compra - Cantina</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  <style>
    .checkout-page {
      max-width: 1080px;
      margin: 120px auto 40px;
      padding: 0 14px;
      display: grid;
      grid-template-columns: 1.2fr 0.8fr;
      gap: 18px;
    }

    .checkout-card {
      background: var(--card);
      border-radius: 14px;
      box-shadow: 0 10px 24px rgba(0, 0, 0, 0.32);
      padding: 20px;
      color: #2d1a12;
    }

    .checkout-card h2 {
      margin: 0 0 14px;
      font-size: 1.2rem;
      color: var(--highlight);
    }

    .checkout-items {
      display: flex;
      flex-direction: column;
      gap: 10px;
      max-height: 420px;
      overflow-y: auto;
      padding-right: 4px;
    }

    .checkout-item {
      background: #d9ccc5;
      border-radius: 10px;
      padding: 10px;
      display: grid;
      grid-template-columns: 58px 1fr auto;
      gap: 10px;
      align-items: center;
    }

    .checkout-item img {
      width: 58px;
      height: 58px;
      border-radius: 8px;
      object-fit: cover;
      background: #fff;
    }

    .checkout-item .title {
      font-weight: 700;
      margin-bottom: 2px;
      font-size: 0.95rem;
    }

    .checkout-item .meta {
      font-size: 0.9rem;
      line-height: 1.35;
      color: #4a2b20;
    }

    .item-actions {
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .qty-btn {
      width: 28px;
      height: 28px;
      border-radius: 8px;
      border: 0;
      font-weight: 700;
      cursor: pointer;
      background: #402b1f;
      color: #d9ccc5;
    }

    .qty-value {
      min-width: 18px;
      text-align: center;
      font-size: 0.9rem;
      font-weight: 700;
    }

    .remove-btn {
      border: 0;
      cursor: pointer;
      background: transparent;
      color: #402b1f;
      font-weight: 700;
      text-decoration: underline;
      font-size: 0.9rem;
      padding: 0;
    }

    .totals {
      margin-top: 12px;
      border-top: 1px solid rgba(0, 0, 0, 0.15);
      padding-top: 12px;
      font-size: 0.95rem;
    }

    .totals .line {
      display: flex;
      justify-content: space-between;
      margin-bottom: 6px;
    }

    .totals .line.total {
      font-size: 1.05rem;
      font-weight: 700;
      color: #2a170f;
    }

    .payment-box {
      margin-top: 8px;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .payment-option {
      background: #d9ccc5;
      padding: 10px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.92rem;
    }

    .checkout-actions {
      margin-top: 14px;
      display: flex;
      gap: 10px;
    }

    .checkout-actions .btn {
      flex: 1;
      text-align: center;
      text-decoration: none;
    }

    .status-message {
      font-size: 0.9rem;
      margin-top: 8px;
      color: #402b1f;
      min-height: 20px;
    }

    .empty-block {
      background: #d9ccc5;
      border-radius: 10px;
      padding: 14px;
      font-size: 0.95rem;
      color: #402b1f;
    }

    @media (max-width: 920px) {
      .checkout-page {
        grid-template-columns: 1fr;
      }
    }
  </style>
  <link rel="stylesheet" href="topbar.css" />
</head>
<body>
  <header class="topbar site-topbar">
    <a class="site-topbar-brand" href="index.php" aria-label="Inicio">
      <picture class="site-topbar-logo-wrap">
        <source media="(max-width: 640px)" srcset="imagens/logo-mobile.png">
        <img class="site-topbar-logo" src="imagens/logo-topbar.png" alt="Sabores Tecnicos - Cantina Online">
      </picture>
    </a>
    <nav class="site-topbar-actions">
      <a class="btn site-topbar-btn" href="index.php">Voltar</a>
      <a class="btn site-topbar-btn" href="deslogar.php">Deslogar</a>
    </nav>
  </header>

  <main class="checkout-page">
    <section class="checkout-card">
      <h2>Resumo do Carrinho</h2>
      <div id="checkoutItems" class="checkout-items"></div>
      <div id="checkoutEmpty" class="empty-block" style="display:none;">
        Seu carrinho esta vazio. Voltando para o cardapio...
      </div>
    </section>

    <section class="checkout-card">
      <h2>Finalizar Compra</h2>
      <form id="checkoutForm">
        <div class="payment-box">
          <label class="payment-option">
            <input type="radio" name="pagamento" value="dinheiro" checked />
            Dinheiro
          </label>
          <label class="payment-option">
            <input type="radio" name="pagamento" value="cartao" />
            Cartão
          </label>
          <label class="payment-option">
            <input type="radio" name="pagamento" value="pix" />
            Pix
          </label>
        </div>

        <div class="totals">
          <div class="line"><span>Subtotal</span><span id="subtotalValue">R$ 0,00</span></div>
          <div class="line total"><span>Total</span><span id="totalValue">R$ 0,00</span></div>
        </div>

        <div class="checkout-actions">
          <a href="index.php" class="btn outline">Continuar Comprando</a>
          <button id="confirmBtn" type="submit" class="btn primary">Confirmar Pedido</button>
        </div>
      </form>
      <div id="checkoutStatus" class="status-message"></div>
    </section>
  </main>

  <script src="finalizar_compra.js"></script>
</body>
</html>
