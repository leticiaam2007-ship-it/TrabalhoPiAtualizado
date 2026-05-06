<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Login - Cantina</title>
  <link rel="stylesheet" href="style.css" />
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  <style>
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
  </style>
</head>

<body>
  <header class="topbar">
    <div class="logo">Cantina</div>
  </header>

  <?php
  include("config.php");

  $conexao = obterConexao();

  $email = $_POST['email'] ?? null;
  $senha = $_POST['senha'] ?? null;
  if (isset($email) && isset($senha)) {
    $stmt = mysqli_prepare($conexao, "SELECT * FROM usuarios where email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    session_start();
    try {
      $exec = mysqli_stmt_execute($stmt);
      if ($exec == true) {
        $result = mysqli_stmt_get_result($stmt);
        echo "<br><br><br>";
        // 4. Buscar os dados
        while ($row = mysqli_fetch_assoc($result)) {
          if ($row['senha'] == $senha) {
            $_SESSION['email'] = $row['email'];
            $_SESSION['usuario_id'] = $row['id'];
            $_SESSION['tipo'] = $row['tipo'];
            return header("Location: index.php");
          }
        }
        return header("Location: login.php?erro=4");
      } else {
        return header("Location: login.php?erro=4");
      }
    } catch (\Throwable $th) {
      // var_dump($th);
      return header("Location: login.php?erro=7");
    }
  }



  $mensagem = null;
  if (isset($_GET['erro'])) {
    if ($_GET['erro'] == 4) {
      $mensagem = "Usuariojá existe";
    }
  } else if (isset($_GET['cad'])) {
    if ($_GET['cad'] == 'ok') {
      $mensagem = "Cadastro realizado";
    }
  }

  ?>
  <main class="auth-page">
    <div class="auth-card">
      <div><?php if ($mensagem != null) {
        echo $mensagem;
      } ?></div>
      <div class="tabs-auth">
        <button id="btnShowLogin" class="btn primary">Entrar</button>
        <button id="btnShowRegister" class="btn outline">Registrar</button>
      </div>

      <form id="loginForm" method="post" action="/cantina/login.php">
        <h2>Entrar</h2>
        <div class="field"><label>Email</label><input id="loginEmail" name="email" type="email" required /></div>
        <div class="field"><label>Senha</label><input id="loginSenha" name="senha" type="password" required /></div>
        <div style="display:flex;gap:8px;align-items:center">
          <input class="inputSubmit" type="submit" name="submit" value="Enviar">
          <a href="index.php" class="btn outline" style="margin-left:auto">Voltar</a>
        </div>
        <div id="loginMsg" class="note"></div>
      </form>

      <form id="registerForm" style="display:none" method="post" action="/cantina/register.php">
        <h2>Cadastrar</h2>
        <div class="field"><label>Nome</label><input id="regNome" name="nome" required /></div>
        <div class="field"><label>Email</label><input id="regEmail" name="email" type="email" required /></div>
        <div class="field"><label>Senha</label><input id="regSenha" name="senha" type="password" required /></div>
        <div style="display:flex;gap:8px;align-items:center">
          <button type="submit" class="btn primary">Registrar</button>
          <button id="regCancelar" type="button" class="btn outline" style="margin-left:auto">Cancelar</button>
        </div>
        <div id="regMsg" class="note"></div>
        <div class="small-note">Ao registrar, você já poderá entrar com seu email e senha.</div>
      </form>
    </div>
  </main>

  <script src="login.js"></script>
</body>

</html>