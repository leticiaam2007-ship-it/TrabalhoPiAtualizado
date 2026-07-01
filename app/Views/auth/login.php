<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Login - Cantina</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  <style>
    .site-mobile-auth-topbar {
      display: none;
    }

    .site-mobile-auth-logo {
      display: block;
      width: 88px;
      height: 64px;
      max-width: 92px;
      object-fit: contain;
    }

    .auth-page {
      display: flex;
      min-height: 100vh;
      align-items: center;
      justify-content: center;
      padding: 80px 16px;
    }

    .auth-card {
      background: var(--card);
      padding: 28px;
      border-radius: 12px;
      max-width: 420px;
      width: 100%;
      box-shadow: 0 12px 30px rgba(0, 0, 0, 0.4);
    }

    .auth-card h2 {
      margin-bottom: 12px;
      color: var(--highlight);
    }

    .field {
      margin-bottom: 12px;
      display: flex;
      flex-direction: column;
    }

    input {
      padding: 10px;
      border-radius: 8px;
      border: 1px solid rgba(0, 0, 0, 0.08);
    }

    .tabs-auth {
      display: flex;
      gap: 8px;
      margin-bottom: 16px;
    }

    .tabs-auth button {
      flex: 1
    }

    .note {
      font-size: 0.9rem;
      color: rgba(0, 0, 0, 0.6);
      margin-top: 8px;
    }

    .small-note {
      font-size: 0.85rem;
      color: rgba(0, 0, 0, 0.55);
      margin-top: 6px;
    }

    .inputSubmit {
      background: var(--highlight);
      color: #D9CCC5;
      box-shadow: 0 3px 10px var(--soft-shadow);
      cursor: pointer;
    }

    .inputSubmit:hover {
      background: var(--accent-light);
      color: #402B1F;
    }

    .msg-auth {
      margin-bottom: 12px;
      padding: 10px;
      border-radius: 8px;
      background: rgba(64, 43, 31, 0.12);
      color: #402B1F;
      font-size: 0.92rem;
    }

    @media (max-width: 640px) {
      .site-mobile-auth-topbar {
        position: sticky;
        top: 0;
        z-index: 150;
        height: 82px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 16px;
        background: #402B1F;
        border-bottom: 1px solid rgba(217, 204, 197, 0.16);
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.24);
      }

      .auth-page {
        min-height: calc(100vh - 82px);
        padding-top: 24px;
      }
    }
  </style>
</head>

<body data-open-register="<?php echo !empty($showRegister) ? '1' : '0'; ?>">
  <?php $nextValue = isset($nextTarget) ? trim((string) $nextTarget) : ''; ?>
  <?php $registerAction = 'register.php' . ($nextValue !== '' ? ('?next=' . rawurlencode($nextValue)) : ''); ?>
  <header class="site-mobile-auth-topbar" aria-label="Sabores Tecnicos">
    <img class="site-mobile-auth-logo" src="imagens/logo-mobile.png" alt="Sabores Tecnicos">
  </header>
  <main class="auth-page">
    <div class="auth-card">
      <?php if (($mensagem ?? null) !== null): ?>
        <div class="msg-auth"><?php echo htmlspecialchars((string) $mensagem, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>

      <div class="tabs-auth">
        <button id="btnShowLogin" class="btn primary">Entrar</button>
        <button id="btnShowRegister" class="btn outline">Registrar</button>
      </div>

      <form id="loginForm" method="post" action="login.php">
        <h2>Entrar</h2>
        <?php if ($nextValue !== ''): ?>
          <input type="hidden" name="next" value="<?php echo htmlspecialchars($nextValue, ENT_QUOTES, 'UTF-8'); ?>" />
        <?php endif; ?>
        <div class="field"><label>Email</label><input id="loginEmail" name="email" type="email" required /></div>
        <div class="field"><label>Senha</label><input id="loginSenha" name="senha" type="password" required /></div>
        <div style="display:flex;gap:8px;align-items:center">
          <input class="inputSubmit" type="submit" name="submit" value="Enviar">
          <a href="index.php" class="btn outline" style="margin-left:auto">Voltar</a>
        </div>
        <div id="loginMsg" class="note"></div>
      </form>

      <form id="registerForm" style="display:none" method="post" action="<?php echo htmlspecialchars($registerAction, ENT_QUOTES, 'UTF-8'); ?>">
        <h2>Cadastrar</h2>
        <?php if ($nextValue !== ''): ?>
          <input type="hidden" name="next" value="<?php echo htmlspecialchars($nextValue, ENT_QUOTES, 'UTF-8'); ?>" />
        <?php endif; ?>
        <div class="field"><label>Nome</label><input id="regNome" name="nome" required /></div>
        <div class="field"><label>Email</label><input id="regEmail" name="email" type="email" required /></div>
        <div class="field"><label>Senha</label><input id="regSenha" name="senha" type="password" required minlength="6" /></div>
        <div style="display:flex;gap:8px;align-items:center">
          <button type="submit" class="btn primary">Registrar</button>
          <button id="regCancelar" type="button" class="btn outline" style="margin-left:auto">Cancelar</button>
        </div>
        <div id="regMsg" class="note"></div>
        <div class="small-note">Ao registrar, você já podera entrar com seu email e senha.</div>
      </form>
    </div>
  </main>

  <script src="login.js"></script>
</body>

</html>
