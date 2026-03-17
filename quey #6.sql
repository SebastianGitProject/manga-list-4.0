-- Aggiornamenti database per tutte le nuove funzionalità
USE manga_collection;

-- 1. Tabella per Manga Stranieri (stessa struttura di serie_manga)
CREATE TABLE IF NOT EXISTS manga_stranieri (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titolo VARCHAR(255) UNIQUE NOT NULL,
    immagine_url TEXT,
    data_pubblicazione DATE,
    volumi_totali INT NOT NULL,
    volumi_posseduti INT DEFAULT 0,
    prezzo_medio DECIMAL(10,2) DEFAULT 0.00,
    stato ENUM('in_corso', 'completo', 'interrotta') DEFAULT 'completo',
    da_prendere_subito BOOLEAN DEFAULT FALSE,
    priorita INT DEFAULT NULL COMMENT 'Numero di priorità per "da prendere subito"',
    categorie TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. Tabella per volumi posseduti manga stranieri
CREATE TABLE IF NOT EXISTS volumi_posseduti_stranieri (
    id INT PRIMARY KEY AUTO_INCREMENT,
    serie_id INT NOT NULL,
    numero_volume INT NOT NULL,
    posseduto BOOLEAN DEFAULT TRUE,
    data_acquisto DATE NULL,
    prezzo_pagato DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (serie_id) REFERENCES manga_stranieri(id) ON DELETE CASCADE,
    UNIQUE KEY unique_volume (serie_id, numero_volume)
);

-- 3. Tabella per Libreria Update (già nel Query #4)
CREATE TABLE IF NOT EXISTS libreria_update (
    id INT PRIMARY KEY AUTO_INCREMENT,
    immagine_url TEXT NOT NULL,
    data_aggiunta DATE NOT NULL,
    numero_manga INT NOT NULL COMMENT 'Numero totale di manga nella foto',
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 4. Tabella per Libri Lovecraft (già nel Query #4)
CREATE TABLE IF NOT EXISTS libri_lovecraft (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titolo VARCHAR(255) NOT NULL,
    immagine_url TEXT,
    data_pubblicazione DATE,
    costo DECIMAL(10,2),
    posseduto BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_titolo (titolo)
);

-- 5. Tabella per Libri Giapponesi (già nel Query #4)
CREATE TABLE IF NOT EXISTS libri_giapponesi (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titolo VARCHAR(255) NOT NULL,
    immagine_url TEXT,
    costo DECIMAL(10,2),
    autore VARCHAR(255),
    posseduto BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_titolo_autore (titolo, autore)
);

-- 6. Indici per migliorare le performance
CREATE INDEX idx_stranieri_stato ON manga_stranieri(stato);
CREATE INDEX idx_stranieri_priorita ON manga_stranieri(priorita);
CREATE INDEX idx_stranieri_da_prendere ON manga_stranieri(da_prendere_subito);
CREATE INDEX idx_stranieri_titolo ON manga_stranieri(titolo);

CREATE INDEX idx_libreria_data ON libreria_update(data_aggiunta);

CREATE INDEX idx_lovecraft_titolo ON libri_lovecraft(titolo);
CREATE INDEX idx_lovecraft_posseduto ON libri_lovecraft(posseduto);

CREATE INDEX idx_giapponesi_titolo ON libri_giapponesi(titolo);
CREATE INDEX idx_giapponesi_autore ON libri_giapponesi(autore);
CREATE INDEX idx_giapponesi_posseduto ON libri_giapponesi(posseduto);

-- 7. Vista per manga stranieri con priorità
CREATE OR REPLACE VIEW manga_stranieri_con_priorita AS
SELECT 
    ms.*,
    COALESCE(vps.volumi_posseduti_count, 0) as volumi_posseduti_actual
FROM manga_stranieri ms
LEFT JOIN (
    SELECT serie_id, COUNT(*) as volumi_posseduti_count
    FROM volumi_posseduti_stranieri 
    WHERE posseduto = TRUE 
    GROUP BY serie_id
) vps ON ms.id = vps.serie_id
WHERE ms.da_prendere_subito = 1
ORDER BY 
    CASE WHEN ms.priorita IS NULL THEN 1 ELSE 0 END,
    ms.priorita ASC,
    ms.titolo ASC;