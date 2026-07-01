<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Cantina - Home</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="stylesheet" href="topbar.css" />
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
</head>

<body>
  <?php
    $nomeExibicao = trim((string) ($nomeUsuario ?? ''));
    if ($nomeExibicao === '') {
      $nomeExibicao = !empty($logado) ? 'Usuario' : 'Visitante';
    }
    $emailExibicao = trim((string) ($emailUsuario ?? ''));
    if ($emailExibicao === '' && empty($logado)) {
      $emailExibicao = 'gmail@gmail.com';
    }
    $fotoExibicao = trim((string) ($fotoUsuario ?? ''));
    $temFoto = $fotoExibicao !== '';
  ?>
  <header class="topbar site-topbar site-topbar-fixed">
    <div class="topbar-left site-topbar-left">
      <button
        id="menuCategoriasToggle"
        class="menu-toggle"
        type="button"
        aria-label="Abrir menu de categorias"
        aria-expanded="false"
        aria-controls="menuCategorias"
      >
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
        <span class="hamburger-line"></span>
      </button>
    </div>

    <a class="site-topbar-brand" href="index.php" aria-label="Inicio">
      <picture class="site-topbar-logo-wrap">
        <source media="(max-width: 640px)" srcset="imagens/logo-mobile.png">
        <img class="site-topbar-logo" src="imagens/logo-topbar.png" alt="Sabores Tecnicos - Cantina Online">
      </picture>
    </a>

    <nav class="topbar-right site-topbar-actions">
      <button
        id="menuUsuarioToggle"
        class="btn btn-user"
        type="button"
        aria-label="Abrir menu do usuario"
        aria-expanded="false"
        aria-controls="menuUsuario"
      >
        <span class="user-chip-avatar <?php echo $temFoto ? 'has-photo' : ''; ?>" aria-hidden="true">
          <?php if ($temFoto): ?>
            <img class="user-avatar-img" src="<?php echo htmlspecialchars($fotoExibicao, ENT_QUOTES, 'UTF-8'); ?>" alt="">
          <?php endif; ?>
        </span>
        <span class="user-chip-text">
          <span class="user-chip-name"><?php echo htmlspecialchars($nomeExibicao, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php if ($emailExibicao !== ''): ?>
            <span class="user-chip-email"><?php echo htmlspecialchars($emailExibicao, ENT_QUOTES, 'UTF-8'); ?></span>
          <?php endif; ?>
        </span>
      </button>
      <button id="abrirCarrinho" class="btn site-topbar-btn">Carrinho (<span id="contador">0</span>)</button>
    </nav>
  </header>

  <div id="menuOverlay" class="menu-overlay" hidden></div>

  <aside id="menuCategorias" class="floating-menu left-menu" aria-hidden="true" hidden>
    <div class="floating-menu-header">
      <h2>Categoria</h2>
    </div>
    <nav id="menuCategoriasLista" class="floating-menu-list">
      <a href="#salgados" class="menu-item menu-item-link" data-menu-target="salgados"><span>Salgados</span></a>
      <a href="#marmitas" class="menu-item menu-item-link" data-menu-target="marmitas"><span>Marmitas</span></a>
      <a href="#bebidas" class="menu-item menu-item-link" data-menu-target="bebidas"><span>Bebidas</span></a>
    </nav>
  </aside>

  <aside id="menuUsuario" class="floating-menu right-menu user-menu" aria-hidden="true" hidden>
    <div class="user-menu-top">
      <div class="user-menu-identity">
        <span class="user-panel-avatar <?php echo $temFoto ? 'has-photo' : ''; ?>" aria-hidden="true">
          <?php if ($temFoto): ?>
            <img class="user-avatar-img" src="<?php echo htmlspecialchars($fotoExibicao, ENT_QUOTES, 'UTF-8'); ?>" alt="">
          <?php endif; ?>
        </span>
        <div class="user-menu-meta">
          <div class="user-menu-title"><?php echo htmlspecialchars($nomeExibicao, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php if ($emailExibicao !== ''): ?>
            <div class="user-menu-email"><?php echo htmlspecialchars($emailExibicao, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="user-menu-action">
        <?php if (!empty($logado)): ?>
          <a href="deslogar.php" class="user-logout-btn">Sair</a>
        <?php else: ?>
          <a href="login.php" class="user-logout-btn">Entrar</a>
        <?php endif; ?>
      </div>
    </div>

    <nav class="floating-menu-list user-panel-links">
      <?php if (!empty($logado)): ?>
        <a href="perfil.php" class="menu-item menu-item-link user-panel-link">Perfil</a>
        <a href="meus_pedidos.php" class="menu-item menu-item-link user-panel-link">Meus pedidos</a>
        <?php if (($tipoUsuario ?? null) === 'admin'): ?>
          <a href="admin.php" class="menu-item menu-item-link user-panel-link">Painel de admin</a>
        <?php endif; ?>
      <?php else: ?>
        <a href="login.php" class="menu-item menu-item-link user-panel-link">Login / Registrar</a>
      <?php endif; ?>
    </nav>
  </aside>

  <main class="container">
    <section class="hero">
      <div class="window">
        <div class="window-inner">
          <h1>Cardapio</h1>
          <div class="subtitle">Nossas melhores opções</div>
        </div>
      </div>
    </section>

    <section id="produtos" class="grid-sections"></section>
  </main>

  <aside id="carrinho" class="cart">
    <div class="cart-header">
      <h2>Seu Carrinho</h2>
      <button id="fecharCarrinho" class="close">X</button>
    </div>
    <div id="itensCarrinho" class="cart-items"></div>
    <div class="cart-footer">
      <div class="total">Total: R$ <span id="total">0,00</span></div>
      <div class="cart-actions">
        <button id="limparCarrinho" class="btn outline">Limpar</button>
        <button id="finalizar" class="btn primary">Finalizar</button>
      </div>
    </div>
  </aside>

  <script src="script.js"></script>
</body>

<footer class="footer">
  <div class="footer-content">
    <div class="footer-section">
      <h3>Sobre Nós</h3>
      <p>Somos uma equipe dedicada a fornecer o melhor PI já feito.</p>
    </div>

    <div class="footer-section">
      <h3>Contato</h3>
      <ul class="contact-info">
        <li>(48) 99603-1418</li>
        <li>saborestecnicos@gmail.com</li>
        <li>Gaspar, SC</li>
      </ul>
    </div>

    <div class="footer-section">
      <h3>Redes Sociais</h3>
      <div class="social-links">
        <a href="https://www.instagram.com/vitorv1e1ra/" target="_blank">Vieira</a>
        <a href="https://www.instagram.com/leticia.almeidamomo/" target="_blank">Lelet</a>
        <a href="https://www.instagram.com/catarinycruz_/" target="_blank">Catariny</a>
      </div>
    </div>
  </div>

  <div class="footer-bottom">
    <p>&copy; 2025 Cantina. Todos os direitos reservados.</p>
  </div>
</footer>

</html>
