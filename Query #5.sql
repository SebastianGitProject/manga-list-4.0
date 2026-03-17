-- Aggiornamenti database per nuove sezioni e funzionalità priorità
USE manga_collection;

-- 1. Aggiungere campo priorità alle tabelle serie_manga e variant_manga
ALTER TABLE serie_manga 
ADD COLUMN priorita INT DEFAULT NULL COMMENT 'Numero di priorità per "da prendere subito"';

ALTER TABLE variant_manga 
ADD COLUMN priorita INT DEFAULT NULL COMMENT 'Numero di priorità per "da prendere subito"';

-- 2. Creare tabella per Libreria Update
CREATE TABLE IF NOT EXISTS libreria_update (
    id INT PRIMARY KEY AUTO_INCREMENT,
    immagine_url TEXT NOT NULL,
    data_aggiunta DATE NOT NULL,
    numero_manga INT NOT NULL COMMENT 'Numero totale di manga nella foto',
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 3. Creare tabella per Libri Lovecraft
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

-- 4. Creare tabella per Libri Giapponesi
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

-- 5. Creare indici per migliorare le performance
CREATE INDEX idx_serie_priorita ON serie_manga(priorita);
CREATE INDEX idx_variant_priorita ON variant_manga(priorita);
CREATE INDEX idx_libreria_data ON libreria_update(data_aggiunta);
CREATE INDEX idx_lovecraft_titolo ON libri_lovecraft(titolo);
CREATE INDEX idx_lovecraft_posseduto ON libri_lovecraft(posseduto);
CREATE INDEX idx_giapponesi_titolo ON libri_giapponesi(titolo);
CREATE INDEX idx_giapponesi_autore ON libri_giapponesi(autore);
CREATE INDEX idx_giapponesi_posseduto ON libri_giapponesi(posseduto);

-- 6. Vista per elementi con priorità (serie)
CREATE OR REPLACE VIEW serie_con_priorita AS
SELECT 
    sm.*,
    COALESCE(vp.volumi_posseduti_count, 0) as volumi_posseduti_actual
FROM serie_manga sm
LEFT JOIN (
    SELECT serie_id, COUNT(*) as volumi_posseduti_count
    FROM volumi_posseduti 
    WHERE posseduto = TRUE 
    GROUP BY serie_id
) vp ON sm.id = vp.serie_id
WHERE sm.da_prendere_subito = 1
ORDER BY 
    CASE WHEN sm.priorita IS NULL THEN 1 ELSE 0 END,
    sm.priorita ASC,
    sm.titolo ASC;

-- 7. Vista per elementi con priorità (variant)
CREATE OR REPLACE VIEW variant_con_priorita AS
SELECT *
FROM variant_manga
WHERE da_prendere_subito = 1
ORDER BY 
    CASE WHEN priorita IS NULL THEN 1 ELSE 0 END,
    priorita ASC,
    titolo ASC;