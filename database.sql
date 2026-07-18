-- ============================================================
-- Delícias da Maria — Banco de Dados
-- Confeitaria: cardápio online (com seções) + painel administrativo
-- ============================================================

CREATE DATABASE IF NOT EXISTS delicias_de_maria
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE delicias_de_maria;

-- ------------------------------------------------------------
-- Administradores (login do painel)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  senha_hash VARCHAR(255) NOT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Contas iniciais (as senhas já estão criptografadas com bcrypt,
-- nunca fique com a senha em texto puro no banco de dados)
-- Login 1: mfatima01@gmail.com  | senha: mfatima1
-- Login 2: joaoviesilva0811@gmail.com | senha: k9djfami
INSERT INTO admins (nome, email, senha_hash) VALUES
  ('Maria de Fátima', 'mfatima01@gmail.com', '$2y$10$cfXXj/aV/YNfSeAxk3D5HuZBO6JtqMowtOaNBDXKSI5pD5PNfjmJ2'),
  ('João', 'joaoviesilva0811@gmail.com', '$2y$10$lw0W2B09RjsLdJ2/lU.vW.Jw8Ir.GhxUTCjrUQa0sET9TwzSoGWty')
ON DUPLICATE KEY UPDATE nome = VALUES(nome);

-- ------------------------------------------------------------
-- Seções do cardápio (ex: Bolos, Doces, Tortas, Salgados...)
-- Quem cria e organiza é a própria Maria, pelo painel.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categorias (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nome VARCHAR(100) NOT NULL UNIQUE,
  emoji VARCHAR(10) DEFAULT '🍰',
  ordem INT NOT NULL DEFAULT 0,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Tabela começa vazia: a Maria cria as seções que quiser no painel.

-- ------------------------------------------------------------
-- Atributos: sabores, coberturas e recheios num só lugar
-- (tipo diferencia a categoria, evita ter 3 tabelas iguais)
-- Usados opcionalmente em qualquer produto, de qualquer seção.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS atributos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  tipo ENUM('sabor', 'cobertura', 'recheio') NOT NULL,
  nome VARCHAR(100) NOT NULL,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_tipo_nome (tipo, nome)
) ENGINE=InnoDB;

-- Tabela começa vazia: cadastre no painel, em Sabores / Coberturas / Recheios.

-- ------------------------------------------------------------
-- Produtos do cardápio (bolos, doces, tortas, salgados... o que
-- a seção for) — cada produto pertence a uma única seção.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS produtos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  categoria_id INT NOT NULL,
  nome VARCHAR(150) NOT NULL,
  descricao TEXT,
  preco DECIMAL(10,2) NOT NULL DEFAULT 0,
  imagem VARCHAR(255) DEFAULT NULL,
  disponivel TINYINT(1) NOT NULL DEFAULT 1,
  criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Relação N:N entre produtos e atributos (sabores/coberturas/
-- recheios disponíveis para aquele produto específico)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS produto_atributos (
  produto_id INT NOT NULL,
  atributo_id INT NOT NULL,
  PRIMARY KEY (produto_id, atributo_id),
  FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE,
  FOREIGN KEY (atributo_id) REFERENCES atributos(id) ON DELETE CASCADE
) ENGINE=InnoDB;
