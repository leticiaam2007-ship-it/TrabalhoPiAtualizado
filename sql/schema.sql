CREATE DATABASE IF NOT EXISTS sabores_tecnicos
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sabores_tecnicos;

CREATE TABLE IF NOT EXISTS usuarios (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  login VARCHAR(40) NULL,
  nick VARCHAR(60) NULL,
  senha VARCHAR(255) NOT NULL,
  tipo ENUM('aluno','funcionario','admin','cliente') NOT NULL DEFAULT 'cliente',
  data_nascimento DATE NULL,
  sexo ENUM('masculino','feminino','outro','nao_informado') NULL,
  endereco VARCHAR(255) NULL,
  foto VARCHAR(255) NULL,
  descricao TEXT NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO usuarios (nome, email, senha, tipo)
SELECT 'Administrador', 'admin@cantina.local', '$2y$10$N1gjtDglWTZ2VI70kqH7uuoo7xvZueEXg5N/EK2SD0wExAlYh501G', 'admin'
WHERE NOT EXISTS (
  SELECT 1 FROM usuarios WHERE email = 'admin@cantina.local'
);

CREATE TABLE IF NOT EXISTS produtos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(120) NOT NULL,
  categoria VARCHAR(50) NOT NULL,
  preco DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  descricao TEXT NULL,
  imagem LONGTEXT NULL,
  is_marmita TINYINT(1) NOT NULL DEFAULT 0,
  marmita_config LONGTEXT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pedidos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NULL,
  valor_total DECIMAL(10,2) NOT NULL,
  status ENUM('pendente','preparando','finalizado','cancelado') NOT NULL DEFAULT 'pendente',
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pedidos_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pedido_itens (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pedido_id INT UNSIGNED NOT NULL,
  produto_id INT UNSIGNED NULL,
  nome_produto VARCHAR(120) NOT NULL,
  quantidade INT UNSIGNED NOT NULL,
  preco_unitario DECIMAL(10,2) NOT NULL,
  configuracao LONGTEXT NULL,
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pedido_itens_pedido
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE,
  CONSTRAINT fk_pedido_itens_produto
    FOREIGN KEY (produto_id) REFERENCES produtos(id)
    ON UPDATE CASCADE
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pagamentos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  pedido_id INT UNSIGNED NOT NULL,
  tipo ENUM('dinheiro','cartao','pix') NOT NULL,
  status ENUM('pendente','pago','cancelado') NOT NULL DEFAULT 'pendente',
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_pagamentos_pedido
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pedidos_agendados (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT UNSIGNED NOT NULL,
  produto_nome VARCHAR(120) NOT NULL,
  descricao TEXT NULL,
  data_agendada DATETIME NOT NULL,
  valor_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  status ENUM('agendado','concluido','cancelado') NOT NULL DEFAULT 'agendado',
  criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pedidos_agendados_usuario
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
    ON UPDATE CASCADE
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE INDEX idx_produtos_categoria ON produtos(categoria);
CREATE INDEX idx_pedidos_usuario ON pedidos(usuario_id);
CREATE INDEX idx_pedido_itens_pedido ON pedido_itens(pedido_id);
CREATE INDEX idx_pagamentos_pedido ON pagamentos(pedido_id);
CREATE INDEX idx_pagamentos_status ON pagamentos(status);
CREATE INDEX idx_usuarios_login ON usuarios(login);
CREATE INDEX idx_pedidos_agendados_usuario ON pedidos_agendados(usuario_id);
CREATE INDEX idx_pedidos_agendados_status ON pedidos_agendados(status);
CREATE INDEX idx_pedidos_agendados_data ON pedidos_agendados(data_agendada);
