USE frimesa;

-- Cria a tabela categorias
CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(255) NOT NULL UNIQUE
);

-- Insere categorias de exemplo na tabela categorias
INSERT
    IGNORE INTO categorias (nome)
VALUES
    ('Iogurtes'),
    ('Queijos'),
    ('Requeijão'),
    ('Manteiga'),
    ('Doce de Leite'),
    ('Creme de Leite'),
    ('Leite Condensado'),
    ('Presunto'),
    ('Mortadela'),
    ('Linguiça Calabresa'),
    ('Congelados');

-- Cria a tabela produtos
CREATE TABLE IF NOT EXISTS produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    camara VARCHAR(255) NOT NULL,
    bloco VARCHAR(255) NOT NULL,
    posicao_bloco VARCHAR(255) NOT NULL,
    nivel VARCHAR(255) NOT NULL,
    categoria_id INT NOT NULL,
    nome VARCHAR(255) NOT NULL,
    quantidade INT NOT NULL CHECK (quantidade >= 0),
    peso_liquido DECIMAL(10, 2) NOT NULL CHECK (peso_liquido >= 0),
    peso_bruto DECIMAL(10, 2) NOT NULL CHECK (peso_bruto >= 0),
    codigo_barras VARCHAR(255) NOT NULL UNIQUE,
    data_fabricacao DATE NOT NULL,
    data_validade DATE NOT NULL,
    foto_produto VARCHAR(255),
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE CASCADE
);

-- Cria a tabela de usuários
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    profile_picture VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE recebimentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    codigo_barras VARCHAR(50) NOT NULL,
    quantidade INT NOT NULL,
    camara VARCHAR(255) NOT NULL,
    bloco VARCHAR(255) NOT NULL,
    posicao_bloco VARCHAR(255) NOT NULL,
    nivel VARCHAR(255) NOT NULL,
    categoria_id INT NOT NULL,
    data_fabricacao DATE NOT NULL,
    data_validade DATE NOT NULL,
    usuario_id INT NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (codigo_barras) REFERENCES produtos(codigo_barras)
);

-- Atualiza a tabela de histórico de movimentações para incluir novos tipos de operação
CREATE TABLE IF NOT EXISTS historico_movimentacoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    data TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    codigo_barras VARCHAR(50) NOT NULL,
    quantidade INT NOT NULL,
    operacao ENUM(
        'Adicionar Quantidade',
        'Remover Quantidade',
        'Alterar Endereço',
        'Adicionar Produto',
        'Atualizar Preço',
        'Mudar Categoria',
        'Transferir Produto',
        'Ajuste de Estoque',
        'Outros'
    ) NOT NULL,
    usuario_id INT NOT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (codigo_barras) REFERENCES produtos(codigo_barras)
);

-- Adiciona índices para melhorar a performance das consultas
CREATE INDEX idx_categoria_id ON produtos(categoria_id);

CREATE INDEX idx_nome ON produtos(nome);

CREATE INDEX idx_camara ON produtos(camara);

CREATE INDEX idx_codigo_barras ON historico_movimentacoes(codigo_barras);

CREATE INDEX idx_usuario_id ON historico_movimentacoes(usuario_id);


import sqlite3

# Conectando ao banco de dados (ou criando, caso não exista)
conn = sqlite3.connect('produtos.db')
cursor = conn.cursor()

# Criando a tabela de produtos
cursor.execute('''
CREATE TABLE IF NOT EXISTS produtos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    codigo_barras TEXT NOT NULL,
    nome TEXT NOT NULL,
    preco REAL NOT NULL
)
''')

# Inserindo um produto como exemplo
cursor.execute('''
INSERT INTO produtos (codigo_barras, nome, preco)
VALUES ('123456789012', 'Produto Exemplo', 19.99)
''')

# Salvando as alterações e fechando a conexão
conn.commit()
conn.close()
