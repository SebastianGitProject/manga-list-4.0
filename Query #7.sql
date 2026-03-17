-- Aggiungi colonna rarità alla tabella serie_manga
ALTER TABLE serie_manga 
ADD COLUMN rarita TINYINT(1) DEFAULT NULL 
COMMENT '1-5 stelle di rarità, NULL se non impostato';

-- Aggiungi indice per migliorare performance ordinamento
CREATE INDEX idx_rarita ON serie_manga(rarita);
