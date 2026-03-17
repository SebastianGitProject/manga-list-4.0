-- Aggiornamento database per le nuove sezioni
USE manga_collection;

-- Tabella per Funko Pop
CREATE TABLE IF NOT EXISTS funko_pop (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) UNIQUE NOT NULL,
    immagine_url TEXT,
    data_pubblicazione DATE,
    prezzo DECIMAL(10,2),
    posseduto BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabella per Monster
CREATE TABLE IF NOT EXISTS monster (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) UNIQUE NOT NULL,
    immagine_url TEXT,
    data_pubblicazione DATE,
    prezzo DECIMAL(10,2),
    posseduto BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabella per Artbooks Anime
CREATE TABLE IF NOT EXISTS artbooks_anime (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) UNIQUE NOT NULL,
    immagine_url TEXT,
    data_pubblicazione DATE,
    prezzo DECIMAL(10,2),
    posseduto BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabella per Gameboys
CREATE TABLE IF NOT EXISTS gameboys (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) UNIQUE NOT NULL,
    immagine_url TEXT,
    data_pubblicazione DATE,
    prezzo DECIMAL(10,2),
    links TEXT, -- JSON array di links
    posseduto BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabella per Pokemon Game
CREATE TABLE IF NOT EXISTS pokemon_game (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) UNIQUE NOT NULL,
    immagine_url TEXT,
    data_pubblicazione DATE,
    prezzo DECIMAL(10,2),
    links TEXT, -- JSON array di links
    posseduto BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabella per Numeri Yu-Gi-Oh
CREATE TABLE IF NOT EXISTS numeri_yugioh (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) UNIQUE NOT NULL,
    codice VARCHAR(11) NOT NULL,
    immagine_url TEXT,
    data_pubblicazione DATE,
    prezzo DECIMAL(10,2),
    posseduto BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabella per Duel Masters
CREATE TABLE IF NOT EXISTS duel_masters (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(255) UNIQUE NOT NULL,
    immagine_url TEXT,
    data_pubblicazione DATE,
    prezzo DECIMAL(10,2),
    is_box BOOLEAN DEFAULT FALSE,
    posseduto BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Indici per migliorare le performance
CREATE INDEX idx_funko_nome ON funko_pop(nome);
CREATE INDEX idx_funko_posseduto ON funko_pop(posseduto);
CREATE INDEX idx_monster_nome ON monster(nome);
CREATE INDEX idx_monster_posseduto ON monster(posseduto);
CREATE INDEX idx_artbooks_nome ON artbooks_anime(nome);
CREATE INDEX idx_artbooks_posseduto ON artbooks_anime(posseduto);
CREATE INDEX idx_gameboys_nome ON gameboys(nome);
CREATE INDEX idx_gameboys_posseduto ON gameboys(posseduto);
CREATE INDEX idx_pokemon_nome ON pokemon_game(nome);
CREATE INDEX idx_pokemon_posseduto ON pokemon_game(posseduto);
CREATE INDEX idx_yugioh_nome ON numeri_yugioh(nome);
CREATE INDEX idx_yugioh_codice ON numeri_yugioh(codice);
CREATE INDEX idx_yugioh_posseduto ON numeri_yugioh(posseduto);
CREATE INDEX idx_duelmaster_nome ON duel_masters(nome);
CREATE INDEX idx_duelmaster_posseduto ON duel_masters(posseduto);
CREATE INDEX idx_duelmaster_box ON duel_masters(is_box);manga_collection