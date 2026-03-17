DROP DATABASE IF EXISTS manga_collection;
CREATE DATABASE IF NOT EXISTS manga_collection;
USE manga_collection;

-- Tabella per le serie manga
CREATE TABLE serie_manga (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titolo VARCHAR(255) UNIQUE NOT NULL,
    immagine_url TEXT,
    data_pubblicazione DATE,
    volumi_totali INT NOT NULL,
    volumi_posseduti INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Tabella per le variant
CREATE TABLE variant_manga (
    id INT PRIMARY KEY AUTO_INCREMENT,
    titolo VARCHAR(255) UNIQUE NOT NULL,
    immagine_url TEXT,
    data_rilascio DATE,
    costo_medio DECIMAL(10,2),
    posseduto BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);