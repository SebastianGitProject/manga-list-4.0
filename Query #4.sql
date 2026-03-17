-- Aggiornamenti database per nuove funzionalità
USE manga_collection;

-- 1. Aggiungere colonne alle tabelle serie_manga e variant_manga
ALTER TABLE serie_manga 
ADD COLUMN stato ENUM('in_corso', 'completo', 'interrotta') DEFAULT 'completo',
ADD COLUMN da_prendere_subito BOOLEAN DEFAULT FALSE,
ADD COLUMN categorie TEXT; -- JSON array di categorie

ALTER TABLE variant_manga 
ADD COLUMN stato ENUM('in_corso', 'completo', 'interrotta') DEFAULT 'completo',
ADD COLUMN da_prendere_subito BOOLEAN DEFAULT FALSE,
ADD COLUMN categorie TEXT; -- JSON array di categorie

-- 2. Creare tabella per Libri Normali
CREATE TABLE IF NOT EXISTS libri_normali (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titolo VARCHAR(255) UNIQUE NOT NULL,
    immagine_url TEXT,
    data_pubblicazione DATE,
    prezzo DECIMAL(10,2),
    autore VARCHAR(255),
    posseduto BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. Creare tabella per Vinili e CD
CREATE TABLE IF NOT EXISTS vinili_cd (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo ENUM('vinile', 'cd') NOT NULL,
    titolo VARCHAR(255) NOT NULL,
    immagine_url TEXT,
    data_pubblicazione DATE,
    costo DECIMAL(10,2),
    autore VARCHAR(255),
    posseduto BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_vinile_cd (tipo, titolo, autore)
);

-- 4. Creare indici per migliorare le performance
CREATE INDEX idx_serie_stato ON serie_manga(stato);
CREATE INDEX idx_serie_da_prendere ON serie_manga(da_prendere_subito);
CREATE INDEX idx_variant_stato ON variant_manga(stato);
CREATE INDEX idx_variant_da_prendere ON variant_manga(da_prendere_subito);
CREATE INDEX idx_libri_titolo ON libri_normali(titolo);
CREATE INDEX idx_libri_autore ON libri_normali(autore);
CREATE INDEX idx_libri_posseduto ON libri_normali(posseduto);
CREATE INDEX idx_vinili_tipo ON vinili_cd(tipo);
CREATE INDEX idx_vinili_titolo ON vinili_cd(titolo);
CREATE INDEX idx_vinili_autore ON vinili_cd(autore);
CREATE INDEX idx_vinili_posseduto ON vinili_cd(posseduto);

-- 5. Aggiornare i dati esistenti (serie_manga con stato 'completo', altri campi vuoti)
UPDATE serie_manga 
SET stato = 'completo', 
    da_prendere_subito = FALSE, 
    categorie = NULL 
WHERE stato IS NULL OR stato = '';

UPDATE variant_manga 
SET stato = 'completo', 
    da_prendere_subito = FALSE, 
    categorie = NULL 
WHERE stato IS NULL OR stato = '';

-- 6. Vista per ottenere tutte le categorie uniche per serie_manga
CREATE OR REPLACE VIEW categorie_serie AS
SELECT DISTINCT 
    JSON_UNQUOTE(JSON_EXTRACT(categorie, CONCAT('$[', numbers.n, ']'))) as categoria
FROM serie_manga
CROSS JOIN (
    SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
    UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
) numbers
WHERE JSON_VALID(categorie) 
  AND JSON_LENGTH(categorie) > numbers.n
  AND JSON_UNQUOTE(JSON_EXTRACT(categorie, CONCAT('$[', numbers.n, ']'))) IS NOT NULL;

-- 7. Vista per ottenere tutte le categorie uniche per variant_manga
CREATE OR REPLACE VIEW categorie_variant AS
SELECT DISTINCT 
    JSON_UNQUOTE(JSON_EXTRACT(categorie, CONCAT('$[', numbers.n, ']'))) as categoria
FROM variant_manga
CROSS JOIN (
    SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 
    UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9
) numbers
WHERE JSON_VALID(categorie) 
  AND JSON_LENGTH(categorie) > numbers.n
  AND JSON_UNQUOTE(JSON_EXTRACT(categorie, CONCAT('$[', numbers.n, ']'))) IS NOT NULL;