<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Meus Pedidos - Cantina</title>
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap');

    :root {
      --bg: #8c6e54;
      --panel: #bfa288;
      --card: #d9ccc5;
      --header: #402b1f;
      --text: #2a170f;
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

    .topbar {
      position: sticky;
      top: 0;
      z-index: 10;
      background: var(--header);
      color: #d9ccc5;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 18px;
      box-shadow: 0 4px 14px rgba(0, 0, 0, 0.3);
    }

    .topbar h1 {
      margin: 0;
      font-size: 1.5rem;
      font-weight: 700;
    }

    .topbar nav {
      display: flex;
      gap: 8px;
      flex-wrap: wrap;
      align-items: center;
    }

    .perfil-mini {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      background: rgba(217, 204, 197, 0.2);
      border: 1px solid rgba(217, 204, 197, 0.35);
      padding: 6px 10px;
      border-radius: 999px;
      max-width: 260px;
    }

    .perfil-mini-avatar {
      width: 30px;
      height: 30px;
      border-radius: 999px;
      background: #c7c8ce;
      position: relative;
      overflow: hidden;
      flex: 0 0 30px;
    }

    .perfil-mini-avatar::before {
      content: '';
      position: absolute;
      left: 50%;
      top: 5px;
      width: 9px;
      height: 9px;
      border-radius: 999px;
      transform: translateX(-50%);
      background: #9fa3b1;
    }

    .perfil-mini-avatar::after {
      content: '';
      position: absolute;
      left: 50%;
      bottom: 5px;
      width: 16px;
      height: 9px;
      border-radius: 10px 10px 6px 6px;
      transform: translateX(-50%);
      background: #9fa3b1;
    }

    .perfil-mini-avatar.has-photo::before,
    .perfil-mini-avatar.has-photo::after {
      display: none;
    }

    .perfil-mini-avatar img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .perfil-mini-info {
      min-width: 0;
      display: grid;
      line-height: 1.1;
    }

    .perfil-mini-nome {
      font-size: 0.95rem;
      font-weight: 600;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      color: #f4e4d8;
    }

    .perfil-mini-email {
      font-size: 0.8rem;
      font-weight: 400;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      color: #ebcdb8;
    }

    .btn {
      text-decoration: none;
      border: 0;
      border-radius: 9px;
      padding: 8px 12px;
      font-weight: 700;
      font-size: 0.86rem;
      cursor: pointer;
      background: #d9ccc5;
      color: #402b1f;
    }

    .page {
      max-width: 1160px;
      margin: 14px auto 0;
      padding: 0 14px 18px;
      display: grid;
      gap: 14px;
    }

    .filtros {
      background: var(--panel);
      border-radius: 14px;
      padding: 12px;
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      align-items: center;
    }

    .filtros span {
      font-weight: 700;
      font-size: 0.9rem;
      margin-right: 4px;
    }

    .filtros .btn {
      background: #e8dfda;
    }

    .filtros .btn.ativo {
      background: #402b1f;
      color: #d9ccc5;
    }

    .bloco {
      background: var(--panel);
      border-radius: 16px;
      padding: 14px;
    }

    .bloco h2 {
      margin: 0 0 10px;
      font-size: 1.25rem;
      color: #25140d;
    }

    .lista-pedidos {
      display: grid;
      gap: 10px;
    }

    .card-pedido {
      background: var(--card);
      border-radius: 12px;
      padding: 12px;
      box-shadow: 0 2px 9px rgba(0, 0, 0, 0.14);
    }

    .pedido-cabecalho {
      display: block;
      margin-bottom: 8px;
    }

    .pedido-id {
      font-size: 1.03rem;
      font-weight: 700;
    }

    .pedido-meta {
      font-size: 0.85rem;
      line-height: 1.35;
      color: #3a251a;
    }

    .etiquetas {
      display: flex;
      gap: 6px;
      flex-wrap: wrap;
      margin-top: 8px;
      justify-content: center;
      width: 100%;
    }

    .tag {
      display: inline-block;
      border-radius: 999px;
      padding: 4px 8px;
      font-size: 0.85rem;
      font-weight: 700;
      color: #fff;
    }

    .tag.pendente { background: #c99d16; }
    .tag.preparando { background: #3b77c9; }
    .tag.pronto { background: #3b77c9; }
    .tag.finalizado { background: #2b8f5a; }
    .tag.entregue { background: #2b8f5a; }
    .tag.cancelado { background: #b43a3a; }
    .tag.desconhecido { background: #6f6f6f; }

    .tag.pag_pago { background: #2b8f5a; }
    .tag.pag_pendente { background: #c99d16; }
    .tag.pag_cancelado { background: #b43a3a; }
    .tag.pag_nao_informado { background: #6f6f6f; }

    .itens {
      margin: 8px 0 0;
      padding: 0;
      list-style: none;
      display: grid;
      gap: 6px;
    }

    .item-linha {
      background: rgba(255, 255, 255, 0.36);
      border-radius: 8px;
      padding: 8px;
      font-size: 0.83rem;
      line-height: 1.35;
    }

    .item-topo {
      font-weight: 700;
      margin-bottom: 4px;
    }

    .item-desc {
      font-size: 0.85rem;
      color: #4a2d1f;
      margin-bottom: 4px;
      line-height: 1.3;
    }

    .item-config {
      font-size: 0.85rem;
      color: #3f271b;
    }

    .total {
      margin-top: 8px;
      font-weight: 700;
      text-align: right;
      font-size: 0.95rem;
      color: #23140d;
    }

    .pedido-actions {
      margin-top: 10px;
      display: flex;
      justify-content: flex-end;
    }

    .btn-cancelar {
      background: #8f2f2f;
      color: #fff;
    }

    .btn-cancelar:disabled {
      opacity: 0.7;
      cursor: wait;
    }

    .vazio {
      background: var(--card);
      border-radius: 12px;
      padding: 16px;
      text-align: center;
    }

    .vazio p {
      margin: 0 0 10px;
      font-size: 0.96rem;
    }

    @media (max-width: 760px) {
      .topbar {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
      }

      .pedido-cabecalho {
        flex-direction: column;
      }
    }
  </style>
  <link rel="stylesheet" href="topbar.css" />
</head>
<body>
  <?php
    $nomeExibicao = trim((string) ($nomeUsuario ?? ''));
    if ($nomeExibicao === '') {
      $nomeExibicao = 'Usuario';
    }
    $emailExibicao = trim((string) ($emailUsuario ?? ''));
    $fotoExibicao = trim((string) ($fotoUsuario ?? ''));
    $temFoto = $fotoExibicao !== '';
  ?>
  <header class="topbar site-topbar">
    <a class="site-topbar-brand" href="index.php" aria-label="Inicio">
      <picture class="site-topbar-logo-wrap">
        <source media="(max-width: 640px)" srcset="imagens/logo-mobile.png">
        <img class="site-topbar-logo" src="imagens/logo-topbar.png" alt="Sabores Tecnicos - Cantina Online">
      </picture>
    </a>
    <nav class="site-topbar-actions">
      <div class="perfil-mini">
        <span class="perfil-mini-avatar <?php echo $temFoto ? 'has-photo' : ''; ?>" aria-hidden="true">
          <?php if ($temFoto): ?>
            <img src="<?php echo htmlspecialchars($fotoExibicao, ENT_QUOTES, 'UTF-8'); ?>" alt="">
          <?php endif; ?>
        </span>
        <span class="perfil-mini-info">
          <span class="perfil-mini-nome"><?php echo htmlspecialchars($nomeExibicao, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php if ($emailExibicao !== ''): ?>
            <span class="perfil-mini-email"><?php echo htmlspecialchars($emailExibicao, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
        </span>
      </div>
      <a class="btn site-topbar-btn" href="index.php">Ver produtos</a>
      <a class="btn site-topbar-btn" href="perfil.php">Perfil</a>
      <a class="btn site-topbar-btn" href="deslogar.php">Deslogar</a>
    </nav>
  </header>

  <main class="page">
    <section class="filtros">
      <span>Filtros:</span>
      <button class="btn ativo" data-filtro="todos">Todos</button>
      <button class="btn" data-filtro="pendentes">Pendentes</button>
      <button class="btn" data-filtro="entregues">Entregues</button>
      <button class="btn" data-filtro="cancelados">Cancelados</button>
      <button class="btn" data-filtro="pagos">Pagos</button>
      <button class="btn" data-filtro="nao_pagos">Não pagos</button>
    </section>

    <section class="bloco">
      <h2>Pedidos atuais</h2>
      <div id="listaAtuais" class="lista-pedidos"></div>
    </section>

    <section class="bloco">
      <h2>Histórico de pedidos</h2>
      <div id="listaHistorico" class="lista-pedidos"></div>
    </section>
  </main>

  <script src="meus_pedidos.js"></script>
</body>
</html>
