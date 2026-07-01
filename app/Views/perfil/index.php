<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Perfil do Usuário - Cantina</title>
  <link rel="shortcut icon" href="favicon.ico" type="image/x-icon">
  <link rel="stylesheet" href="perfil.css" />
</head>
<body>
  <?php
    $perfil = is_array($perfil ?? null) ? $perfil : [];
    $pedidosFeitos = is_array($pedidosFeitos ?? null) ? $pedidosFeitos : [];
    $pedidosPendentes = is_array($pedidosPendentes ?? null) ? $pedidosPendentes : [];
    $flash = is_array($flash ?? null) ? $flash : null;
    $tipoUsuarioLabel = (string) ($tipoUsuarioLabel ?? 'Usuario');

    $nome = trim((string) ($perfil['nome'] ?? 'Usuario'));
    $email = trim((string) ($perfil['email'] ?? ''));
    $nick = trim((string) ($perfil['nick'] ?? ''));
    $login = trim((string) ($perfil['login'] ?? ''));
    $endereco = trim((string) ($perfil['endereco'] ?? ''));
    $foto = trim((string) ($perfil['foto'] ?? ''));

    $abaAtiva = strtolower(trim((string) ($_GET['aba'] ?? 'dados')));
    if (!in_array($abaAtiva, ['dados', 'senha', 'foto'], true)) {
      $abaAtiva = 'dados';
    }

  ?>

  <div class="perfil-layout">
    <aside class="perfil-lateral">
      <div class="perfil-cartao-usuario">
        <div class="avatar-grande">
          <?php if ($foto !== ''): ?>
            <img src="<?php echo htmlspecialchars($foto, ENT_QUOTES, 'UTF-8'); ?>" alt="Foto de perfil" />
          <?php endif; ?>
        </div>
        <h2><?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?></h2>
        <p><?php echo htmlspecialchars($tipoUsuarioLabel, ENT_QUOTES, 'UTF-8'); ?></p>
      </div>

      <nav class="perfil-menu">
        <a href="#secao-dados" class="perfil-menu-item">Dados Pessoais</a>
        <a href="#secao-feitos" class="perfil-menu-item">Pedidos Já Feitos</a>
        <a href="#secao-pendentes" class="perfil-menu-item">Pedidos Pendentes</a>
        <a href="index.php" class="perfil-menu-item">Início</a>
        <?php if (($perfil['tipo'] ?? '') === 'admin'): ?>
          <a href="admin.php" class="perfil-menu-item">Painel de Admin</a>
        <?php endif; ?>
        <a href="deslogar.php" class="perfil-menu-item">Sair</a>
      </nav>
    </aside>

    <main class="perfil-conteudo">
      <section id="secao-dados" class="perfil-painel">
        <div class="perfil-titulo-wrap">
          <h1>Dados do Usuário</h1>
          <p>Visualize e atualize suas informações da conta.</p>
        </div>

        <?php if ($flash !== null && trim((string) ($flash['message'] ?? '')) !== ''): ?>
          <div class="alerta <?php echo ($flash['type'] ?? '') === 'error' ? 'alerta-erro' : 'alerta-sucesso'; ?>">
            <?php echo htmlspecialchars((string) ($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <div class="abas">
          <button class="aba-btn <?php echo $abaAtiva === 'dados' ? 'ativo' : ''; ?>" type="button" data-aba="dados">Dados Pessoais</button>
          <button class="aba-btn <?php echo $abaAtiva === 'senha' ? 'ativo' : ''; ?>" type="button" data-aba="senha">Senha</button>
          <button class="aba-btn <?php echo $abaAtiva === 'foto' ? 'ativo' : ''; ?>" type="button" data-aba="foto">Foto</button>
        </div>

        <div class="aba-conteudo <?php echo $abaAtiva === 'dados' ? 'ativo' : ''; ?>" data-painel="dados">
          <form method="post" action="perfil.php" class="form-dados">
            <input type="hidden" name="acao" value="salvar_dados" />

            <div class="form-grid">
              <label>
                <span>Nome completo</span>
                <input type="text" name="nome" maxlength="120" required value="<?php echo htmlspecialchars($nome, ENT_QUOTES, 'UTF-8'); ?>" />
              </label>

              <label>
                <span>E-mail</span>
                <input type="email" name="email" maxlength="190" required value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>" />
              </label>

              <label>
                <span>Nick</span>
                <input type="text" name="nick" maxlength="60" value="<?php echo htmlspecialchars($nick, ENT_QUOTES, 'UTF-8'); ?>" />
              </label>

              <label>
                <span>Login</span>
                <input type="text" name="login" maxlength="40" value="<?php echo htmlspecialchars($login, ENT_QUOTES, 'UTF-8'); ?>" />
              </label>

              <label class="full">
                <span>Endereço</span>
                <input type="text" name="endereco" maxlength="255" value="<?php echo htmlspecialchars($endereco, ENT_QUOTES, 'UTF-8'); ?>" />
              </label>
            </div>

            <div class="form-acoes">
              <button type="submit" class="btn-principal">Gravar Alterações</button>
            </div>
          </form>
        </div>

        <div class="aba-conteudo <?php echo $abaAtiva === 'senha' ? 'ativo' : ''; ?>" data-painel="senha">
          <form method="post" action="perfil.php?aba=senha" class="form-dados">
            <input type="hidden" name="acao" value="alterar_senha" />
            <div class="form-grid">
              <label class="full">
                <span>Senha atual</span>
                <input type="password" name="senha_atual" minlength="8" required />
              </label>
              <label>
                <span>Nova senha</span>
                <input type="password" name="senha_nova" minlength="8" required />
              </label>
              <label>
                <span>Confirmar nova senha</span>
                <input type="password" name="senha_confirmacao" minlength="8" required />
              </label>
            </div>

            <div class="form-acoes">
              <button type="submit" class="btn-principal">Alterar Senha</button>
            </div>
          </form>
        </div>

        <div class="aba-conteudo <?php echo $abaAtiva === 'foto' ? 'ativo' : ''; ?>" data-painel="foto">
          <form method="post" action="perfil.php?aba=foto" enctype="multipart/form-data" class="form-foto">
            <input type="hidden" name="acao" value="alterar_foto" />
            <div class="foto-preview">
              <div class="avatar-grande">
                <?php if ($foto !== ''): ?>
                  <img src="<?php echo htmlspecialchars($foto, ENT_QUOTES, 'UTF-8'); ?>" alt="Foto de perfil" />
                <?php endif; ?>
              </div>
              <p>Envie uma imagem JPG, PNG ou WEBP de até 3MB.</p>
            </div>

            <label class="upload-field">
              <span>Selecionar foto</span>
              <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" required />
            </label>

            <div class="form-acoes">
              <button type="submit" class="btn-principal">Atualizar Foto</button>
            </div>
          </form>
        </div>
      </section>

      <section class="cards-grid">
        <article id="secao-feitos" class="card-resumo">
          <div class="card-cabecalho">
            <h2>Pedidos Já Feitos</h2>
            <a href="meus_pedidos.php">Ver todos</a>
          </div>

          <?php if ($pedidosFeitos === []): ?>
            <p class="vazio">Nenhum pedido encontrado.</p>
          <?php else: ?>
            <div class="lista-card">
              <?php foreach ($pedidosFeitos as $pedido): ?>
                <div class="item-card">
                  <strong><?php echo htmlspecialchars((string) ($pedido['produto'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                  <span><?php echo htmlspecialchars((string) ($pedido['descricao'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                  <span><?php echo htmlspecialchars((string) ($pedido['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                  <span>Pagamento: <?php echo htmlspecialchars((string) ($pedido['forma_pagamento'] ?? 'Não informado'), ENT_QUOTES, 'UTF-8'); ?></span>
                  <span><?php echo htmlspecialchars((string) ($pedido['data'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                  <span><?php echo htmlspecialchars((string) ($pedido['valor_total'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </article>

        <article id="secao-pendentes" class="card-resumo">
          <div class="card-cabecalho">
            <h2>Pedidos Pendentes (Não Entregues)</h2>
          </div>

          <?php if ($pedidosPendentes === []): ?>
            <p class="vazio">Nenhum pedido pendente no momento.</p>
          <?php else: ?>
            <div class="lista-card">
              <?php foreach ($pedidosPendentes as $pedido): ?>
                <div class="item-card">
                  <strong><?php echo htmlspecialchars((string) ($pedido['produto'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                  <span><?php echo htmlspecialchars((string) ($pedido['descricao'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                  <span>Status: <?php echo htmlspecialchars((string) ($pedido['status'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                  <span>Pagamento: <?php echo htmlspecialchars((string) ($pedido['forma_pagamento'] ?? 'Nao informado'), ENT_QUOTES, 'UTF-8'); ?></span>
                  <span><?php echo htmlspecialchars((string) ($pedido['data'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                  <span><?php echo htmlspecialchars((string) ($pedido['valor_total'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </article>
      </section>
    </main>
  </div>

  <script src="perfil.js"></script>
</body>
</html>
